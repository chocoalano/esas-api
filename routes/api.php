<?php

use App\Http\Controllers\Api\AnnouncementController;
use App\Http\Controllers\Api\AssetsController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\AttendanceDeviceController;
use App\Http\Controllers\Api\BugReportController;
use App\Http\Controllers\Api\FormController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\PermitController;
use App\Http\Controllers\Api\PermitTypeController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/assets/{folder}/{filename}', [AssetsController::class, 'index']);

Route::prefix('attendance-device')->group(function () {
    Route::controller(AttendanceDeviceController::class)
        ->group(function () {
            Route::get('company', 'company');
            Route::get('departement', 'departement');
            Route::get('shift', 'shift');
            Route::post('generate-qr', 'store');
            Route::post('qr-attendance', 'qr_attendance');
            Route::post('face-attendance', 'face_attendance');
            Route::post('cek-nip', 'validate_user');
        });
});

Route::prefix('auth')->group(function () {
    Route::controller(UserController::class)
        ->group(function () {
            Route::post('register', 'register');
            Route::post('login', 'login');
        });
});

Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::controller(UserController::class)
            ->group(function () {
                Route::post('store-device-token', 'get_fcm_token');
                Route::get('profile', 'profile');
                Route::get('setting', 'setting');
                Route::post('set-imei', 'set_imei');
                Route::get('profile-display', 'profile_display');
                Route::post('profile-avatar', 'profile_avatar');
                Route::get('profile-permission', 'profile_permission');
                Route::post('profile-update', 'profile_update');
                Route::post('profile-update-family', 'profile_update_family');
                Route::post('profile-update-formal-education', 'profile_update_formal_education');
                Route::post('profile-update-informal-education', 'profile_update_informal_education');
                Route::post('profile-update-work-experience', 'profile_update_work_experience');
                Route::post('profile-update-bank', 'profile_update_bank');
                Route::post('profile-update-password', 'profile_update_password');
                Route::get('profile-schedule', 'profile_schedule');
                Route::get('profile-schedule-list', 'profile_schedule_list');
                Route::get('profile-list-time', 'profile_list_time');
                Route::get('profile-current-attendance/{userId}', 'profile_current_attendance');
                Route::post('logout', 'logout');
            });

    });

    Route::prefix('form')->group(function () {
        Route::prefix('auth')->group(function () {
            Route::controller(FormController::class)->group(function () {
                Route::get('office', 'auth_office');
            });
        });

    });

    Route::get('attendance/auth', [AttendanceController::class, 'auth_all']);

    Route::resources([
        // 'roles' => RoleController::class,
        'user' => UserController::class,
        'permit' => PermitController::class,
        'permit-type' => PermitTypeController::class,
        'attendance' => AttendanceController::class,
        'announcement' => AnnouncementController::class,
        'bug-report' => BugReportController::class,
        'notification' => NotificationController::class,
    ]);
});
