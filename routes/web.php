<?php

use Illuminate\Support\Facades\Route;

// Everything that isn't the API is handed to Vue Router.
Route::view('/{any?}', 'app')->where('any', '^(?!api/|sanctum/|up$).*$');
