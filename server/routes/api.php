<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ContentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\TournamentController;
use App\Http\Middleware\ActiveUser;
use App\Models\Discipline;
use Illuminate\Support\Facades\Route;

Route::post('auth/local', [AuthController::class, 'login'])->middleware('throttle:10,1');
Route::post('auth/local/register', [AuthController::class, 'register'])->middleware('throttle:10,1');
Route::get('disciplines', fn () => ['data' => Discipline::with('icon')->orderBy('name')->get()]);
Route::get('seo', [\App\Http\Controllers\SeoController::class, 'metadata']);
Route::get('teams', [TeamController::class, 'index']);
Route::get('teams/{team}/details', [TeamController::class, 'details']);
Route::get('tournaments', [TournamentController::class, 'index']);
Route::get('tournaments/{tournament}/details', [TournamentController::class, 'details']);
Route::get('articles/{id}', [ContentController::class, 'article']);
Route::get('staffs/{id}', [ContentController::class, 'staff']);
Route::get('{kind}', [ContentController::class, 'index'])->whereIn('kind', ['articles', 'staffs', 'about', 'first-screen', 'setting']);
Route::middleware(['auth:sanctum', ActiveUser::class])->group(function () {
    Route::get('users/me', [AuthController::class, 'me']);
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::post('auth/change-password', [AuthController::class, 'password']);
    Route::get('player-profile/me', [ProfileController::class, 'show']);
    Route::put('player-profile/me', [ProfileController::class, 'save']);
    Route::post('upload', [ProfileController::class, 'upload'])->middleware('throttle:20,1');
    Route::get('teams/mine', [TeamController::class, 'mine']);
    Route::post('teams/mine', [TeamController::class, 'create']);
    Route::put('teams/{team}/mine', [TeamController::class, 'update']);
    Route::post('teams/{team}/players', [TeamController::class, 'add']);
    Route::post('teams/{team}/players/existing', [TeamController::class, 'addExisting']);
    Route::delete('teams/{team}/players/{membership}', [TeamController::class, 'remove']);
    Route::post('teams/{team}/transfer-captain', [TeamController::class, 'transfer']);
    Route::get('tournament-registrations/mine', [RegistrationController::class, 'mine']);
    Route::post('tournament-registrations/register', [RegistrationController::class, 'register']);
    Route::post('tournament-registrations/{registration}/withdraw', [RegistrationController::class, 'withdraw']);
    Route::put('tournament-registrations/{registration}/review', [RegistrationController::class, 'review']);
    Route::get('tournaments/{tournament}/registrations', [RegistrationController::class, 'forTournament']);
    Route::post('tournaments/{tournament}/generate-bracket', [TournamentController::class, 'generate']);
    Route::put('tournaments/{tournament}/matches/{match}/result', [TournamentController::class, 'result']);
});
