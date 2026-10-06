<?php

use Illuminate\Support\Facades\Route;

// The web app (frontend/app.html) is copied into public/ by setup.sh, so the
// API and the app share one domain. Visiting the domain opens the app.
Route::get('/', fn () => redirect('/app.html'));
