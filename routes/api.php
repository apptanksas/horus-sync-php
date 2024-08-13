<?php

use AppTank\Horus\HorusContainer;
use AppTank\Horus\Illuminate\Http\Controller\GetDataEntitiesController;
use AppTank\Horus\Illuminate\Http\Controller\GetEntityHashesController;
use AppTank\Horus\Illuminate\Http\Controller\GetMigrationSchemaController;
use AppTank\Horus\Illuminate\Http\Controller\GetQueueActionsController;
use AppTank\Horus\Illuminate\Http\Controller\GetQueueLastActionController;
use AppTank\Horus\Illuminate\Http\Controller\PostSyncQueueActionsController;
use AppTank\Horus\Illuminate\Http\Controller\SearchEntitiesController;
use AppTank\Horus\Illuminate\Http\Controller\ValidateEntitiesDataController;
use AppTank\Horus\Illuminate\Http\Controller\ValidateHashingController;
use AppTank\Horus\RouteName;
use Illuminate\Support\Facades\Route;

$middlewares = array_merge(['throttle'], HorusContainer::getInstance()->getMiddlewares());

Route::get('/migration', [
    'uses' => GetMigrationSchemaController::class,
    'as' => RouteName::GET_MIGRATIONS->value,
    'middleware' => $middlewares
]);

Route::post("queue/actions", [
    'uses' => PostSyncQueueActionsController::class,
    'as' => RouteName::POST_SYNC_QUEUE_ACTIONS->value,
    'middleware' => $middlewares
]);

Route::get("queue/actions", [
    'uses' => GetQueueActionsController::class,
    'as' => RouteName::GET_SYNC_QUEUE_ACTIONS->value,
    'middleware' => $middlewares
]);

Route::get("data", [
    'uses' => GetDataEntitiesController::class,
    'as' => RouteName::GET_DATA_ENTITIES->value,
    'middleware' => $middlewares
]);

Route::get("data/{entity}", [
    'uses' => SearchEntitiesController::class,
    'as' => RouteName::GET_ENTITY_DATA->value,
    'middleware' => $middlewares
]);

// Get last action
Route::get("queue/actions/last", [
    'uses' => GetQueueLastActionController::class,
    'as' => RouteName::GET_SYNC_QUEUE_LAST_ACTION->value,
    'middleware' => $middlewares
]);

// Get entity hashes
Route::get("entity/{entity}/hashes", [
    'uses' => GetEntityHashesController::class,
    'as' => RouteName::GET_ENTITY_HASHES->value,
    'middleware' => $middlewares
]);

// Validate data
Route::post("validate/data", [
    'uses' => ValidateEntitiesDataController::class,
    'as' => RouteName::POST_VALIDATE_DATA->value,
    'middleware' => $middlewares
]);

// Validate hashing
Route::post("validate/hashing", [
    'uses' => ValidateHashingController::class,
    'as' => RouteName::POST_VALIDATE_HASHING->value,
    'middleware' => $middlewares
]);

