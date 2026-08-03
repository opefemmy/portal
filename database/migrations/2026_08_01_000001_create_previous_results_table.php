<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Stores result rows imported from previous institutions or older
     * sessions inside the same institution. Used to build a transcript
     * for students currently in 200L/300L and beyond who already have
     * academic history that pre-dates this portal.
     *
     * Distinct from `results`: each row here is standalone (no
     * student_course_id link), carries its own session/semester/level,
     * and is meant to be displayed on the transcript alongside rows
     * captured live during the current session.
     */
    public function up(): void
    {
        Schema::create('previous_results', function (Blueprint $table) {
            $table->id();

            // Identity — who this row belongs to.
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');

            // Course identity. We store course_code + title free-form so we
            // can ingest rows from a different institution's curriculum
            // without forcing the admin to map every legacy code into our
            // courses table.
            $table->string('course_code', 50);
            $table->string('course_title')->nullable();
            $table->unsignedTinyInteger('units')->default(0);

            // Academic period the row belongs to.
            $table->string('session_name', 50);
            $table->enum('semester', ['first', 'second'])->default('first');
            $table->unsignedTinyInteger('level')->nullable();

            // Scores — broken out so the admin can preserve CA/test/exam
            // breakdown if they have it. Total is required; the rest are
            // optional.
            $table->decimal('ca', 5, 2)->nullable();
            $table->decimal('test', 5, 2)->nullable();
            $table->decimal('assignment', 5, 2)->nullable();
            $table->decimal('exam', 5, 2)->nullable();
            $table->decimal('total_score', 5, 2);

            // Grade + grade-point (computed from total_score if admin
            // uploaded raw scores but left grade blank — see PreviousResult
            // observer).
            $table->string('grade', 5)->nullable();
            $table->decimal('grade_point', 3, 1)->nullable();
            $table->text('remarks')->nullable();

            // Where did this row come from?
            $table->string('source_institution', 255)->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('uploaded_at')->useCurrent();

            $table->timestamps();

            // Index for transcript queries — pulls all rows for a student
            // in session/semester order, which is exactly what a transcript
            // renderer needs.
            $table->index(['student_id', 'session_name', 'semester', 'level'], 'pr_student_session_semester_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('previous_results');
    }
};