<template>
  <div style="padding: 20px; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f4f6f9; min-height: 100vh; position: relative;">
    
    <!-- Notifikasi Toast -->
    <transition name="fade">
      <div v-if="toast.show" :style="{ backgroundColor: toast.type === 'success' ? '#28a745' : '#dc3545' }" style="position: fixed; top: 20px; right: 20px; color: white; padding: 15px 25px; border-radius: 8px; font-weight: bold; box-shadow: 0 4px 12px rgba(0,0,0,0.2); z-index: 10000; display: flex; align-items: center; gap: 10px;">
        <span style="font-size: 18px;">{{ toast.type === 'success' ? '✅' : '❌' }}</span>
        {{ toast.message }}
      </div>
    </transition>

    <!-- Overlay Loading Toga -->
    <div v-if="isSubmitting" style="position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.8); display: flex; flex-direction: column; justify-content: center; align-items: center; z-index: 10000;">
      <div class="toga-loader">🎓</div>
      <div style="color: white; font-size: 20px; font-weight: bold; margin-top: 15px; letter-spacing: 3px; animation: pulse-text 1.5s infinite;">LOADING...</div>
    </div>

    <div style="max-width: 1300px; margin: 0 auto;">
      <!-- Header -->
      <div style="background: white; padding: 20px 25px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
        <div>
          <h2 style="color: #333; margin: 0; font-size: 22px;">KRS</h2>
          <p style="color: #666; margin: 5px 0 0 0; font-size: 13px;">Empowering Academic Excellence at Scale</p>
        </div>
        <div style="display: flex; gap: 10px;">
          <button @click="openAddModal" style="background: #28a745; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; font-weight: bold; font-size: 13px;">
            + Tambah Data KRS
          </button>
          <button @click="exportCsv" style="background: #007bff; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; font-weight: bold; font-size: 13px;">
            Export CSV
          </button>
        </div>
      </div>

      <!-- Statistik (5 Kolom: Total, Draft, Submitted, Approved, Rejected) -->
      <div style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 15px; margin-bottom: 20px;">
        <div style="background: white; padding: 20px; border-radius: 8px; border-left: 4px solid #007bff;">
          <div style="color: #666; font-size: 12px; font-weight: bold;">Total KRS</div>
          <div style="font-size: 22px; font-weight: bold; color: #333; margin-top: 5px;">{{ globalTotal.toLocaleString() }}</div>
        </div>
        <div style="background: white; padding: 20px; border-radius: 8px; border-left: 4px solid #6c757d;">
          <div style="color: #666; font-size: 12px; font-weight: bold;">Draft</div>
          <div style="font-size: 22px; font-weight: bold; color: #333; margin-top: 5px;">{{ draftTotal.toLocaleString() }}</div>
        </div>
        <div style="background: white; padding: 20px; border-radius: 8px; border-left: 4px solid #17a2b8;">
          <div style="color: #666; font-size: 12px; font-weight: bold;">Submitted</div>
          <div style="font-size: 22px; font-weight: bold; color: #333; margin-top: 5px;">{{ submittedTotal.toLocaleString() }}</div>
        </div>
        <div style="background: white; padding: 20px; border-radius: 8px; border-left: 4px solid #28a745;">
          <div style="color: #666; font-size: 12px; font-weight: bold;">Approved</div>
          <div style="font-size: 22px; font-weight: bold; color: #333; margin-top: 5px;">{{ approvedTotal.toLocaleString() }}</div>
        </div>
        <div style="background: white; padding: 20px; border-radius: 8px; border-left: 4px solid #dc3545;">
          <div style="color: #666; font-size: 12px; font-weight: bold;">Rejected</div>
          <div style="font-size: 22px; font-weight: bold; color: #333; margin-top: 5px;">{{ rejectedTotal.toLocaleString() }}</div>
        </div>
      </div>

      <!-- Pencarian dengan Debounce & Tombol Toggle Advanced Filter -->
      <div style="background: white; padding: 15px 20px; border-radius: 8px; margin-bottom: 15px; display: flex; gap: 12px; align-items: center;">
        <input 
          type="text" 
          v-model="search" 
          placeholder="Cari otomatis (Debounced): NIM, Nama, atau Kode MK..." 
          style="flex: 1; padding: 9px 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 13px;"
        />
        <select v-model="selectedStatus" @change="executeSearch" style="padding: 9px 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 13px; background: white;">
          <option value="">Semua Status</option>
          <option value="DRAFT">DRAFT</option>
          <option value="SUBMITTED">SUBMITTED</option>
          <option value="APPROVED">APPROVED</option>
          <option value="REJECTED">REJECTED</option>
        </select>
        <select v-model="selectedSemester" @change="executeSearch" style="padding: 9px 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 13px; background: white;">
          <option value="">Semua Semester</option>
          <option value="GANJIL">GANJIL</option>
          <option value="GENAP">GENAP</option>
        </select>
        <button @click="toggleAdvancedFilterPanel" style="background: #6f42c1; color: white; border: none; padding: 9px 16px; border-radius: 4px; cursor: pointer; font-weight: bold; font-size: 13px; white-space: nowrap;">
          ⚙️ Advanced Filter
        </button>
        <button v-if="hasActiveFilter()" @click="cancelSearch" style="background: #dc3545; color: white; border: none; padding: 9px 16px; border-radius: 4px; cursor: pointer; font-weight: bold; font-size: 13px;">
          ✖ Reset
        </button>
      </div>

      <!-- PANEL ADVANCED FILTER & ADVANCED ORDER (TS-09 & TS-10) -->
      <div v-if="showAdvancedFilter" style="background: white; border: 2px solid #6f42c1; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 4px 12px rgba(111,66,193,0.1);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; border-bottom: 1px solid #eee; padding-bottom: 10px;">
          <h3 style="margin: 0; color: #6f42c1; font-size: 16px;">Advanced Filter & Advanced Order Panel</h3>
          <button @click="showAdvancedFilter = false" style="background: none; border: none; font-size: 16px; cursor: pointer; font-weight: bold;">✕</button>
        </div>

        <div style="margin-bottom: 15px;" v-for="(rule, index) in advancedFilters" :key="index">
          <div style="display: flex; gap: 10px; align-items: center; background: #f8f9fa; padding: 10px; border-radius: 6px;">
            <select v-if="index > 0" v-model="rule.logic" style="padding: 7px; border: 1px solid #ddd; border-radius: 4px; font-weight: bold; font-size: 12px;">
              <option value="AND">AND</option>
              <option value="OR">OR</option>
            </select>
            <span v-else style="font-size: 12px; font-weight: bold; width: 45px; text-align: center;">WHERE</span>

            <select v-model="rule.column" style="flex: 1; padding: 7px; border: 1px solid #ddd; border-radius: 4px; font-size: 12px;">
              <option value="nim">NIM Mahasiswa</option>
              <option value="name">Nama Mahasiswa</option>
              <option value="course_code">Kode Mata Kuliah</option>
              <option value="course_name">Nama Mata Kuliah</option>
              <option value="semester">Semester</option>
              <option value="academic_year">Tahun Ajaran</option>
              <option value="status">Status</option>
            </select>

            <select v-model="rule.operator" style="flex: 1; padding: 7px; border: 1px solid #ddd; border-radius: 4px; font-size: 12px;">
              <option value="contains">Contains (Mengandung)</option>
              <option value="equal">Equal (Sama dengan)</option>
              <option value="not_equal">Not Equal (Tidak sama dengan)</option>
              <option value="between">Between (Di antara rentang)</option>
              <option value="greater_than">Greater Than (&gt;)</option>
              <option value="less_than">Less Than (&lt;)</option>
            </select>

            <input type="text" v-model="rule.value" placeholder="Nilai..." style="flex: 1.5; padding: 7px; border: 1px solid #ddd; border-radius: 4px; font-size: 12px;" />
            
            <input v-if="rule.operator === 'between'" type="text" v-model="rule.value2" placeholder="Nilai akhir..." style="flex: 1.5; padding: 7px; border: 1px solid #ddd; border-radius: 4px; font-size: 12px;" />

            <button @click="removeAdvancedRule(index)" style="background: #dc3545; color: white; border: none; padding: 7px 12px; border-radius: 4px; cursor: pointer; font-size: 12px;">Hapus</button>
          </div>
        </div>

        <div style="display: flex; gap: 10px; margin-top: 15px;">
          <button @click="addAdvancedRule" style="background: #6f42c1; color: white; border: none; padding: 8px 14px; border-radius: 4px; font-size: 12px; cursor: pointer; font-weight: bold;">+ Tambah Aturan Filter</button>
          <button @click="applyAdvancedFilter" style="background: #28a745; color: white; border: none; padding: 8px 16px; border-radius: 4px; font-size: 12px; cursor: pointer; font-weight: bold;">Terapkan Advanced Filter</button>
          <button @click="resetAdvancedFilter" style="background: #6c757d; color: white; border: none; padding: 8px 14px; border-radius: 4px; font-size: 12px; cursor: pointer;">Reset Filter</button>
        </div>
      </div>

      <!-- Indikator Pencarian -->
      <div v-if="hasActiveFilter()" style="margin-bottom: 15px; display: flex; align-items: center; gap: 10px; font-size: 13px;">
        <span style="background: #e8f0fe; color: #1a73e8; padding: 6px 12px; border-radius: 16px; font-weight: bold;">
          Filter Aktif: 
          <span v-if="search">"{{ search }}"</span>
          <span v-if="selectedStatus"> | Status: {{ selectedStatus }}</span>
          <span v-if="selectedSemester"> | Semester: {{ selectedSemester }}</span>
          <span v-if="advancedFilters.length > 0"> | Advanced Filters ({{ advancedFilters.length }} rules)</span>
        </span>
        <span style="color: #666;">
          Menemukan <strong style="color: #333; font-size: 14px;">{{ totalData.toLocaleString() }}</strong> data
        </span>
      </div>

      <!-- Tabel -->
      <div style="background: white; border-radius: 8px; overflow: hidden; margin-bottom: 20px;">
        <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 13px;">
          <thead>
            <tr style="background: #f8f9fa; color: #495057; border-bottom: 2px solid #dee2e6;">
              <th @click="handleSort('nim')" style="padding: 12px 15px; cursor: pointer; user-select: none;">
                NIM <span style="font-size: 10px; color: #007bff;">{{ getSortIcon('nim') }}</span>
              </th>
              <th @click="handleSort('name')" style="padding: 12px 15px; cursor: pointer; user-select: none;">
                NAME <span style="font-size: 10px; color: #007bff;">{{ getSortIcon('name') }}</span>
              </th>
              <th @click="handleSort('course_code')" style="padding: 12px 15px; cursor: pointer; user-select: none;">
                COURSE CODE <span style="font-size: 10px; color: #007bff;">{{ getSortIcon('course_code') }}</span>
              </th>
              <th @click="handleSort('course_name')" style="padding: 12px 15px; cursor: pointer; user-select: none;">
                COURSE NAME <span style="font-size: 10px; color: #007bff;">{{ getSortIcon('course_name') }}</span>
              </th>
              <th @click="handleSort('semester')" style="padding: 12px 15px; cursor: pointer; user-select: none;">
                SEMESTER <span style="font-size: 10px; color: #007bff;">{{ getSortIcon('semester') }}</span>
              </th>
              <th @click="handleSort('academic_year')" style="padding: 12px 15px; cursor: pointer; user-select: none;">
                YEAR <span style="font-size: 10px; color: #007bff;">{{ getSortIcon('academic_year') }}</span>
              </th>
              <th @click="handleSort('status')" style="padding: 12px 15px; cursor: pointer; user-select: none;">
                STATUS <span style="font-size: 10px; color: #007bff;">{{ getSortIcon('status') }}</span>
              </th>
              <th style="padding: 12px 15px; text-align: center;">ACTIONS</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading">
              <td colspan="8" style="text-align: center; padding: 50px; color: #666; font-style: italic;">Memuat data...</td>
            </tr>
            <tr v-else-if="enrollments.length === 0">
              <td colspan="8" style="text-align: center; padding: 50px; color: #666;">Data tidak ditemukan.</td>
            </tr>
            <template v-else>
              <tr v-for="item in enrollments" :key="item.id" style="border-bottom: 1px solid #f2f2f2;">
                <td style="padding: 12px 15px; color: #333;">{{ item.student ? item.student.nim : '-' }}</td>
                <td style="padding: 12px 15px; font-weight: 500; color: #333;">{{ item.student ? item.student.name : '-' }}</td>
                <td style="padding: 12px 15px; color: #555;">{{ item.course ? item.course.code : '-' }}</td>
                <td style="padding: 12px 15px; color: #555;">{{ item.course ? item.course.name : '-' }}</td>
                <td style="padding: 12px 15px; color: #555;">{{ item.semester }}</td>
                <td style="padding: 12px 15px; color: #555;">{{ item.academic_year }}</td>
                <td style="padding: 12px 15px;">
                  <span :style="getStatusStyle(item.status)" style="padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: bold; display: inline-block;">
                    {{ item.status }}
                  </span>
                </td>
                <td style="padding: 12px 15px; text-align: center;">
                  <button @click="openEditModal(item)" style="background: none; border: none; cursor: pointer; color: #007bff; font-size: 14px; margin-right: 8px;" title="Edit">✏️</button>
                  <button @click="deleteEnrollment(item.id)" style="background: none; border: none; cursor: pointer; color: #dc3545; font-size: 14px;" title="Hapus">🗑️</button>
                </td>
              </tr>
            </template>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div style="display: flex; justify-content: space-between; align-items: center; background: white; padding: 15px 20px; border-radius: 8px;">
        <span style="color: #666; font-size: 13px;">{{ paginationInfo }}</span>
        <div>
          <button @click="changePage(currentPage - 1)" :disabled="currentPage <= 1" style="padding: 6px 14px; margin-right: 5px; background: #f8f9fa; border: 1px solid #ddd; border-radius: 4px; cursor: pointer; font-size: 13px;">Sebelumnya</button>
          <span style="margin: 0 10px; font-size: 13px; font-weight: bold;">Halaman {{ currentPage }} dari {{ lastPage }}</span>
          <button @click="changePage(currentPage + 1)" :disabled="currentPage >= lastPage" style="padding: 6px 14px; margin-left: 5px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 13px;">Selanjutnya</button>
        </div>
      </div>
    </div>

    <!-- MODAL FORM CRUD -->
    <div v-if="showModal" style="position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.5); display: flex; justify-content: center; align-items: center; z-index: 999;">
      <div style="background: white; width: 650px; max-height: 90vh; overflow-y: auto; border-radius: 8px; padding: 25px;">
        <h3 style="margin-top: 0; border-bottom: 1px solid #eee; padding-bottom: 10px; margin-bottom: 20px;">
          {{ isEditing ? 'Edit Data KRS' : 'Tambah Data KRS' }}
        </h3>
        
        <div v-if="clientError" style="background: #fce8e6; border-left: 4px solid #ea4335; padding: 10px; margin-bottom: 15px; color: #c5221f; font-size: 12px;">
          ⚠️ {{ clientError }}
        </div>

        <form @submit.prevent="validateAndSubmit">
          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
            <div style="grid-column: span 2; font-weight: bold; font-size: 13px; color: #007bff;">Data Mahasiswa</div>
            
            <div>
              <label style="display: block; font-size: 12px; margin-bottom: 3px;">NIM *</label>
              <input type="text" v-model="form.student_nim" :disabled="isEditing" maxlength="10" placeholder="Contoh: 2026051001" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;" />
              <small style="color: #666; font-size: 10px;">Wajib angka, 8-12 digit.</small>
              <div v-if="formErrors.student_nim" style="color: #dc3545; font-size: 11px; margin-top: 2px;">{{ formErrors.student_nim[0] }}</div>
            </div>

            <div>
              <label style="display: block; font-size: 12px; margin-bottom: 3px;">Nama Mahasiswa *</label>
              <input type="text" v-model="form.student_name" :disabled="isEditing" placeholder="Nama lengkap" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;" />
              <div v-if="formErrors.student_name" style="color: #dc3545; font-size: 11px; margin-top: 2px;">{{ formErrors.student_name[0] }}</div>
            </div>

            <div v-if="!isEditing" style="grid-column: span 2;">
              <label style="display: block; font-size: 12px; margin-bottom: 3px;">Email Mahasiswa *</label>
              <input type="email" v-model="form.student_email" placeholder="nama@email.com" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;" />
              <div v-if="formErrors.student_email" style="color: #dc3545; font-size: 11px; margin-top: 2px;">{{ formErrors.student_email[0] }}</div>
            </div>

            <div style="grid-column: span 2; font-weight: bold; font-size: 13px; color: #007bff; margin-top: 10px;">Data Mata Kuliah</div>
            
            <div>
              <label style="display: block; font-size: 12px; margin-bottom: 3px;">Kode MK *</label>
              <input type="text" v-model="form.course_code" :disabled="isEditing" maxlength="5" placeholder="Contoh: CB089" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; text-transform: uppercase;" />
              <div v-if="formErrors.course_code" style="color: #dc3545; font-size: 11px; margin-top: 2px;">{{ formErrors.course_code[0] }}</div>
            </div>

            <div>
              <label style="display: block; font-size: 12px; margin-bottom: 3px;">Nama MK *</label>
              <input type="text" v-model="form.course_name" :disabled="isEditing" placeholder="Nama Mata Kuliah" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;" />
              <div v-if="formErrors.course_name" style="color: #dc3545; font-size: 11px; margin-top: 2px;">{{ formErrors.course_name[0] }}</div>
            </div>

            <div v-if="!isEditing">
              <label style="display: block; font-size: 12px; margin-bottom: 3px;">SKS *</label>
              <input type="number" v-model="form.course_credits" min="1" max="6" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;" />
              <div v-if="formErrors.course_credits" style="color: #dc3545; font-size: 11px; margin-top: 2px;">{{ formErrors.course_credits[0] }}</div>
            </div>

            <div style="grid-column: span 2; font-weight: bold; font-size: 13px; color: #007bff; margin-top: 10px;">Detail KRS</div>
            
            <div :style="isEditing ? 'grid-column: span 2;' : ''">
              <label style="display: block; font-size: 12px; margin-bottom: 3px;">Tahun Ajaran *</label>
              <div style="display: flex; gap: 8px; align-items: center;">
                <input type="text" v-model="form.startYear" maxlength="4" placeholder="2025" required style="flex: 1; padding: 8px; border: 1px solid #ddd; border-radius: 4px;" />
                <span style="font-weight: bold; color: #555;">/</span>
                <input type="text" v-model="form.endYear" maxlength="4" placeholder="2026" required style="flex: 1; padding: 8px; border: 1px solid #ddd; border-radius: 4px;" />
              </div>
              <div v-if="formErrors.academic_year" style="color: #dc3545; font-size: 11px; margin-top: 2px;">{{ formErrors.academic_year[0] }}</div>
            </div>

            <div>
              <label style="display: block; font-size: 12px; margin-bottom: 3px;">Semester *</label>
              <select v-model="form.semester" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; background: white;">
                <option value="GANJIL">GANJIL</option>
                <option value="GENAP">GENAP</option>
              </select>
            </div>

            <div>
              <label style="display: block; font-size: 12px; margin-bottom: 3px;">Status *</label>
              <select v-model="form.status" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; background: white;">
                <option value="DRAFT">DRAFT</option>
                <option value="SUBMITTED">SUBMITTED</option>
                <option value="APPROVED">APPROVED</option>
                <option value="REJECTED">REJECTED</option>
              </select>
            </div>
          </div>

          <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 25px;">
            <button type="button" @click="closeModal" style="padding: 8px 16px; background: #f8f9fa; border: 1px solid #ddd; border-radius: 4px;">Batal</button>
            <button type="submit" style="padding: 8px 16px; background: #007bff; color: white; border: none; border-radius: 4px;">Simpan Data</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted, watch } from 'vue';
import axios from 'axios';

export default {
  setup() {
    const enrollments = ref([]);
    const search = ref('');
    const selectedStatus = ref('');
    const selectedSemester = ref('');
    
    // State Advanced Filter (TS-09)
    const showAdvancedFilter = ref(false);
    const advancedFilters = ref([]);

    // State Sorting (TS-06 & TS-10)
    const sortBy = ref('id');
    const sortOrder = ref('desc');

    const currentPage = ref(1);
    const lastPage = ref(1);
    const totalData = ref(0);
    const globalTotal = ref(0); 
    
    const draftTotal = ref(0);
    const submittedTotal = ref(0), approvedTotal = ref(0), rejectedTotal = ref(0);
    const loading = ref(false);

    const paginationInfo = computed(() => {
      if (totalData.value === 0) return 'Tidak ada data ditemukan';
      const perPage = 25;
      const start = (currentPage.value - 1) * perPage + 1;
      const end = start + enrollments.value.length - 1;
      return `Menampilkan data ke-${start.toLocaleString()} sampai ${end.toLocaleString()} dari total ${totalData.value.toLocaleString()} baris`;
    });

    const showModal = ref(false), isEditing = ref(false), isSubmitting = ref(false), currentId = ref(null), formErrors = ref({});
    const clientError = ref(''); 
    
    const form = ref({ 
      student_nim: '', student_name: '', student_email: '', course_code: '', course_name: '', course_credits: 3, startYear: '2025', endYear: '2026', semester: 'GANJIL', status: 'DRAFT' 
    });

    const toast = ref({ show: false, message: '', type: 'success' });

    const showToast = (message, type = 'success') => {
      toast.value = { show: true, message, type };
      setTimeout(() => { toast.value.show = false; }, 3500);
    };

    const fetchEnrollments = async (page = 1) => {
      loading.value = true;
      try {
        const response = await axios.get(`/api/enrollments`, { 
          params: { 
            page: page, 
            search: search.value, 
            status: selectedStatus.value, 
            semester: selectedSemester.value,
            advanced_filters: advancedFilters.value,
            sort_by: sortBy.value,
            sort_order: sortOrder.value,
            per_page: 25 
          } 
        });
        enrollments.value = response.data.data;
        currentPage.value = response.data.current_page;
        lastPage.value = response.data.last_page;
        
        totalData.value = response.data.total; 
        globalTotal.value = response.data.global_total || 5000000; 
        
        draftTotal.value = response.data.draft || 0;
        submittedTotal.value = response.data.submitted || 0;
        approvedTotal.value = response.data.approved || 0;
        rejectedTotal.value = response.data.rejected || 0;
      } catch (error) { console.error(error); } finally { loading.value = false; }
    };

    const hasActiveFilter = () => {
      if (search.value) return true;
      if (selectedStatus.value) return true;
      if (selectedSemester.value) return true;
      if (advancedFilters.value.length > 0) return true;
      return false;
    };

    // Advanced Filter Methods
    const toggleAdvancedFilterPanel = () => {
      showAdvancedFilter.value = !showAdvancedFilter.value;
      if (advancedFilters.value.length === 0) {
        addAdvancedRule();
      }
    };

    const addAdvancedRule = () => {
      advancedFilters.value.push({
        column: 'nim',
        operator: 'contains',
        value: '',
        value2: '',
        logic: 'AND'
      });
    };

    const removeAdvancedRule = (index) => {
      advancedFilters.value.splice(index, 1);
    };

    const applyAdvancedFilter = () => {
      currentPage.value = 1;
      fetchEnrollments(1);
    };

    const resetAdvancedFilter = () => {
      advancedFilters.value = [];
      showAdvancedFilter.value = false;
      currentPage.value = 1;
      fetchEnrollments(1);
    };

    let searchTimeout = null;
    watch(search, () => {
      clearTimeout(searchTimeout);
      searchTimeout = setTimeout(() => {
        currentPage.value = 1;
        fetchEnrollments(1);
      }, 400);
    });

    const executeSearch = () => { currentPage.value = 1; fetchEnrollments(1); };
    
    const cancelSearch = () => {
      search.value = '';
      selectedStatus.value = '';
      selectedSemester.value = '';
      advancedFilters.value = [];
      sortBy.value = 'id';
      sortOrder.value = 'desc';
      currentPage.value = 1;
      fetchEnrollments(1);
    };

    const handleSort = (column) => {
      if (sortBy.value === column) {
        sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc';
      } else {
        sortBy.value = column;
        sortOrder.value = 'asc';
      }
      fetchEnrollments(currentPage.value);
    };

    const getSortIcon = (column) => {
      if (sortBy.value !== column) return '↕';
      if (sortOrder.value === 'asc') return '▲';
      return '▼';
    };

    const changePage = (page) => { 
      if (page >= 1 && page <= lastPage.value) {
        fetchEnrollments(page);
      } 
    };

    const openAddModal = () => {
      isEditing.value = false; currentId.value = null; formErrors.value = {}; clientError.value = '';
      form.value = { student_nim: '', student_name: '', student_email: '', course_code: '', course_name: '', course_credits: 3, startYear: '2025', endYear: '2026', semester: 'GANJIL', status: 'DRAFT' };
      showModal.value = true;
    };

    const openEditModal = (item) => {
      isEditing.value = true; currentId.value = item.id; formErrors.value = {}; clientError.value = '';
      let years = item.academic_year ? item.academic_year.split('/') : ['2025', '2026'];
      form.value = { 
        student_nim: item.student?.nim || '', 
        student_name: item.student?.name || '', 
        student_email: item.student?.email || '', 
        course_code: item.course?.code || '', 
        course_name: item.course?.name || '', 
        course_credits: item.course?.credits || '', 
        startYear: years[0] || '2025',
        endYear: years[1] || '2026',
        semester: item.semester, 
        status: item.status 
      };
      showModal.value = true;
    };

    const closeModal = () => { showModal.value = false; };

    const validateAndSubmit = async () => {
      clientError.value = '';
      
      const isNimNumeric = /^\d+$/.test(form.value.student_nim);
      if (!isNimNumeric) {
        clientError.value = 'NIM wajib diisi dengan angka.';
        return;
      }
      
      const nimLength = form.value.student_nim.length;
      if (nimLength < 8) {
        clientError.value = 'Panjang NIM minimal 8 digit.';
        return;
      }
      if (nimLength > 12) {
        clientError.value = 'Panjang NIM maksimal 12 digit.';
        return;
      }

      const isStartYearValid = /^\d{4}$/.test(form.value.startYear);
      if (!isStartYearValid) {
        clientError.value = 'Format tahun mulai harus berupa 4 digit angka (contoh: 2025).';
        return;
      }

      const isEndYearValid = /^\d{4}$/.test(form.value.endYear);
      if (!isEndYearValid) {
        clientError.value = 'Format tahun selesai harus berupa 4 digit angka (contoh: 2026).';
        return;
      }

      if (parseInt(form.value.endYear) < parseInt(form.value.startYear)) {
        clientError.value = 'Tahun selesai tidak boleh lebih kecil dari tahun mulai.';
        return;
      }

      await submitForm();
    };

    const submitForm = async () => {
      isSubmitting.value = true; 
      formErrors.value = {};
      
      const payload = {
        student_nim: form.value.student_nim,
        student_name: form.value.student_name,
        student_email: form.value.student_email,
        course_code: form.value.course_code,
        course_name: form.value.course_name,
        course_credits: form.value.course_credits,
        academic_year: form.value.startYear + '/' + form.value.endYear,
        semester: form.value.semester,
        status: form.value.status
      };

      try {
        if (isEditing.value) {
          await axios.put(`/api/enrollments/${currentId.value}`, payload);
          showToast('Data KRS berhasil diperbarui!', 'success');
        } else {
          await axios.post(`/api/enrollments`, payload);
          showToast('Data KRS berhasil ditambahkan!', 'success');
        }
        closeModal(); 
        await fetchEnrollments(currentPage.value);
      } catch (error) {
        if (error.response?.status === 422) {
          formErrors.value = error.response.data.errors;
          showToast('Gagal! Periksa kembali isian formulir.', 'error');
        } else if (error.response?.data?.error) {
          formErrors.value = { server: [error.response.data.error] };
          showToast('Gagal! ' + error.response.data.error, 'error');
        } else {
          showToast('Terjadi kesalahan pada server.', 'error');
        }
      } finally { 
        isSubmitting.value = false; 
      }
    };

    const deleteEnrollment = async (id) => {
      if (window.confirm("Apakah Anda yakin ingin menghapus data KRS ini?")) {
        isSubmitting.value = true; 
        try { 
          await axios.delete(`/api/enrollments/${id}`); 
          showToast('Data KRS berhasil dihapus!', 'success');
          await fetchEnrollments(currentPage.value); 
        } catch (error) { 
          showToast('Terjadi kesalahan saat menghapus data.', 'error');
        } finally {
          isSubmitting.value = false;
        }
      }
    };

    const exportCsv = () => { window.location.href = '/api/enrollments/export'; };
    
    const getStatusStyle = (status) => {
      if (status === 'APPROVED') return 'background: #e6f4ea; color: #137333;';
      if (status === 'REJECTED') return 'background: #fce8e6; color: #c5221f;';
      if (status === 'SUBMITTED') return 'background: #e8f0fe; color: #1a73e8;';
      return 'background: #f1f3f4; color: #5f6368;';
    };

    onMounted(() => { fetchEnrollments(); });

    return {
      enrollments, search, selectedStatus, selectedSemester, currentPage, lastPage, totalData, globalTotal, 
      draftTotal, submittedTotal, approvedTotal, rejectedTotal, loading, paginationInfo,
      showAdvancedFilter, advancedFilters, toggleAdvancedFilterPanel, addAdvancedRule, removeAdvancedRule, applyAdvancedFilter, resetAdvancedFilter,
      fetchEnrollments, executeSearch, cancelSearch, changePage, exportCsv, getStatusStyle, showModal, isEditing, isSubmitting, form, formErrors, clientError, openAddModal, openEditModal, closeModal, validateAndSubmit, deleteEnrollment, toast,
      handleSort, getSortIcon, hasActiveFilter
    };
  }
};
</script>

<style scoped>
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.3s, transform 0.3s;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
  transform: translateY(-20px);
}
.toga-loader {
  font-size: 70px;
  animation: bounce-toga 0.8s infinite alternate ease-in-out;
}
@keyframes bounce-toga {
  0% { transform: translateY(0); }
  100% { transform: translateY(-25px); }
}
@keyframes pulse-text {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.4; }
}
</style>