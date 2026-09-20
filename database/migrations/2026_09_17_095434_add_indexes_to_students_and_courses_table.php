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
        Schema::table('students', function (Blueprint $table) {
            $table->index('nim'); // Index untuk pencarian NIM kilat
        });

        Schema::table('courses', function (Blueprint $table) {
            $table->index('code'); // Index untuk pencarian Kode Mata Kuliah kilat
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students_and_courses', function (Blueprint $table) {
            //
        });
    }
};
