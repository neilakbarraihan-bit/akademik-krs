<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Matikan Query Log agar RAM tidak jebol
        DB::connection()->disableQueryLog();

        $this->command->info('Menghapus data lama...');
        Schema::disableForeignKeyConstraints();
        DB::table('enrollments')->truncate();
        DB::table('courses')->truncate();
        DB::table('students')->truncate();
        Schema::enableForeignKeyConstraints();

        // KUNCI OPTIMASI: Gunakan 1 string waktu statis, hindari pemanggilan now() jutaan kali di dalam loop
        $timestamp = '2026-09-17 15:33:19'; 

        $this->command->info('Membuat 100 Mata Kuliah...');
        $courses = [];
        for ($i = 1; $i <= 100; $i++) {
            $courses[] = [
                'code' => 'IF' . str_pad($i, 3, '0', STR_PAD_LEFT),
                'name' => 'Mata Kuliah Komputer ' . $i,
                'credits' => rand(2, 4),
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];
        }
        DB::table('courses')->insert($courses);

        $this->command->info('Membuat 50.000 Mahasiswa...');
        for ($chunk = 0; $chunk < 10; $chunk++) {
            $students = [];
            for ($i = 1; $i <= 5000; $i++) {
                $id = ($chunk * 5000) + $i;
                $students[] = [
                    'nim' => '2026' . str_pad($id, 6, '0', STR_PAD_LEFT),
                    'name' => 'Mahasiswa ' . $id,
                    'email' => 'mhs' . $id . '@kampus.ac.id',
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];
            }
            DB::table('students')->insert($students);
            unset($students); // Paksa bersihkan RAM
        }

        $this->command->info('Mulai menyuntikkan 5 JUTA data Enrollments (Proses aman dari batas RAM)...');
        
        $semesters = ['GANJIL', 'GENAP'];
        $statuses = ['DRAFT', 'SUBMITTED', 'APPROVED', 'REJECTED'];
        $academic_years = ['2023/2024', '2024/2025', '2025/2026'];

        // Kurangi beban memori menjadi 20 mahasiswa (2000 baris data) per kali eksekusi
        for ($chunk = 1; $chunk <= 50000; $chunk += 20) { 
            $enrollments = [];
            
            for ($student_id = $chunk; $student_id < $chunk + 20; $student_id++) {
                for ($course_id = 1; $course_id <= 100; $course_id++) {
                    $enrollments[] = [
                        'student_id' => $student_id,
                        'course_id' => $course_id,
                        'academic_year' => $academic_years[array_rand($academic_years)],
                        'semester' => $semesters[array_rand($semesters)],
                        'status' => $statuses[array_rand($statuses)],
                        'created_at' => $timestamp,
                        'updated_at' => $timestamp,
                    ];
                }
            }
            
            DB::table('enrollments')->insertOrIgnore($enrollments);
            unset($enrollments); // Paksa bersihkan RAM setelah query dikirim

            if (($chunk + 19) % 5000 == 0) {
                $progress = ($chunk + 19) * 100;
                $this->command->info("Progres: $progress baris data berhasil disuntikkan...");
            }
        }

        $this->command->info('SELESAI! 5 Juta data sukses masuk ke database.');
    }
}