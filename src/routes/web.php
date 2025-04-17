<?php
use twachikku\Vue3cdn\Controllers\VueController;
use Illuminate\Support\Facades\Route;
use Illuminate\Session\Middleware\StartSession;

Route::any('/vue3/{appid}/{pageid}', [VueController::class, 'index'])
    ->middleware(StartSession::class);
