<?php

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
    return redirect()->back()->fallback(route('home'));
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

/*
|--------------------------------------------------------------------------
| Ably Token Auth
|--------------------------------------------------------------------------
| Ably JS SDK requests a connection-level token (no channel_name), which
| is incompatible with Laravel's AblyBroadcaster::auth() that expects a
| per-channel request. This endpoint generates a signed TokenRequest that
| the Ably JS SDK exchanges directly with Ably servers.
*/

// GET, no CSRF — Ably JS requests this with authMethod: 'GET'.
// Returns a signed TokenRequest; Ably SDK exchanges it directly with Ably.
Route::get('/ably-auth', function () {
    $ably = new \Ably\AblyRest(config('broadcasting.connections.ably.key'));
    $tokenRequest = $ably->auth->createTokenRequest([
        'capability' => json_encode(['public:competition.*' => ['subscribe', 'history', 'channel-metadata']]),
    ]);
    return response()->json($tokenRequest);
})->name('ably.auth');
