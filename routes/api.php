<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\EnrollmentController;

// Rute untuk fitur Read (Pagination, Sort, Filter, Search)
Route::get('/enrollments', [EnrollmentController::class, 'index']);

// Rute untuk fitur Create (Insert 3 tabel dengan DB Transaction)
Route::post('/enrollments', [EnrollmentController::class, 'store']);

// Rute untuk fitur Update & Delete
Route::put('/enrollments/{id}', [EnrollmentController::class, 'update']);
Route::delete('/enrollments/{id}', [EnrollmentController::class, 'destroy']);

// Rute untuk Export CSV 5 Juta Data
Route::get('/enrollments/export', [EnrollmentController::class, 'export']);