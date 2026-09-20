<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('course_id')->constrained('courses')->onDelete('cascade');
            $table->string('academic_year', 9);
            $table->enum('semester', ['GANJIL', 'GENAP']);
            $table->enum('status', ['DRAFT', 'SUBMITTED', 'APPROVED', 'REJECTED']);
            $table->timestamps();

            $table->index(['status', 'semester']);
            $table->index('academic_year');
            $table->unique(['student_id', 'course_id', 'academic_year', 'semester'], 'enrollment_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enrollments_tabl');
    }
};
