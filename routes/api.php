<?php

use AppTank\Horus\Illuminate\Http\Controller\GetMigrationSchemaController;
use AppTank\Horus\Illuminate\Http\Controller\PostSyncQueueActionsController;
use AppTank\Horus\RouteName;
use Illuminate\Support\Facades\Route;

Route::get('/migration', [
    'uses' => GetMigrationSchemaController::class,
    'as' => RouteName::GET_MIGRATIONS->value,
    'middleware' => 'throttle'
]);

Route::post("queue/actions", [
    'uses' => PostSyncQueueActionsController::class,
    'as' => RouteName::POST_SYNC_QUEUE_ACTIONS->value,
    'middleware' => 'throttle'
]);