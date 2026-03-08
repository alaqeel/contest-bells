<?php

use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\CompetitionController as AdminCompetitionController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\CompetitionController;
use App\Http\Controllers\ContestantController;
use App\Http\Controllers\DisplayController;
use App\Http\Controllers\JudgeController;
use App\Http\Controllers\ResultsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Judge Routes
|--------------------------------------------------------------------------
*/

// Locale switcher
Route::get('/locale/{locale}', function (string $locale) {
    $supported = ['ar', 'en'];
    if (in_array($locale, $supported, true)) {
        session(['locale' => $locale]);
    }
    return redirect()->back(302, [], route('home'));
})->name('locale.switch');

// Setup page (home)
Route::get('/', [CompetitionController::class, 'create'])->name('home');
Route::post('/competitions', [CompetitionController::class, 'store'])->name('competition.store');

// Judge dashboard
Route::prefix('judge/{roomCode}')->name('judge.')->group(function () {
    Route::get('/', [JudgeController::class, 'dashboard'])->name('dashboard');
    Route::post('/rounds/start', [JudgeController::class, 'startRound'])->name('rounds.start');
    Route::post('/rounds/reset', [JudgeController::class, 'resetRound'])->name('rounds.reset');
    Route::post('/rounds/{round}/answer', [JudgeController::class, 'markAnswer'])->name('rounds.answer');
    Route::post('/end', [JudgeController::class, 'endCompetition'])->name('end');
    Route::get('/state', [JudgeController::class, 'state'])->name('state');
});

/*
|--------------------------------------------------------------------------
| Contestant Routes
|--------------------------------------------------------------------------
*/

Route::prefix('join/{roomCode}')->name('contestant.')->group(function () {
    Route::get('/', [ContestantController::class, 'join'])->name('join');
    Route::post('/claim', [ContestantController::class, 'claim'])->name('claim');
    Route::get('/state', [ContestantController::class, 'joinState'])->name('join.state');
});

Route::prefix('play/{roomCode}/{contestantId}')->name('contestant.')->group(function () {
    Route::get('/', [ContestantController::class, 'play'])->name('play');
    Route::post('/buzz', [ContestantController::class, 'buzz'])->name('buzz');
    Route::get('/state', [ContestantController::class, 'state'])->name('state');
});

/*
|--------------------------------------------------------------------------
| Results + Public Display
|--------------------------------------------------------------------------
*/

Route::get('/results/{roomCode}', [ResultsController::class, 'show'])->name('competition.results');
Route::get('/display/{roomCode}', [DisplayController::class, 'show'])->name('display.show');
Route::get('/display/{roomCode}/state', [DisplayController::class, 'state'])->name('display.state');

/*
|--------------------------------------------------------------------------
| Super Admin Routes
|--------------------------------------------------------------------------
*/

// Public: admin login / logout
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login',  [AdminAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AdminAuthController::class, 'login'])->name('login.submit');
    Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');

    // Protected: requires auth + super_admin role
    Route::middleware(['auth', 'super_admin'])->group(function () {
        Route::get('/',                                   [AdminDashboardController::class, 'index'])->name('dashboard');
        Route::get('/competitions',                       [AdminCompetitionController::class, 'index'])->name('competitions.index');
        Route::get('/competitions/{competition}',         [AdminCompetitionController::class, 'show'])->name('competitions.show');
        Route::post('/competitions/{competition}/end',    [AdminCompetitionController::class, 'end'])->name('competitions.end');
    });
});
