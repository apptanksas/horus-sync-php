<?php

use AppTank\Horus\Illuminate\Http\Controller\GetMigrationSchemaController;
use AppTank\Horus\RouteName;
use Illuminate\Support\Facades\Route;

Route::get('/migration', [
    'uses' => GetMigrationSchemaController::class,
    'as' => RouteName::GET_MIGRATIONS->value,
    'middleware' => 'throttle'
]);