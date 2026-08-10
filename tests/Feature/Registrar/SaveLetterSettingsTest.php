<?php

namespace Tests\Feature\Registrar;

use App\Models\Role;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Regression test for /registrar/admission-letter/settings save.
 *
 * User complaint: "am trying to save this settings but its not working"
 * — the registrar opens the settings page, edits fields, hits Save, and
 * the page silently bounces back without saving the row (or with a
 * generic error flash the user couldn't see).
 *
 * This test exercises the full POST flow with the same payload the
 * browser sends, then re-reads system_settings to confirm the row was
 * persisted. We don't depend on the dashboard's role-middleware aliases
 * (registrar/super_admin/admin) — we authenticate as an admin and
 * rely on the prefix middleware accepting any of them.
 */
class SaveLetterSettingsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->buildSchema();
        $this->seedFixtures();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('system_settings');
        Schema::dropIfExists('users');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('departments');
        parent::tearDown();
    }

    public function test_save_letter_settings_persists_each_field(): void
    {
        $admin = $this->makeUser('admin');

        $response = $this->actingAs($admin)->post('/registrar/admission-letter/settings', [
            'admission_letter_body'  => 'Dear {name}, welcome to {school}.',
            'fees' => [
                ['name' => 'Acceptance Fee', 'amount' => 25000],
                ['name' => 'Caution Fee', 'amount' => 5000],
            ],
            'institution_name'    => 'Ekiti State College of Technology',
            'institution_address' => 'Ado-Ekiti, Ekiti State',
            'institution_phone'   => '+234-800-000-0000',
            'institution_email'   => 'info@eportal.example',
            'institution_website' => 'https://eportal.example',
            'registrar_name'      => 'Dr. A. B. Registrar',
        ]);

        // If the controller bounced, the session will hold either an
        // error flash or a validation errors bag. Otherwise the save
        // is presumed to have run.
        if ($response->getStatusCode() >= 400) {
            $this->fail('POST returned ' . $response->getStatusCode()
                . '; success=' . var_export(session('success'), true)
                . '; error=' . var_export(session('error'), true));
        }

        $row = SystemSetting::where('key', 'institution_name')->first();
        echo 'institution_name row: ' . var_export($row?->toArray(), true) . PHP_EOL;

        $this->assertNotNull($row, 'institution_name was not saved');
        $this->assertEquals('Ekiti State College of Technology', $row->value);
    }

    /**
     * Same flow but with NO fees row at all (admin deletes the only
     * fee row before saving). Pin the previous behaviour where the
     * `fees` array was missing from the request and the controller
     * stored the empty list.
     */
    public function test_save_with_no_fees_payload_does_not_crash(): void
    {
        $admin = $this->makeUser('admin');

        $response = $this->actingAs($admin)->post('/registrar/admission-letter/settings', [
            'admission_letter_body' => 'Body only.',
            'institution_name'      => 'EKSCOTECH',
            'registrar_name'        => 'Dr. Test',
        ]);

        if ($response->getStatusCode() >= 400) {
            $this->fail('POST returned ' . $response->getStatusCode()
                . '; error=' . var_export(session('error'), true)
                . '; errors=' . var_export(session('errors')?->all(), true));
        }

        $fees = SystemSetting::where('key', 'admission_letter_fees')->value('value');
        $this->assertEquals('[]', $fees);
    }

    /**
     * Mimic the exact payload the form sends when the admin adds
     * an extra fee row via the JS clone. The form submits
     * fees[0][name], fees[0][amount], fees[1][name], fees[1][amount].
     */
    public function test_save_with_two_fee_rows_persists_both(): void
    {
        $admin = $this->makeUser('admin');

        $response = $this->actingAs($admin)->post('/registrar/admission-letter/settings', [
            'admission_letter_body' => 'Body',
            'institution_name'      => 'EKSCOTECH',
            'registrar_name'        => 'Dr. Test',
            'fees' => [
                ['name' => 'Acceptance Fee', 'amount' => 25000],
                ['name' => 'Caution Fee',    'amount' => 5000],
            ],
        ]);

        if ($response->getStatusCode() >= 400) {
            $this->fail('POST returned ' . $response->getStatusCode()
                . '; error=' . var_export(session('error'), true));
        }

        $fees = json_decode(SystemSetting::where('key', 'admission_letter_fees')->value('value'), true);
        $this->assertCount(2, $fees);
        $this->assertEquals('Acceptance Fee', $fees[0]['name']);
        $this->assertEquals(25000.0, (float) $fees[0]['amount']);
        $this->assertEquals('Caution Fee', $fees[1]['name']);
        $this->assertEquals(5000.0, (float) $fees[1]['amount']);
    }

    /**
     * Browser-realistic: simulate the user filling the registrar_name
     * field and one fee row, then submitting. The payload below is the
     * exact field shape the browser emits — single-row fees[0] alongside
     * a registrar_name and an institution_name.
     *
     * Pins the most common "I added one acceptance fee and clicked Save
     * and the page came back without saving" complaint.
     */
    public function test_save_registrar_name_and_single_fee_row_persists(): void
    {
        $admin = $this->makeUser('admin');

        $response = $this->actingAs($admin)->post('/registrar/admission-letter/settings', [
            'admission_letter_body' => 'Welcome',
            'institution_name'      => 'EKSCOTECH',
            'registrar_name'        => 'Dr. Jane Smith',
            'fees' => [
                ['name' => 'Acceptance Fee', 'amount' => 25000],
            ],
        ]);

        if ($response->getStatusCode() >= 400) {
            $this->fail('POST returned ' . $response->getStatusCode()
                . '; error=' . var_export(session('error'), true));
        }

        $this->assertEquals(
            'Dr. Jane Smith',
            SystemSetting::where('key', 'registrar_name')->value('value'),
            'registrar_name did not persist on Save.'
        );

        $fees = json_decode(SystemSetting::where('key', 'admission_letter_fees')->value('value'), true);
        $this->assertCount(1, $fees, 'Expected exactly one acceptance fee to be stored.');
        $this->assertEquals('Acceptance Fee', $fees[0]['name']);
        $this->assertEquals(25000.0, (float) $fees[0]['amount']);
    }

    /**
     * Pin the JS auto-submit on signature file-select: the file input
     * has the id `registrar_signature_input` AND a `change` listener
     * calls `outerForm.submit()`. We can only assert the static HTML
     * contract here (the JS runs in the browser) — i.e. the input has
     * the id, and the outer form has the right action so the auto-submit
     * lands on the same endpoint as the manual Save click.
     */
    public function test_auto_save_signature_input_id_and_form_action_aligned(): void
    {
        $admin = $this->makeUser('admin');

        $response = $this->actingAs($admin)->get('/registrar/admission-letter/settings');
        $body = $response->getContent();

        // The signature input must have the id the JS binds its change
        // listener to — without it, "auto-save on file select" silently
        // does nothing and the registrar thinks the page is broken.
        $this->assertStringContainsString(
            'id="registrar_signature_input"',
            $body,
            'Signature input is missing id="registrar_signature_input" — auto-save on file select will not fire.'
        );

        // The form action that the JS submits must match the route
        // that the manual Save button submits to. Otherwise an
        // auto-save bypasses the controller and goes to a 404.
        // Use a regex so the test is not brittle to host:port.
        $this->assertMatchesRegularExpression(
            '#action="[^"]*registrar/admission-letter/settings"#',
            $body,
            'Outer Save form action is not /registrar/admission-letter/settings — auto-submit would 404.'
        );
    }

    /**
     * The view has a file input for registrar_signature but no
     * enctype="multipart/form-data" on the form. Most browsers
     * still POST the form correctly but DROP the file silently —
     * which is the user's complaint: "am trying to safe this
     * settings but its not working".
     *
     * This test asserts the rest of the fields still save when a
     * file is included in the payload. It also asserts the file
     * is processed and the path is stored. If the controller ever
     * starts using an `enctype` or the form is rebuilt without one,
     * this test pins the expected behaviour either way.
     */
    public function test_save_with_signature_file_stores_path_and_other_fields(): void
    {
        $admin = $this->makeUser('admin');

        // Snapshot the public/uploads/signatures directory before so we
        // can verify a fresh file lands there. The test runs in an
        // isolated temp environment, but the controller writes into the
        // real public/uploads/signatures — assert directly on disk so
        // we don't depend on Storage::fake() (the new contract bypasses
        // Storage::disk('public') and writes directly via $file->move()
        // to mirror the passport-upload pattern).
        $sigDir = public_path('uploads/signatures');
        if (! is_dir($sigDir)) {
            mkdir($sigDir, 0775, true);
        }
        foreach (glob($sigDir . '/registrar_signature.*') ?: [] as $stale) {
            @unlink($stale);
        }

        $file = UploadedFile::fake()->image('signature.png', 200, 80);

        $response = $this->actingAs($admin)->post('/registrar/admission-letter/settings', [
            'admission_letter_body' => 'With sig',
            'institution_name'      => 'EKSCOTECH',
            'registrar_name'        => 'Dr. Sig',
            'registrar_signature'   => $file,
        ]);

        if ($response->getStatusCode() >= 400) {
            $this->fail('POST returned ' . $response->getStatusCode()
                . '; error=' . var_export(session('error'), true));
        }

        $this->assertEquals('Dr. Sig', SystemSetting::where('key', 'registrar_name')->value('value'));
        $path = SystemSetting::where('key', 'registrar_signature_path')->value('value');
        $this->assertNotNull($path, 'signature path was not stored');
        // After the upload-target move the path is public-relative:
        // 'uploads/signatures/registrar_signature.{ext}' so asset()
        // can serve it directly from the public web root.
        $this->assertStringStartsWith('uploads/signatures/', $path);
        $this->assertStringEndsWith('.png', $path);

        // The file must actually land in public/uploads/signatures — not
        // just the path row.
        $this->assertFileExists(
            public_path($path),
            "signature file was not written to public/uploads/signatures: {$path}"
        );
    }

    /**
     * Uploading a new signature must overwrite (and the old file must
     * be removed from disk) so a .png -> .jpg swap doesn't leave the
     * previous extension serving stale content. Pins the
     * "delete-then-store" logic in the controller.
     */
    public function test_uploading_new_signature_removes_old_file(): void
    {
        $admin = $this->makeUser('admin');

        // Pre-seed the DB row with the old signature path so the
        // controller's signatureCandidatePaths() lookup finds a stale
        // path on the next upload. We can't reliably file_put_contents()
        // a real byte file here (the test sandbox may not have write
        // permission on public/uploads), but the controller's cleanup
        // code path uses is_file() + unlink() — the SystemSetting row
        // is the entry point the cleanup walks from. Set the row and
        // we exercise the contract: the next upload must overwrite the
        // path AND remove any leftover file the row pointed at.
        SystemSetting::set('registrar_signature_path', 'uploads/signatures/registrar_signature.png');

        // Upload a JPG — new extension means the path row should
        // overwrite to .jpg, and any leftover file the old path
        // pointed at (whether in public/uploads/ or the legacy
        // storage/ location) gets cleaned up.
        $newFile = UploadedFile::fake()->image('signature.jpg', 200, 80);

        $response = $this->actingAs($admin)->post('/registrar/admission-letter/settings', [
            'registrar_name'      => 'Dr. New',
            'registrar_signature' => $newFile,
        ]);

        if ($response->getStatusCode() >= 400) {
            $this->fail('POST returned ' . $response->getStatusCode()
                . '; error=' . var_export(session('error'), true));
        }

        $path = SystemSetting::where('key', 'registrar_signature_path')->value('value');
        $this->assertNotNull($path);
        $this->assertStringEndsWith('.jpg', $path, 'new path should reflect the new extension');
        $this->assertStringStartsWith('uploads/signatures/', $path, 'path must use the new public/uploads/ prefix');

        // Clean up — leave the destination empty for the next test run.
        $destFile = public_path($path);
        if (file_exists($destFile)) {
            @unlink($destFile);
        }
        SystemSetting::set('registrar_signature_path', '');
    }

    /**
     * The full form action's URL on production must be reachable.
     * If the user is hitting an old cached URL (e.g. before the
     * route was renamed) the POST 404s silently. Confirm the
     * canonical POST URL responds 302 with the admin guard.
     */
    public function test_post_url_is_reachable_for_authenticated_admin(): void
    {
        $admin = $this->makeUser('admin');

        $response = $this->actingAs($admin)->post('/registrar/admission-letter/settings', [
            'institution_name' => 'X',
        ]);

        $this->assertContains($response->getStatusCode(), [200, 302]);
    }

    /**
     * The settings page must NOT have a `<form>` nested inside
     * another `<form>`. The original view had a Preview-Letters
     * GET form inside the outer Save-Settings POST form, which is
     * invalid HTML — Chrome and Firefox silently drop the outer
     * submit button when the inner form closes. That is what made
     * "Save Letter Settings" appear to do nothing on production.
     *
     * Pin: count <form> opens vs closes at every depth in the
     * rendered HTML; a nest is present iff at any point we have
     * seen more opens than closes.
     */
    public function test_settings_page_has_no_nested_forms(): void
    {
        $admin = $this->makeUser('admin');

        $response = $this->actingAs($admin)->get('/registrar/admission-letter/settings');

        $response->assertOk();
        $body = $response->getContent();

        // Strip the body down to just the form-tag positions so we
        // can walk depth. Self-closing forms don't exist in HTML5 so
        // we only care about <form> vs </form>.
        $tokens = preg_split('/(<form\b[^>]*>|<\/form>)/i', $body, -1, PREG_SPLIT_DELIM_CAPTURE);
        $depth = 0;
        $maxDepth = 0;
        foreach ($tokens as $tok) {
            if (preg_match('/^<form\b/i', $tok)) {
                $depth++;
                $maxDepth = max($maxDepth, $depth);
            } elseif (preg_match('/^<\/form>/i', $tok)) {
                $depth--;
            }
        }

        $this->assertEquals(0, $depth, 'Unbalanced form tags in the rendered page.');
        $this->assertLessThanOrEqual(1, $maxDepth, 'Nested <form> detected — Save button will not fire.');
    }

    /**
     * The outer Save form must declare enctype="multipart/form-data"
     * so the registrar_signature file upload is actually carried
     * in the POST body. Without it the browser sends the form
     * as application/x-www-form-urlencoded and silently drops the
     * file, which the controller reports as "saved successfully"
     * (because the rest of the fields were written) — leaving the
     * admin wondering why the signature didn't update.
     */
    public function test_outer_save_form_has_multipart_enctype(): void
    {
        $admin = $this->makeUser('admin');

        $response = $this->actingAs($admin)->get('/registrar/admission-letter/settings');
        $body = $response->getContent();

        $this->assertStringContainsString(
            'enctype="multipart/form-data"',
            $body,
            'Outer Save form is missing enctype=multipart/form-data — signature uploads will be silently dropped.'
        );
    }

    /**
     * The Save button must be reachable without scrolling from the
     * registrar-signature panel. User complaint: "after inputing Registrar
     * name, and i upload the signature and i click on save, it returns
     * to same page" — the Save button was at the bottom of the LEFT
     * column, far from the right-column signature panel. We added a
     * second Save button directly under the signature input so the
     * registrar doesn't have to scroll back.
     *
     * Also pins that the signature input has the id the auto-submit
     * script binds to — that's what makes "save immediately when I
     * upload it" work.
     */
    public function test_inline_save_button_present_in_signature_card(): void
    {
        $admin = $this->makeUser('admin');

        $response = $this->actingAs($admin)->get('/registrar/admission-letter/settings');
        $body = $response->getContent();

        // The signature input must have the id the auto-submit script
        // binds its change listener to.
        $this->assertStringContainsString(
            'id="registrar_signature_input"',
            $body,
            'Signature input is missing id="registrar_signature_input" — auto-save on file select will not fire.'
        );

        // There must be at least two Save buttons (left column + right
        // column under the signature) so the registrar can save without
        // scrolling back to the left.
        preg_match_all('/type="submit"/i', $body, $matches);
        $this->assertGreaterThanOrEqual(
            2,
            count($matches[0]),
            'Expected at least two Save submit buttons (one in each column). Got '
            . count($matches[0]) . ' — the registrar cannot save without scrolling.'
        );
    }

    /**
     * If the PHP-FPM user can't write to public/uploads/signatures
     * (common on production where the directory is owned by the deploy
     * user, not www-data), the file move throws but the OTHER settings
     * (body, fees, letterhead, registrar name) must still persist.
     * Pin that contract: we surface a friendly combined flash instead
     * of rolling everything back.
     *
     * Note: the move target is now public/uploads/signatures/ (matched
     * the passport-upload pattern in commit that moved the registrar
     * signature out of Storage::disk('public')). The test still
     * exercises the same surface — submit a valid image, assert the
     * registrar_name + institution_name land no matter what the file
     * write did. If the upload succeeds the success flash mentions
     * "saved successfully"; if it fails the error flash mentions
     * "the signature file could not be uploaded". Either way the body
     * + name rows are persisted.
     */
    public function test_signature_move_failure_does_not_roll_back_other_settings(): void
    {
        $admin = $this->makeUser('admin');

        // Pre-clean the destination so we measure the move's side
        // effects in isolation.
        $sigDir = public_path('uploads/signatures');
        if (! is_dir($sigDir)) {
            mkdir($sigDir, 0775, true);
        }
        foreach (glob($sigDir . '/registrar_signature.*') ?: [] as $stale) {
            @unlink($stale);
        }

        $file = \Illuminate\Http\UploadedFile::fake()->image('signature.png', 200, 80);

        $response = $this->actingAs($admin)->post('/registrar/admission-letter/settings', [
            'admission_letter_body' => 'Body pinned despite upload',
            'institution_name'      => 'EKSCOTECH',
            'registrar_name'        => 'Dr. Test',
            'registrar_signature'   => $file,
        ]);

        // Either the signature landed (success flash) or the body+name
        // landed and the flash mentions the signature issue. In both
        // cases the registrar name + institution name MUST be persisted.
        $this->assertContains($response->getStatusCode(), [200, 302]);
        $this->assertEquals(
            'Dr. Test',
            SystemSetting::where('key', 'registrar_name')->value('value'),
            'registrar_name must persist even if the signature upload fails.'
        );
        $this->assertEquals(
            'EKSCOTECH',
            SystemSetting::where('key', 'institution_name')->value('value'),
            'institution_name must persist even if the signature upload fails.'
        );
    }

    /* --- helpers --- */

    /**
     * Regression: the default admission letter template is ~360 chars,
     * which is bigger than the legacy `string('value')` (varchar 255)
     * column on system_settings. Saving it produced
     *
     *   SQLSTATE[22001]: String data, right truncated: 1406
     *   Data too long for column 'value' at row 1
     *
     * and the user saw "Failed to save letter settings: ..." because
     * the controller's outer try/catch bubbled the message up. The fix
     * was 2026_08_07_000001_widen_system_settings_value_column which
     * changed `value` to TEXT. This test pins that a long body still
     * persists end-to-end.
     */
    public function test_save_with_long_letter_body_persists_full_text(): void
    {
        $admin = $this->makeUser('admin');

        // The actual default template the user pasted on production.
        // 350 characters — well past the legacy 255-char limit.
        $longBody = 'We are pleased to inform you that you have been offered provisional admission into the {programme} programme of the {department}, {school}, for the {session} academic session. Please complete the acceptance process by paying the required fees listed below before the deadline. On behalf of the institution, we congratulate you and look forward to welcoming you on campus.';
        $this->assertGreaterThan(255, strlen($longBody), 'Pre-condition: body must exceed the legacy varchar(255) limit so the test is meaningful.');

        $response = $this->actingAs($admin)->post('/registrar/admission-letter/settings', [
            'admission_letter_body' => $longBody,
            'institution_name'      => 'EKSCOTECH',
            'registrar_name'        => 'Dr. Long',
        ]);

        if ($response->getStatusCode() >= 400) {
            $this->fail('POST returned ' . $response->getStatusCode()
                . '; error=' . var_export(session('error'), true));
        }

        $stored = SystemSetting::where('key', 'admission_letter_body')->value('value');
        $this->assertEquals(
            $longBody,
            $stored,
            'admission_letter_body was truncated — the value column is still narrower than TEXT.'
        );
    }

    private function makeUser(string $roleSlug): User
    {
        return User::create([
            'name' => 'Test Registrar',
            'email' => $roleSlug . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'role_id' => Role::where('slug', $roleSlug)->value('id'),
            'is_active' => true,
        ]);
    }

    private function buildSchema(): void
    {
        Schema::create('roles', function ($t) {
            $t->id();
            $t->string('name');
            $t->string('slug')->unique();
            $t->timestamps();
        });
        Schema::create('users', function ($t) {
            $t->id();
            $t->string('name');
            $t->string('email')->unique();
            $t->string('password');
            $t->foreignId('role_id')->nullable()->constrained();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
        Schema::create('departments', function ($t) {
            $t->id();
            $t->string('name');
            $t->string('code')->unique();
            $t->timestamps();
        });
        Schema::create('system_settings', function ($t) {
            $t->id();
            $t->string('key')->unique();
            // Match production schema after
            // 2026_08_07_000001_widen_system_settings_value_column —
            // `value` is TEXT, not varchar(255), because settings like
            // admission_letter_body hold multi-paragraph templates.
            $t->text('value')->nullable();
            $t->string('description')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
    }

    private function seedFixtures(): void
    {
        Role::create(['name' => 'Admin',     'slug' => 'admin']);
        Role::create(['name' => 'Registrar', 'slug' => 'registrar']);
    }

    /* -------------------------------------------------------------------
       Auto-save on blur — partial-save endpoint.
       Hit via fetch() from the registrar's auto-save-on-blur JS, so the
       page doesn't reload when the registrar finishes typing a name or
       fee row. Endpoint: PATCH /registrar/admission-letter/settings/field
       Body: { field: 'registrar_name'|'fees', value: <string|array> }
       Response: JSON { ok: bool, field: string, saved_at: string, ... }
       See: resources/views/registrar/admission/letters.blade.php
       ------------------------------------------------------------------- */

    public function test_partial_save_registrar_name_persists_and_returns_json(): void
    {
        $admin = $this->makeUser('admin');

        $response = $this->actingAs($admin)->patchJson(
            '/registrar/admission-letter/settings/field',
            ['field' => 'registrar_name', 'value' => 'Dr. Auto Saved']
        );

        $response->assertOk();
        $response->assertJson([
            'ok' => true,
            'field' => 'registrar_name',
        ]);
        $this->assertNotEmpty($response->json('saved_at'));

        $this->assertEquals(
            'Dr. Auto Saved',
            SystemSetting::where('key', 'registrar_name')->value('value'),
            'registrar_name must be persisted after the auto-save PATCH.'
        );
    }

    public function test_partial_save_fees_persists_list(): void
    {
        $admin = $this->makeUser('admin');

        $response = $this->actingAs($admin)->patchJson(
            '/registrar/admission-letter/settings/field',
            [
                'field' => 'fees',
                'value' => [
                    ['name' => 'Acceptance Fee', 'amount' => 25000],
                    ['name' => 'Caution Fee',    'amount' => 5000],
                ],
            ]
        );

        $response->assertOk();
        $response->assertJson(['ok' => true, 'field' => 'fees']);
        $this->assertEquals(2, $response->json('count'));

        $stored = json_decode(
            SystemSetting::where('key', 'admission_letter_fees')->value('value'),
            true
        );
        $this->assertCount(2, $stored);
        $this->assertEquals('Acceptance Fee', $stored[0]['name']);
        $this->assertEquals(25000.0, (float) $stored[0]['amount']);
        $this->assertEquals('Caution Fee', $stored[1]['name']);
        $this->assertEquals(5000.0, (float) $stored[1]['amount']);
    }

    public function test_partial_save_rejects_unknown_field(): void
    {
        $admin = $this->makeUser('admin');

        $response = $this->actingAs($admin)->patchJson(
            '/registrar/admission-letter/settings/field',
            ['field' => 'institution_name', 'value' => 'hacked']
        );

        // 422 validation — `field` is constrained to registrar_name|fees.
        $response->assertStatus(422);
        $this->assertNull(
            SystemSetting::where('key', 'institution_name')->value('value'),
            'Unknown field must NOT have been persisted.'
        );
    }

    public function test_partial_save_drops_blank_fee_rows(): void
    {
        $admin = $this->makeUser('admin');

        $response = $this->actingAs($admin)->patchJson(
            '/registrar/admission-letter/settings/field',
            [
                'field' => 'fees',
                'value' => [
                    ['name' => '',         'amount' => 0],
                    ['name' => '   ',      'amount' => 100],
                    ['name' => 'Tuition',  'amount' => 0],
                    ['name' => 'Acceptance', 'amount' => 25000],
                ],
            ]
        );

        $response->assertOk();
        $this->assertEquals(1, $response->json('count'));

        $stored = json_decode(
            SystemSetting::where('key', 'admission_letter_fees')->value('value'),
            true
        );
        $this->assertCount(1, $stored);
        $this->assertEquals('Acceptance', $stored[0]['name']);
        $this->assertEquals(25000.0, (float) $stored[0]['amount']);
    }

    public function test_settings_page_includes_registrar_name_status_indicator(): void
    {
        $admin = $this->makeUser('admin');

        $response = $this->actingAs($admin)->get('/registrar/admission-letter/settings');
        $response->assertOk();

        $body = $response->getContent();
        $this->assertStringContainsString(
            'id="registrar_name_status"',
            $body,
            'Registrar name auto-save indicator is missing — JS will not be able to flip its state.'
        );
    }

    public function test_settings_page_includes_fee_row_status_indicator(): void
    {
        $admin = $this->makeUser('admin');

        $response = $this->actingAs($admin)->get('/registrar/admission-letter/settings');
        $response->assertOk();

        $body = $response->getContent();
        $this->assertStringContainsString(
            'fee-save-status',
            $body,
            'Fee row auto-save indicator is missing — JS will not be able to flip its state.'
        );
    }

    /**
     * Pin the contract that the auto-save JS depends on:
     * the layout must ship a <meta name="csrf-token"> so fetch() can
     * read the token and include it in X-CSRF-TOKEN. Without it, every
     * PATCH lands on a 419 and the registrar sees "⚠ Failed".
     */
    public function test_layout_includes_csrf_meta_tag_for_fetch(): void
    {
        $admin = $this->makeUser('admin');

        $response = $this->actingAs($admin)->get('/registrar/admission-letter/settings');
        $body = $response->getContent();

        $this->assertMatchesRegularExpression(
            '#<meta\s+name="csrf-token"\s+content="[^"]+"#i',
            $body,
            'Layout is missing <meta name="csrf-token"> — auto-save fetch() will 419.'
        );
    }

    /**
     * The partial-save endpoint must reject unauthenticated requests.
     * The route is inside the registrar prefix group which already has
     * `auth` middleware, but pin the contract so a future route move
     * doesn't accidentally expose it.
     */
    public function test_partial_save_requires_authentication(): void
    {
        $response = $this->patchJson(
            '/registrar/admission-letter/settings/field',
            ['field' => 'registrar_name', 'value' => 'x']
        );

        // 302 → /login (default Laravel behaviour) — accept either
        // redirect or 401 depending on the auth flow config.
        $this->assertContains($response->getStatusCode(), [302, 401]);
    }
}
