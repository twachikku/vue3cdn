<?php
use twachikku\Vue3cdn\Controllers\VueController;
use Illuminate\Support\Facades\Route;

Route::any('/vue3/{appid}/{pageid}', [VueController::class, 'index']);
