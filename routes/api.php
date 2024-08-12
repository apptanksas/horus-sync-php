<?php

use AppTank\Horus\Illuminate\Http\Controller\GetDataEntitiesController;
use AppTank\Horus\Illuminate\Http\Controller\GetEntityHashesController;
use AppTank\Horus\Illuminate\Http\Controller\GetMigrationSchemaController;
use AppTank\Horus\Illuminate\Http\Controller\GetQueueActionsController;
use AppTank\Horus\Illuminate\Http\Controller\GetQueueLastActionController;
use AppTank\Horus\Illuminate\Http\Controller\PostSyncQueueActionsController;
use AppTank\Horus\Illuminate\Http\Controller\SearchEntitiesController;
use AppTank\Horus\Illuminate\Http\Controller\ValidateEntitiesDataController;
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

Route::get("queue/actions", [
    'uses' => GetQueueActionsController::class,
    'as' => RouteName::GET_SYNC_QUEUE_ACTIONS->value,
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

// Get last action
Route::get("queue/actions/last", [
    'uses' => GetQueueLastActionController::class,
    'as' => RouteName::GET_SYNC_QUEUE_LAST_ACTION->value,
    'middleware' => 'throttle'
]);

// Get entity hashes
Route::get("entity/{entity}/hashes", [
    'uses' => GetEntityHashesController::class,
    'as' => RouteName::GET_ENTITY_HASHES->value,
    'middleware' => 'throttle'
]);

// Validate data
Route::post("validate/data", [
    'uses' => ValidateEntitiesDataController::class,
    'as' => RouteName::POST_VALIDATE_DATA->value,
    'middleware' => 'throttle'
]);

// Validate hashing
Route::post("validate/hashing", [
    'as' => RouteName::POST_VALIDATE_HASHING->value,
    'middleware' => 'throttle'
]);

