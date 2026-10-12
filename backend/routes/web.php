<?php

use Illuminate\Support\Facades\Route;

// setup.sh copies the web app (frontend/app.html) and, when built, the product
// website (marketing/dist/website -> public/home.html) into public/. The domain's
// home page shows the website if present, otherwise goes straight to the app.
Route::get('/', fn () => redirect(file_exists(public_path('home.html')) ? '/home.html' : '/app.html'));
