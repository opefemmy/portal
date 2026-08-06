<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Regression net for the "dynamic payment catalogue" refactor.
 *
 * After the refactor, no Blade template should look up a PaymentType by
 * a hardcoded code literal ('APP_FORM' / 'ACCEPT_FEE' / 'SCHOOL_FEE').
 * All lookups go through PaymentType::findByPurpose() or
 * PaymentType::findByCode() so an admin can rename or duplicate a row
 * without code changes.
 *
 * If a future contributor re-introduces such a lookup the test fails
 * with the exact file + line + the offending needle.
 */
class DynamicCatalogueNoHardcodedViewTest extends TestCase
{
    /**
     * For each hardcoded code literal we expect zero matches. The grep
     * here is intentionally narrow:
     *   - only inside actual code (skip <?php comments and HTML <!-- -->)
     *   - only inside where-in-Sql / where('code', '...') lookups, OR
     *     inside a PaymentType::where / PaymentType::findByCode call
     *   - not in HTML body, not in option values for a purpose select
     *
     * @dataProvider hardcodedCodeProvider
     */
    public function test_views_do_not_lookup_payment_types_by_hardcoded_code(string $code): void
    {
        $offenders = [];
        $viewDir   = base_path('resources/views');

        $rii = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($viewDir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($rii as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }
            $path = realpath($file->getPathname());
            if ($path === realpath(__FILE__)) {
                continue;
            }

            $contents = file_get_contents($path);
            // Strip Blade/HTML comments — they are documentation, not
            // code that runs, so they shouldn't fail the test.
            $contents = preg_replace('/<!--.*?-->/s', '', $contents);
            $contents = preg_replace('/\{\{--.*?--\}\}/s', '', $contents);

            // Match the literal in a where(..., code-literal) lookup or
            // a findByCode() call — those are the actual lookup sites.
            $pattern = '/'
                . 'where\s*\(\s*[\'"]code[\'"]\s*,\s*[\'"]' . preg_quote($code, '/') . '[\'"]'
                . '|findByCode\s*\(\s*[\'"]' . preg_quote($code, '/') . '[\'"]'
                . '|where\s*\(\s*[\'"]' . preg_quote($code, '/') . '[\'"]\s*\)'
                . '/i';

            if (preg_match_all($pattern, $contents, $matches, PREG_OFFSET_CAPTURE)) {
                foreach ($matches[0] as $match) {
                    $offset = $match[1];
                    $line   = substr_count(substr($contents, 0, $offset), "\n") + 1;
                    $offenders[] = "$path:$line → {$match[0]}";
                }
            }
        }

        $this->assertEmpty(
            $offenders,
            "These Blade files still look up a PaymentType by the legacy '{$code}' code literal. "
                . "Switch to \$paymentType->display_label, \$paymentType->name, "
                . "or PaymentType::findByPurpose(\$purpose) instead. Offenders: "
                . implode(', ', $offenders)
        );
    }

    public static function hardcodedCodeProvider(): array
    {
        return [
            ['APP_FORM'],
            ['ACCEPT_FEE'],
            ['SCHOOL_FEE'],
        ];
    }
}
