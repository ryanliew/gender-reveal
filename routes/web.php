<?php

use App\Http\Controllers\VoteController;
use Illuminate\Support\Facades\Route;

Route::get('/', [VoteController::class, 'create'])->name('home');
Route::post('/vote', [VoteController::class, 'store'])->name('vote.store');
