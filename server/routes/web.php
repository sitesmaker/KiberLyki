<?php

use App\Http\Controllers\AdminController as A;
use App\Http\Middleware\AdminAccess;
use Illuminate\Support\Facades\Route;

Route::get('robots.txt', [\App\Http\Controllers\SeoController::class, 'robots']);
Route::get('sitemap.xml', [\App\Http\Controllers\SeoController::class, 'sitemap']);
Route::view('/admin/login', 'admin.login')->name('login');
Route::post('/admin/login', [A::class, 'login'])->middleware('throttle:10,1');
Route::prefix('admin')->middleware(AdminAccess::class)->group(function () {
    Route::get('/', [A::class, 'home']);
    Route::view('account', 'admin.account');
    Route::post('account/password', [A::class, 'ownPassword']);
    Route::get('seo', [\App\Http\Controllers\SeoAdminController::class, 'edit']);
    Route::post('seo', [\App\Http\Controllers\SeoAdminController::class, 'save']);
    Route::post('seo/settings', [\App\Http\Controllers\SeoAdminController::class, 'settings']);
    Route::post('logout', [A::class, 'logout']);
    Route::get('teams', [A::class, 'teams']);
    Route::post('teams', [A::class, 'createTeam']);
    Route::get('teams/{team}', [A::class, 'team']);
    Route::post('teams/{team}/{action}/{member?}', [A::class, 'teamAction']);
    Route::get('tournaments/{tournament}', [A::class, 'tournament']);
    Route::get('tournaments/{tournament}/export', [A::class, 'export']);
    Route::post('tournaments/{tournament}/generate', [A::class, 'generate']);
    Route::post('tournaments/{tournament}/matches/{match}', [A::class, 'result']);
    Route::post('registrations/{registration}/review', [A::class, 'review']);
    Route::get('users', [A::class, 'users']);
    Route::post('users/create', [A::class, 'createUser']);
    Route::post('users/{user}/password', [A::class, 'setPassword']);
    Route::post('users/{user}/staff-access', [A::class, 'staffAccess']);
    Route::post('users/{user}/delete', [A::class, 'deleteStaff']);
    Route::post('users/administration', [A::class, 'promote']);
    Route::post('users/{user}/access', [A::class, 'playerAccess']);
    Route::post('users/{user}/role', [A::class, 'role']);
    Route::get('content/{section}', [A::class, 'listing']);
    Route::get('content/{section}/new', [A::class, 'edit']);
    Route::get('content/{section}/{id}/edit', [A::class, 'edit']);
    Route::post('content/{section}/{id?}', [A::class, 'save']);
});
Route::get('/{path?}', [\App\Http\Controllers\SeoController::class, 'frontend'])->where('path', '(?!(?:api|admin|storage|uploads|site|up)(?:/|$)).*');
