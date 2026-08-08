<?php

use App\Http\Controllers\HostController;
use App\Http\Controllers\TvController;
use App\Http\Controllers\VoteController;
use Illuminate\Support\Facades\Route;

Route::get('/', [VoteController::class, 'create'])->name('home');
Route::post('/vote', [VoteController::class, 'store'])->name('vote.store');

Route::get('/tv', TvController::class)->name('tv');

Route::get('/host/{secret}', [HostController::class, 'show'])->name('host.show');
Route::post('/host/{secret}/reveal', [HostController::class, 'reveal'])->name('host.reveal');
Route::post('/host/{secret}/reset', [HostController::class, 'reset'])->name('host.reset');
