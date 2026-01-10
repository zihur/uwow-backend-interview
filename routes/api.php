<?php

use App\Http\Controllers\OrderController;
use App\Http\Controllers\PostController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json(['message' => 'API is working']);
});

Route::get('/posts/search', [PostController::class, 'search'])->name('posts.search');
Route::get('/active-posts', [PostController::class, 'activeIndex'])->name('posts.activeIndex');
Route::get('/trashed-posts', [PostController::class, 'withTrashIndex'])->name('posts.withTrashIndex');
Route::get('/posts', [PostController::class, 'index'])->name('posts.index');
Route::get('/posts/{post}', [PostController::class, 'show'])->name('posts.show');
Route::post('/posts', [PostController::class, 'store'])->name('posts.store');
Route::put('/posts/{post}', [PostController::class, 'update'])->name('posts.update');
Route::delete('/posts/{post}', [PostController::class, 'destroy'])->name('posts.destroy');

