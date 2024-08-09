<?php

use AppTank\Horus\Illuminate\Http\Controller\GetDataEntitiesController;
use AppTank\Horus\Illuminate\Http\Controller\GetMigrationSchemaController;
use AppTank\Horus\Illuminate\Http\Controller\PostSyncQueueActionsController;
use AppTank\Horus\Illuminate\Http\Controller\SearchEntitiesController;
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

Route::get("data", [
    'uses' => GetDataEntitiesController::class,
    'as' => RouteName::GET_DATA_ENTITIES->value,
    'middleware' => 'throttle'
]);

Route::get("data/{entity}", [
    'uses' => SearchEntitiesController::class,
    'as' => RouteName::SEARCH_ENTITIES->value,
    'middleware' => 'throttle'
]);