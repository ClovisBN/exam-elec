<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ElectionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AdminController;


Route::get('/', function () {
    return view('welcome');
});

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

Route::resource('elections', ElectionController::class);
Route::post('elections/{election}/start', [ElectionController::class, 'start'])->name('elections.start');
Route::post('elections/{election}/endRound', [ElectionController::class, 'endRound'])->name('elections.endRound');
Route::get('elections/{election}/vote/{type}', [ElectionController::class, 'vote'])->name('elections.vote');
Route::post('elections/{election}/submit-vote/{type}', [ElectionController::class, 'submitVote'])->name('elections.submitVote');
Route::get('elections/{election}/results', [ElectionController::class, 'results'])->name('elections.results');
Route::get('elections/{election}/join', [ElectionController::class, 'showJoinForm'])->name('elections.joinForm');
Route::post('elections/{election}/join', [ElectionController::class, 'join'])->name('elections.join');
Route::get('elections/{election}/waiting', [ElectionController::class, 'waiting'])->name('elections.waiting');
Route::post('elections/{election}/toggle-candidate', [ElectionController::class, 'toggleCandidate'])->name('elections.toggleCandidate');
Route::get('/elections/{election}/check-round-status', [ElectionController::class, 'checkRoundStatus'])->name('elections.checkRoundStatus');


Route::group(['middleware' => ['check.visitor']], function () {
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update');
});


Route::middleware(['auth', 'check.role:admin'])->group(function () {
    Route::get('/admin/users', [AdminController::class, 'index'])->name('admin.users.index');
    Route::get('/admin/users/{user}', [AdminController::class, 'show'])->name('admin.users.show');
    Route::get('/admin/users/{user}/edit', [AdminController::class, 'edit'])->name('admin.users.edit');
    Route::put('/admin/users/{user}', [AdminController::class, 'update'])->name('admin.users.update');
    Route::delete('/admin/users/{user}', [AdminController::class, 'destroy'])->name('admin.users.destroy');
});
