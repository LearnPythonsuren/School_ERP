<?php

use App\Http\Controllers\Api\AdmissionController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CommunicationController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ExamController;
use App\Http\Controllers\Api\FeeController;
use App\Http\Controllers\Api\HostelController;
use App\Http\Controllers\Api\LibraryController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\SettingsController;
use App\Http\Controllers\Api\StaffController;
use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\Api\TimetableController;
use App\Http\Controllers\Api\TransportController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Central\AuthController as CentralAuth;
use App\Http\Controllers\Central\LicenseController;
use App\Http\Controllers\Central\TenantController;
use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByRequestData;

/*
|==========================================================================
| CENTRAL CONTROL PLANE — the product owner (Chenthur Info Tech)
|==========================================================================
| Issue license keys, provision & suspend schools. Super-admin only.
*/
Route::prefix('central')->group(function () {
    Route::post('/login', [CentralAuth::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me',      [CentralAuth::class, 'me']);
        Route::post('/logout', [CentralAuth::class, 'logout']);

        // Licensing
        Route::get('/licenses',                 [LicenseController::class, 'index']);
        Route::post('/licenses',                [LicenseController::class, 'store']);
        Route::get('/licenses/{license}',       [LicenseController::class, 'show']);
        Route::post('/licenses/{license}/suspend',  [LicenseController::class, 'suspend']);
        Route::post('/licenses/{license}/activate', [LicenseController::class, 'activate']);
        Route::post('/licenses/{license}/revoke',   [LicenseController::class, 'revoke']);

        // Schools
        Route::get('/schools',                  [TenantController::class, 'index']);
        Route::post('/schools',                 [TenantController::class, 'store']);
        Route::post('/schools/{tenant}/suspend',  [TenantController::class, 'suspend']);
        Route::post('/schools/{tenant}/activate', [TenantController::class, 'activate']);
    });
});

/*
|==========================================================================
| TENANT API — one school (X-Tenant header). License-gated at login.
|==========================================================================
*/
Route::middleware([InitializeTenancyByRequestData::class])->group(function () {

    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        // Account / auth
        Route::get('/me',               [AuthController::class, 'me']);
        Route::post('/logout',          [AuthController::class, 'logout']);
        Route::post('/logout-all',      [AuthController::class, 'logoutAll']);
        Route::post('/change-password', [AuthController::class, 'changePassword']);

        Route::get('/dashboard', [DashboardController::class, 'index']);

        // School admin: role-wise access management
        Route::get('/roles', [UserController::class, 'roles']);
        Route::apiResource('users', UserController::class);

        // Modules (full CRUD + special actions)
        Route::apiResource('students', StudentController::class);

        Route::apiResource('fees', FeeController::class)->parameters(['fees' => 'invoice']);
        Route::post('/fees/{invoice}/collect', [FeeController::class, 'collect']);

        Route::apiResource('staff', StaffController::class)->parameters(['staff' => 'staff']);
        Route::apiResource('exams', ExamController::class)->parameters(['exams' => 'exam']);

        Route::get('/attendance',  [AttendanceController::class, 'index']);
        Route::post('/attendance', [AttendanceController::class, 'store']);

        Route::apiResource('admissions', AdmissionController::class);
        Route::post('/admissions/{admission}/approve', [AdmissionController::class, 'approve']);
        Route::post('/admissions/{admission}/reject',  [AdmissionController::class, 'reject']);
        Route::post('/admissions/{admission}/enroll',  [AdmissionController::class, 'enroll']);

        Route::apiResource('timetable', TimetableController::class)->parameters(['timetable' => 'slot']);

        Route::apiResource('books', LibraryController::class)->parameters(['books' => 'book']);
        Route::post('/books/{book}/issue',         [LibraryController::class, 'issue']);
        Route::post('/book-issues/{issue}/return', [LibraryController::class, 'returnBook']);

        Route::apiResource('vehicles', TransportController::class)->parameters(['vehicles' => 'vehicle']);
        Route::post('/vehicles/{vehicle}/location', [TransportController::class, 'updateLocation']);

        Route::apiResource('rooms', HostelController::class)->parameters(['rooms' => 'room']);
        Route::post('/rooms/{room}/allot',  [HostelController::class, 'allot']);
        Route::post('/rooms/{room}/vacate', [HostelController::class, 'vacate']);

        Route::apiResource('announcements', CommunicationController::class)->parameters(['announcements' => 'announcement']);
        Route::post('/announcements/send', [CommunicationController::class, 'send']);

        // Reports & settings
        Route::get('/reports/summary', [ReportController::class, 'summary']);
        Route::get('/settings',        [SettingsController::class, 'index']);
        Route::put('/settings',        [SettingsController::class, 'update']);
        Route::get('/license',         [SettingsController::class, 'license']);
    });
});
