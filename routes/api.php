<?php

use AppTank\Horus\Illuminate\Http\Controller\GetMigrationSchemaController;
use Illuminate\Support\Facades\Route;

Route::get('/migration', [
    'uses' => GetMigrationSchemaController::class,
    'as' => 'migration',
    'middleware' => 'throttle',
]);