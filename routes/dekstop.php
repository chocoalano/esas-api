<?php

use App\Http\Controllers\ApiWeb\AbsensiController;
use App\Http\Controllers\ApiWeb\DashboardHrController;
use App\Http\Controllers\ApiWeb\DocumentationController;
use App\Http\Controllers\ApiWeb\IzinController;
use App\Http\Controllers\ApiWeb\KelengkapanFormController;
use App\Http\Controllers\ApiWeb\AuthController;
use App\Http\Controllers\ApiWeb\CompanyController;
use App\Http\Controllers\ApiWeb\DepartemenController;
use App\Http\Controllers\ApiWeb\LevelController;
use App\Http\Controllers\ApiWeb\PositionController;
use App\Http\Controllers\ApiWeb\PeranController;
use App\Http\Controllers\ApiWeb\JenisIzinController;
use App\Http\Controllers\ApiWeb\JamKerjaController;
use App\Http\Controllers\ApiWeb\PenggunaController;
use App\Http\Controllers\ApiWeb\PengumumanController;
use App\Http\Controllers\ApiWeb\LaporanBugController;
use App\Http\Controllers\ApiWeb\JadwalKerjaController;
use Illuminate\Support\Facades\Route;

Route::get('unauthorized', [AuthController::class, 'unauthorized'])->name('unauthorized');
Route::prefix('web-auth')->group(function () {
    Route::controller(AuthController::class)
        ->group(function () {
            Route::post('login', 'login');
        });
});
Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('web-auth')->group(function () {
        Route::controller(AuthController::class)
            ->group(function () {
                Route::get('logout', 'logout');
                Route::get('permission', 'permission');
                Route::get('profile', 'profile');
                Route::post('profile', 'profile_update');
                Route::get('pemberitahuan', 'pemberitahuan');
                Route::get('pemberitahuan/{id}', 'pemberitahuan_read');
            });
    });
    Route::prefix('web-api')->group(function () {
        Route::controller(KelengkapanFormController::class)
            ->group(function () {
                Route::get('kelengkapan-form/all-company', 'all_company');
                Route::get('kelengkapan-form/all-departement', 'all_departement');
                Route::get('kelengkapan-form/all-position', 'all_position');
                Route::get('kelengkapan-form/all-level', 'all_level');
                Route::get('kelengkapan-form/all-timework', 'all_timework');
                Route::get('kelengkapan-form/all-schedule', 'all_schedule');
                Route::get('kelengkapan-form/all-user', 'all_user');
                Route::get('kelengkapan-form/all-permit', 'all_permit');
                Route::get('kelengkapan-form/all-roles', 'all_roles');
                Route::get('kelengkapan-form/filter-company', 'filter_company');
                Route::get('kelengkapan-form/filter-departement', 'filter_departement');
                Route::get('kelengkapan-form/filter-position', 'filter_position');
                Route::get('kelengkapan-form/filter-level', 'filter_level');
                Route::get('kelengkapan-form/filter-timework', 'filter_timework');
                Route::get('kelengkapan-form/filter-user', 'filter_user');
                Route::get('kelengkapan-form/filter-schedule', 'filter_schedule');
                Route::get('kelengkapan-form/filter-timework', 'filter_timework');
            });
        Route::controller(DashboardHrController::class)
            ->group(function () {
                Route::get('dashboard-hr/akun', 'akun');
                Route::get('dashboard-hr/departemen', 'departemen');
                Route::get('dashboard-hr/posisi', 'posisi');
                Route::get('dashboard-hr/absen', 'absen');
                Route::get('dashboard-hr/absen-telat', 'absen_telat');
                Route::get('dashboard-hr/absen-alpha', 'absen_alpha');
                Route::get('dashboard-hr/absen-chart', 'absen_chart');
                Route::get('dashboard-hr/izin', 'izin');
            });
        Route::get('company/download', [CompanyController::class, 'downloadpdf']);
        Route::get('company/export', [CompanyController::class, 'downloadExcel']);
        Route::get('departemen/download', [DepartemenController::class, 'downloadpdf']);
        Route::get('departemen/export', [DepartemenController::class, 'downloadExcel']);
        Route::get('level/download', [LevelController::class, 'downloadpdf']);
        Route::get('level/export', [LevelController::class, 'downloadExcel']);
        Route::get('posisi/download', [PositionController::class, 'downloadpdf']);
        Route::get('posisi/export', [PositionController::class, 'downloadExcel']);
        Route::get('peran/download', [PeranController::class, 'downloadpdf']);
        Route::get('peran/export', [PeranController::class, 'downloadExcel']);
        Route::get('jenis-izin/download', [JenisIzinController::class, 'downloadpdf']);
        Route::get('jenis-izin/export', [JenisIzinController::class, 'downloadExcel']);
        Route::get('jam-kerja/download', [JamKerjaController::class, 'downloadpdf']);
        Route::get('jam-kerja/export', [JamKerjaController::class, 'downloadExcel']);
        Route::get('pengguna/download', [PenggunaController::class, 'downloadpdf']);
        Route::get('pengguna/export', [PenggunaController::class, 'downloadExcel']);
        Route::get('pengguna/{id}/reset', [PenggunaController::class, 'reset']);
        Route::get('pengguna/{id}/reset/password', [PenggunaController::class, 'reset_password']);
        Route::get('pengumuman/download', [PengumumanController::class, 'downloadpdf']);
        Route::get('pengumuman/export', [PengumumanController::class, 'downloadExcel']);
        Route::get('laporan-bug/download', [LaporanBugController::class, 'downloadpdf']);
        Route::get('laporan-bug/export', [LaporanBugController::class, 'downloadExcel']);
        Route::get('jadwal-kerja/download', [JadwalKerjaController::class, 'downloadpdf']);
        Route::get('jadwal-kerja/export', [JadwalKerjaController::class, 'downloadExcel']);
        Route::get('izin/download', [IzinController::class, 'downloadpdf']);
        Route::get('izin/export', [IzinController::class, 'downloadExcel']);
        Route::get('izin/code-numbers', [IzinController::class, 'codeNumbers']);
        Route::put('izin/{id}/approval', [IzinController::class, 'approval']);
        Route::get('absensi/download', [AbsensiController::class, 'downloadpdf']);
        Route::get('absensi/export', [AbsensiController::class, 'downloadExcel']);
        Route::resources([
            'company' => CompanyController::class,
            'departemen' => DepartemenController::class,
            'level' => LevelController::class,
            'posisi' => PositionController::class,
            'peran' => PeranController::class,
            'jenis-izin' => JenisIzinController::class,
            'jam-kerja' => JamKerjaController::class,
            'pengguna' => PenggunaController::class,
            'pengumuman' => PengumumanController::class,
            'laporan-bug' => LaporanBugController::class,
            'jadwal-kerja' => JadwalKerjaController::class,
            'izin' => IzinController::class,
            'absensi' => AbsensiController::class,
            'dokumentasi' => DocumentationController::class,
        ]);
    });
});
