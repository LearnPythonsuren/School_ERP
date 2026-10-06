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
use App\Http\Middleware\EnsureCentralUser;
use App\Http\Middleware\EnsureLicenseActive;
use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\IdentifySchool;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

// Brute-force guard for both login screens: 10 tries a minute per email + IP
// (per email, not just IP, so a whole school behind one NAT is not locked out).
RateLimiter::for('login', fn (Request $r) => Limit::perMinute(10)->by(strtolower((string) $r->input('email')).'|'.$r->ip()));

// Uptime probe for hosting platforms / load balancers.
Route::get('/health', fn () => response()->json(['status' => 'ok', 'app' => 'Scholar ERP', 'time' => now()->toIso8601String()]));

/*
|==========================================================================
| CENTRAL CONTROL PLANE — the product owner (Chenthur Info Tech)
|==========================================================================
| Issue license keys, provision & suspend schools. Super-admin only.
*/
Route::prefix('central')->group(function () {
    Route::post('/login', [CentralAuth::class, 'login'])->middleware('throttle:login');

    Route::middleware(['auth:sanctum', EnsureCentralUser::class])->group(function () {
        Route::get('/me',               [CentralAuth::class, 'me']);
        Route::post('/logout',          [CentralAuth::class, 'logout']);
        Route::post('/change-password', [CentralAuth::class, 'changePassword']);
        Route::get('/overview',         [TenantController::class, 'overview']);

        // Licensing
        Route::get('/licenses',                     [LicenseController::class, 'index']);
        Route::post('/licenses',                    [LicenseController::class, 'store']);
        Route::get('/licenses/{license}',           [LicenseController::class, 'show']);
        Route::put('/licenses/{license}',           [LicenseController::class, 'update']); // renew / change plan
        Route::post('/licenses/{license}/suspend',  [LicenseController::class, 'suspend']);
        Route::post('/licenses/{license}/activate', [LicenseController::class, 'activate']);
        Route::post('/licenses/{license}/revoke',   [LicenseController::class, 'revoke']);

        // Schools
        Route::get('/schools',                    [TenantController::class, 'index']);
        Route::post('/schools',                   [TenantController::class, 'store']);
        Route::get('/schools/{tenant}',           [TenantController::class, 'show']);
        Route::post('/schools/{tenant}/suspend',  [TenantController::class, 'suspend']);
        Route::post('/schools/{tenant}/activate', [TenantController::class, 'activate']);
    });
});

/*
|==========================================================================
| TENANT API — one school (X-Tenant header). License enforced per request.
|==========================================================================
| Role matrix (super-admin always passes):
|   admin       everything in the school
|   accountant  fees, reports, student list
|   teacher     attendance, exams, library, students (read), messages
|   driver      own bus GPS pings, vehicle list
|   student / parent   timetable, announcements, settings (read)
*/
Route::middleware([IdentifySchool::class, EnsureLicenseActive::class])->group(function () {

    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');

    Route::middleware('auth:sanctum')->group(function () {
        // ---- Every signed-in user ----
        Route::get('/me',               [AuthController::class, 'me']);
        Route::post('/logout',          [AuthController::class, 'logout']);
        Route::post('/logout-all',      [AuthController::class, 'logoutAll']);
        Route::post('/change-password', [AuthController::class, 'changePassword']);

        Route::get('/settings',  [SettingsController::class, 'index']);
        Route::get('/license',   [SettingsController::class, 'license']);
        Route::apiResource('timetable', TimetableController::class)->parameters(['timetable' => 'slot'])->only(['index', 'show']);
        Route::apiResource('announcements', CommunicationController::class)->only(['index', 'show']);

        // ---- Staff who work with students ----
        Route::middleware(EnsureRole::class.':admin,teacher,accountant')->group(function () {
            Route::get('/dashboard', [DashboardController::class, 'index']);
            Route::apiResource('students', StudentController::class)->only(['index', 'show']);
        });

        Route::middleware(EnsureRole::class.':admin,teacher')->group(function () {
            Route::apiResource('staff', StaffController::class)->parameters(['staff' => 'staff'])->only(['index', 'show']);
            Route::apiResource('admissions', AdmissionController::class)->only(['index', 'show']);
            Route::apiResource('rooms', HostelController::class)->parameters(['rooms' => 'room'])->only(['index', 'show']);

            Route::get('/attendance',  [AttendanceController::class, 'index']);
            Route::post('/attendance', [AttendanceController::class, 'store']);

            Route::apiResource('exams', ExamController::class)->parameters(['exams' => 'exam']);

            Route::apiResource('books', LibraryController::class)->parameters(['books' => 'book']);
            Route::get('/book-issues',                 [LibraryController::class, 'issues']);
            Route::post('/books/{book}/issue',         [LibraryController::class, 'issue']);
            Route::post('/book-issues/{issue}/return', [LibraryController::class, 'returnBook']);

            Route::post('/announcements',      [CommunicationController::class, 'store']);
            Route::post('/announcements/send', [CommunicationController::class, 'send']);
        });

        Route::middleware(EnsureRole::class.':admin,teacher,driver')->group(function () {
            Route::apiResource('vehicles', TransportController::class)->parameters(['vehicles' => 'vehicle'])->only(['index', 'show']);
        });
        Route::post('/vehicles/{vehicle}/location', [TransportController::class, 'updateLocation'])
            ->middleware(EnsureRole::class.':admin,driver');

        // ---- Finance ----
        Route::middleware(EnsureRole::class.':admin,accountant')->group(function () {
            Route::post('/fees/bulk', [FeeController::class, 'bulk']);
            Route::apiResource('fees', FeeController::class)->parameters(['fees' => 'invoice']);
            Route::post('/fees/{invoice}/collect', [FeeController::class, 'collect']);
            Route::get('/fees/{invoice}/receipt',  [FeeController::class, 'receipt']);

            Route::get('/reports/summary',        [ReportController::class, 'summary']);
            Route::get('/reports/export/{type}',  [ReportController::class, 'export']);
        });

        // ---- School admin only ----
        Route::middleware(EnsureRole::class.':admin')->group(function () {
            Route::get('/roles', [UserController::class, 'roles']);
            Route::apiResource('users', UserController::class);

            Route::apiResource('students', StudentController::class)->only(['store', 'update', 'destroy']);
            Route::apiResource('staff', StaffController::class)->parameters(['staff' => 'staff'])->only(['store', 'update', 'destroy']);

            Route::apiResource('admissions', AdmissionController::class)->only(['store', 'update', 'destroy']);
            Route::post('/admissions/{admission}/approve', [AdmissionController::class, 'approve']);
            Route::post('/admissions/{admission}/reject',  [AdmissionController::class, 'reject']);
            Route::post('/admissions/{admission}/enroll',  [AdmissionController::class, 'enroll']);

            Route::apiResource('timetable', TimetableController::class)->parameters(['timetable' => 'slot'])->only(['store', 'update', 'destroy']);

            Route::apiResource('vehicles', TransportController::class)->parameters(['vehicles' => 'vehicle'])->only(['store', 'update', 'destroy']);

            Route::apiResource('rooms', HostelController::class)->parameters(['rooms' => 'room'])->only(['store', 'update', 'destroy']);
            Route::post('/rooms/{room}/allot',  [HostelController::class, 'allot']);
            Route::post('/rooms/{room}/vacate', [HostelController::class, 'vacate']);

            Route::apiResource('announcements', CommunicationController::class)->only(['update', 'destroy']);

            Route::put('/settings', [SettingsController::class, 'update']);
        });
    });
});
