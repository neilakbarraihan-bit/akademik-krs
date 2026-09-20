# KRS Management System - Technical Test

Single-page CRUD application untuk mengelola data Kartu Rencana Studi (KRS) dengan kapabilitas menangani 5.000.000 baris data secara responsif.

## 🚀 Tech Stack
- **Backend:** Laravel (PHP)
- **Frontend:** [Isi dengan Vue.js / React / Blade / dsb]
- **Database:** MySQL / PostgreSQL

## 🗄️ Database Schema & Relasi
Aplikasi ini menggunakan 3 tabel utama yang saling berelasi (Atomic Transaction diterapkan saat Create):
1. `students`: `id`, `nim`, `name`, `email`
2. `courses`: `id`, `code`, `name`, `credits`
3. `enrollments`: `id`, `student_id` (FK), `course_id` (FK), `academic_year`, `semester`, `status`

## 🛠️ Cara Instalasi & Menjalankan di Lokal (Setup)

1. **Clone Repository**
   ```bash
   git clone [https://github.com/username/repo-krs.git](https://github.com/username/repo-krs.git)
   cd repo-krs