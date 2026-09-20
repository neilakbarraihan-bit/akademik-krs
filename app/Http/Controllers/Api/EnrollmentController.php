<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Enrollment;
use App\Models\Student;
use App\Models\Course;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;

class EnrollmentController extends Controller
{
    public function index(Request $request)
    {
        set_time_limit(300);

        try {
            $query = Enrollment::with(['student', 'course']);
            $isSearching = false;

            // 1. Live Search Standar
            if ($search = $request->input('search')) {
                $isSearching = true;
                $studentIds = Student::where('nim', 'like', "{$search}%")
                    ->orWhere('name', 'like', "{$search}%")
                    ->pluck('id');

                $courseIds = Course::where('code', 'like', "{$search}%")
                    ->pluck('id');

                if ($studentIds->isEmpty() && $courseIds->isEmpty()) {
                    $query->whereRaw('1 = 0');
                } else {
                    $query->where(function ($q) use ($studentIds, $courseIds) {
                        if ($studentIds->isNotEmpty()) {
                            $q->orWhereIn('student_id', $studentIds);
                        }
                        if ($courseIds->isNotEmpty()) {
                            $q->orWhereIn('course_id', $courseIds);
                        }
                    });
                }
            }

            // 2. Quick Filter: Status
            if ($status = $request->input('status')) {
                $isSearching = true;
                $query->where('status', $status);
            }

            // 3. Quick Filter: Semester
            if ($semester = $request->input('semester')) {
                $isSearching = true;
                $query->where('semester', $semester);
            }

            // 4. ADVANCED FILTER (TS-09)
            if ($request->has('advanced_filters') && is_array($request->input('advanced_filters'))) {
                $filters = $request->input('advanced_filters');
                if (count($filters) > 0) {
                    $isSearching = true;
                    $joinedStudents = false;
                    $joinedCourses = false;

                    // Pastikan join dilakukan pada query utama terlebih dahulu jika ada filter relasi
                    foreach ($filters as $filter) {
                        $col = $filter['column'] ?? null;
                        if (($col === 'nim' || $col === 'name') && !$joinedStudents) {
                            $query->join('students', 'enrollments.student_id', '=', 'students.id');
                            $joinedStudents = true;
                        }
                        if (($col === 'course_code' || $col === 'course_name') && !$joinedCourses) {
                            $query->join('courses', 'enrollments.course_id', '=', 'courses.id');
                            $joinedCourses = true;
                        }
                    }

                    $query->where(function ($q) use ($filters) {
                        foreach ($filters as $index => $filter) {
                            $column = $filter['column'] ?? null;
                            $operator = $filter['operator'] ?? 'contains';
                            $value = $filter['value'] ?? null;
                            $value2 = $filter['value2'] ?? null;
                            $logic = strtoupper($filter['logic'] ?? 'AND');

                            if (!$column) continue;

                            $dbColumn = $column;
                            if ($column === 'nim' || $column === 'name') {
                                $dbColumn = "students.{$column}";
                            } elseif ($column === 'course_code') {
                                $dbColumn = 'courses.code';
                            } elseif ($column === 'course_name') {
                                $dbColumn = 'courses.name';
                            } else {
                                $dbColumn = "enrollments.{$column}";
                            }

                            $method = ($index === 0 || $logic === 'AND') ? 'where' : 'orWhere';

                            switch ($operator) {
                                case 'contains':
                                    $q->$method($dbColumn, 'like', "%{$value}%");
                                    break;
                                case 'equal':
                                    $q->$method($dbColumn, '=', $value);
                                    break;
                                case 'not_equal':
                                    $q->$method($dbColumn, '!=', $value);
                                    break;
                                case 'between':
                                    if ($method === 'orWhere') {
                                        $q->orWhereBetween($dbColumn, [$value, $value2]);
                                    } else {
                                        $q->whereBetween($dbColumn, [$value, $value2]);
                                    }
                                    break;
                                case 'greater_than':
                                    $q->$method($dbColumn, '>', $value);
                                    break;
                                case 'less_than':
                                    $q->$method($dbColumn, '<', $value);
                                    break;
                            }
                        }
                    });
                    $query->select('enrollments.*');
                }
            }

            // 5. ADVANCED ORDER & SORTING (TS-10)
            $sortBy = $request->input('sort_by', 'id');
            $sortOrder = $request->input('sort_order', 'desc');

            if ($sortBy === 'nim' || $sortBy === 'name') {
                if (!collect($query->getQuery()->joins)->contains('table', 'students')) {
                    $query->join('students', 'enrollments.student_id', '=', 'students.id');
                }
                $query->orderBy("students.{$sortBy}", $sortOrder)
                    ->select('enrollments.*');
            } elseif ($sortBy === 'course_code' || $sortBy === 'course_name') {
                if (!collect($query->getQuery()->joins)->contains('table', 'courses')) {
                    $query->join('courses', 'enrollments.course_id', '=', 'courses.id');
                }
                $query->orderBy($sortBy === 'course_code' ? 'courses.code' : 'courses.name', $sortOrder)
                    ->select('enrollments.*');
            } else {
                $query->orderBy("enrollments.{$sortBy}", $sortOrder);
            }

            $perPage = $request->input('per_page', 25);

            if ($isSearching || $sortBy !== 'id' || $sortOrder !== 'desc') {
                $enrollments = $query->paginate($perPage);
                $totalData = $enrollments->total();
                $currentPage = $enrollments->currentPage();
                $lastPage = $enrollments->lastPage();
            } else {
                $enrollments = $query->simplePaginate($perPage);
                $totalData = 5000000;
                $currentPage = $enrollments->currentPage();
                $lastPage = $enrollments->hasMorePages() ? $currentPage + 1 : $currentPage;
            }

            $statusCounts = Cache::remember('enrollment_status_counts', 60, function () {
                return Enrollment::select('status', DB::raw('count(*) as total'))
                    ->groupBy('status')
                    ->pluck('total', 'status');
            });

            return response()->json([
                'data' => $enrollments->items(),
                'current_page' => $currentPage,
                'last_page' => $lastPage,
                'total' => $totalData,
                'global_total' => $statusCounts->sum() ?: 5000000,
                'draft' => $statusCounts['DRAFT'] ?? 0,
                'submitted' => $statusCounts['SUBMITTED'] ?? 0,
                'approved' => $statusCounts['APPROVED'] ?? 0,
                'rejected' => $statusCounts['REJECTED'] ?? 0
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'student_nim' => 'required|string|min:8|max:12|regex:/^[0-9]+$/',
            'student_name' => 'required|string|min:1|max:100',
            'student_email' => 'required|email',
            'course_code' => 'required|string|max:5',
            'course_name' => 'required|string|min:1|max:120',
            'course_credits' => 'required|integer|min:1|max:6',
            'academic_year' => 'required|string|regex:/^\d{4}\/\d{4}$/',
            'semester' => 'required|in:GANJIL,GENAP',
            'status' => 'required|in:DRAFT,SUBMITTED,APPROVED,REJECTED',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        list($startYear, $endYear) = explode('/', $request->academic_year);
        if ((int) $endYear < (int) $startYear) {
            return response()->json([
                'errors' => ['academic_year' => ['Tahun selesai tidak boleh lebih kecil dari tahun mulai.']]
            ], 422);
        }

        DB::beginTransaction();
        try {
            $emailOwner = Student::where('email', $request->student_email)->where('nim', '!=', $request->student_nim)->exists();
            if ($emailOwner) {
                return response()->json([
                    'errors' => ['student_email' => ['Email ini sudah digunakan oleh mahasiswa lain.']]
                ], 422);
            }

            $student = Student::firstOrCreate(
                ['nim' => $request->student_nim],
                ['name' => $request->student_name, 'email' => $request->student_email]
            );

            $course = Course::firstOrCreate(
                ['code' => $request->course_code],
                ['name' => $request->course_name, 'credits' => $request->course_credits]
            );

            $exists = Enrollment::where([
                'student_id' => $student->id,
                'course_id' => $course->id,
                'academic_year' => $request->academic_year,
                'semester' => $request->semester,
            ])->exists();

            if ($exists) {
                throw new \Exception('Mahasiswa ini sudah mengambil mata kuliah tersebut pada semester yang sama.');
            }

            $enrollment = Enrollment::create([
                'student_id' => $student->id,
                'course_id' => $course->id,
                'academic_year' => $request->academic_year,
                'semester' => $request->semester,
                'status' => $request->status,
            ]);

            DB::commit();
            Cache::forget('enrollment_status_counts');
            $enrollment->load(['student', 'course']);

            return response()->json([
                'message' => 'Data KRS berhasil ditambahkan secara terkoordinasi.',
                'data' => $enrollment
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            $statusCode = str_contains($e->getMessage(), 'sudah mengambil') ? 400 : 500;
            return response()->json(['error' => $e->getMessage()], $statusCode);
        }
    }

    public function update(Request $request, $id)
    {
        $enrollment = Enrollment::find($id);
        if (!$enrollment) {
            return response()->json(['error' => 'Data KRS tidak ditemukan.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'academic_year' => 'sometimes|required|string|regex:/^\d{4}\/\d{4}$/',
            'semester' => 'sometimes|required|in:GANJIL,GENAP',
            'status' => 'sometimes|required|in:DRAFT,SUBMITTED,APPROVED,REJECTED',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if ($request->has('academic_year')) {
            list($startYear, $endYear) = explode('/', $request->academic_year);
            if ((int) $endYear < (int) $startYear) {
                return response()->json([
                    'errors' => ['academic_year' => ['Tahun selesai tidak boleh lebih kecil dari tahun mulai.']]
                ], 422);
            }
        }

        $enrollment->update($request->only(['academic_year', 'semester', 'status']));
        $enrollment->load(['student', 'course']);
        Cache::forget('enrollment_status_counts');

        return response()->json([
            'message' => 'Data KRS berhasil diperbarui.',
            'data' => $enrollment
        ]);
    }

    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $enrollment = Enrollment::findOrFail($id);
            $studentId = $enrollment->student_id;

            $enrollment->delete();

            $remainingEnrollments = Enrollment::where('student_id', $studentId)->count();

            if ($remainingEnrollments === 0) {
                Student::where('id', $studentId)->delete();
            }

            DB::commit();
            Cache::forget('enrollment_status_counts');

            return response()->json([
                'message' => 'Data KRS dan mahasiswa terkait berhasil dihapus.'
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Gagal menghapus data: ' . $e->getMessage()
            ], 500);
        }
    }

    public function export()
    {
        // 1. Matikan batasan waktu dan memori
        set_time_limit(0);
        ini_set('memory_limit', '-1');

        $fileName = 'export_krs_' . date('Y-m-d_H-i-s') . '.csv';

        // 2. Siapkan header untuk streaming download
        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0",
            "X-Accel-Buffering"   => "no" // Penting untuk server web (seperti Nginx) agar tidak menahan buffer
        ];

        return response()->stream(function() {
            $file = fopen('php://output', 'w');
            
            fputcsv($file, ['ID', 'NIM', 'Nama Mahasiswa', 'Kode MK', 'Nama MK', 'Semester', 'Tahun Ajaran', 'Status']);

            $pdo = \Illuminate\Support\Facades\DB::connection()->getPdo();
            
            // 3. PENTING: Matikan buffering bawaan MySQL agar tidak memakan RAM!
            $pdo->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
            
            $sql = "
                SELECT 
                    e.id, 
                    s.nim, 
                    s.name as student_name, 
                    c.code, 
                    c.name as course_name, 
                    e.semester, 
                    e.academic_year, 
                    e.status 
                FROM enrollments e
                LEFT JOIN students s ON e.student_id = s.id
                LEFT JOIN courses c ON e.course_id = c.id
            ";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute();

            $counter = 0;
            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                fputcsv($file, [
                    $row['id'],
                    $row['nim'] ?? '-',
                    $row['student_name'] ?? '-',
                    $row['code'] ?? '-',
                    $row['course_name'] ?? '-',
                    $row['semester'],
                    $row['academic_year'],
                    $row['status']
                ]);

                $counter++;
                // 4. Dorong output ke browser setiap 5.000 baris agar koneksi tidak dianggap mati (Timeout)
                if ($counter % 5000 === 0) {
                    if (ob_get_level() > 0) {
                        ob_flush();
                    }
                    flush();
                }
            }

            fclose($file);
            
            // Kembalikan pengaturan buffer ke awal agar tidak mengganggu kueri lain setelahnya
            $pdo->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
            
        }, 200, $headers);
    }
}