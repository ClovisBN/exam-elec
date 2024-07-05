<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ElectionController;

Route::get('/', function () {
    return view('welcome');
});

Auth::routes();

Route::get('/home', [HomeController::class, 'index'])->name('home');

Route::resource('elections', ElectionController::class);
Route::post('elections/{election}/start', [ElectionController::class, 'start'])->name('elections.start');
Route::post('elections/{election}/endRound', [ElectionController::class, 'endRound'])->name('elections.endRound');
Route::get('elections/{election}/vote', [ElectionController::class, 'vote'])->name('elections.vote');
Route::post('elections/{election}/submit-vote', [ElectionController::class, 'submitVote'])->name('elections.submitVote');
Route::get('elections/{election}/results', [ElectionController::class, 'results'])->name('elections.results');
Route::get('elections/{election}/join', [ElectionController::class, 'showJoinForm'])->name('elections.joinForm');
Route::post('elections/{election}/join', [ElectionController::class, 'join'])->name('elections.join');
Route::get('elections/{election}/waiting', [ElectionController::class, 'waiting'])->name('elections.waiting');
Route::post('elections/{election}/toggle-candidate', [ElectionController::class, 'toggleCandidate'])->name('elections.toggleCandidate');
Route::get('/elections/{election}/check-round-status', [ElectionController::class, 'checkRoundStatus'])->name('elections.checkRoundStatus');
