<?php
 

use Illuminate\Support\Facades\Route;
use Modules\ThemeStyle\App\Http\Controllers\ThemeStyle;

Route::get('theme-style', ThemeStyle::class);
