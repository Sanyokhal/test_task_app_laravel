<?php

use App\Http\Controllers\Articles;
use Illuminate\Support\Facades\Route;

Route::get('/articles', [Articles::class, 'index'])->name('articles.index');
Route::post('/upload_price', [Articles::class, 'uploadArticles'])->name('upload.price');
