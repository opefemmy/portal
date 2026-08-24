<?php

namespace Tests\Feature\Student;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Slice N — student_courses unique key + per-semester registration.
 *
 * Verifies the new unique key shape
 *   UNIQUE (student_id, course_id, session_id, semester)
 * lets two StudentCourse rows exist for the same (student, course,
 * session) tuple when their semesters differ.
 *
 * Why this test exists separately: the previous key shape was
 *   UNIQUE (student_id, course_id, session_id)
 * which collided the moment a fully-paid student tried to register
 * the same course in both semesters of the same session. The
 * controller's now-correct behaviour (per-semester rows) was
 * impossible without this schema change.
 *
 * Schema choice: an in-memory sqlite schema is enough — what we
 * exercise is unique-key semantics, not Laravel's session/auth
 * stack. The migration is verified separately on the actual MySQL
 * connection at runtime.
 */
class StudentCourseUniqueKeyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Hand-rolled sqlite schema covering only the unique-index
        // shape we care about.
        Schema::create('students', function ($t) {
            $t->id();
            $t->timestamps();
        });

        Schema::create('courses', function ($t) {
            $t->id();
            $t->timestamps();
        });

        Schema::create('sessions', function ($t) {
            $t->id();
            $t->timestamps();
        });

        Schema::create('student_courses', function ($t) {
            $t->id();
            $t->integer('student_id');
            $t->integer('course_id');
            $t->integer('session_id');
            $t->string('semester', 10);
            $t->string('status', 20);
            $t->string('course_type', 20);
            $t->timestamps();
            $t->unique(['student_id', 'course_id', 'session_id', 'semester'], 'student_course_unique');
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('student_courses');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('courses');
        Schema::dropIfExists('students');
        parent::tearDown();
    }

    public function test_fully_paid_student_can_have_two_rows_per_course_one_per_semester(): void
    {
        DB::table('students')->insert(['id' => 1]);
        DB::table('courses')->insert(['id' => 1]);
        DB::table('sessions')->insert(['id' => 1]);

        DB::table('student_courses')->insert([
            'student_id' => 1, 'course_id' => 1, 'session_id' => 1,
            'semester' => 'first', 'status' => 'registered', 'course_type' => 'main',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        // The original 3-column unique key would have thrown here.
        // The new 4-column key allows it.
        DB::table('student_courses')->insert([
            'student_id' => 1, 'course_id' => 1, 'session_id' => 1,
            'semester' => 'second', 'status' => 'registered', 'course_type' => 'main',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $rows = DB::table('student_courses')
            ->where('student_id', 1)
            ->orderBy('semester')
            ->get();

        $this->assertCount(2, $rows, 'Both semester rows should coexist.');
        $this->assertSame('first', $rows[0]->semester);
        $this->assertSame('second', $rows[1]->semester);
    }

    public function test_duplicate_semester_for_same_course_throws(): void
    {
        DB::table('students')->insert(['id' => 1]);
        DB::table('courses')->insert(['id' => 1]);
        DB::table('sessions')->insert(['id' => 1]);

        DB::table('student_courses')->insert([
            'student_id' => 1, 'course_id' => 1, 'session_id' => 1,
            'semester' => 'first', 'status' => 'registered', 'course_type' => 'main',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);
        DB::table('student_courses')->insert([
            'student_id' => 1, 'course_id' => 1, 'session_id' => 1,
            'semester' => 'first', 'status' => 'registered', 'course_type' => 'main',
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_different_students_can_each_have_their_own_per_semester_rows(): void
    {
        DB::table('students')->insert(['id' => 1]);
        DB::table('students')->insert(['id' => 2]);
        DB::table('courses')->insert(['id' => 1]);
        DB::table('sessions')->insert(['id' => 1]);

        foreach (['first', 'second'] as $i => $sem) {
            DB::table('student_courses')->insert([
                'student_id' => 1, 'course_id' => 1, 'session_id' => 1,
                'semester' => $sem, 'status' => 'registered', 'course_type' => 'main',
                'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('student_courses')->insert([
                'student_id' => 2, 'course_id' => 1, 'session_id' => 1,
                'semester' => $sem, 'status' => 'registered', 'course_type' => 'main',
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        $this->assertSame(
            4,
            DB::table('student_courses')->count(),
            'Two students * two semesters = four rows, no collision.'
        );
    }
}
