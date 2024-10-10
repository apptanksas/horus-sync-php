<?php

use AppTank\Horus\Horus;
use AppTank\Horus\Illuminate\Http\Controller\GetDataEntitiesController;
use AppTank\Horus\Illuminate\Http\Controller\GetEntityHashesController;
use AppTank\Horus\Illuminate\Http\Controller\GetMigrationSchemaController;
use AppTank\Horus\Illuminate\Http\Controller\GetQueueActionsController;
use AppTank\Horus\Illuminate\Http\Controller\GetQueueLastActionController;
use AppTank\Horus\Illuminate\Http\Controller\PostSyncQueueActionsController;
use AppTank\Horus\Illuminate\Http\Controller\SearchEntitiesController;
use AppTank\Horus\Illuminate\Http\Controller\UploadFileController;
use AppTank\Horus\Illuminate\Http\Controller\ValidateEntitiesDataController;
use AppTank\Horus\Illuminate\Http\Controller\ValidateHashingController;
use AppTank\Horus\RouteName;
use Illuminate\Support\Facades\Route;


/**
 * Route definitions for handling various API endpoints related to migrations, queues, data entities, and validations.
 * Each route is associated with a specific controller and middleware, with routes covering GET and POST operations.
 *
 * Middlewares: Merged array of default throttling and other middlewares retrieved from the Horus.
 * @author John Ospina
 * Year: 2024
 */

// Combine throttle middleware with additional middlewares from the Horus
$middlewares = array_merge(['throttle'], Horus::getInstance()->getMiddlewares());

// Route to get migration schema
Route::get('/migration', [
    'uses' => GetMigrationSchemaController::class,
    'as' => RouteName::GET_MIGRATIONS->value,
    'middleware' => $middlewares
]);

// Route to post queue actions for synchronization
Route::post("queue/actions", [
    'uses' => PostSyncQueueActionsController::class,
    'as' => RouteName::POST_SYNC_QUEUE_ACTIONS->value,
    'middleware' => $middlewares
]);

// Route to get queue actions for synchronization
Route::get("queue/actions", [
    'uses' => GetQueueActionsController::class,
    'as' => RouteName::GET_SYNC_QUEUE_ACTIONS->value,
    'middleware' => $middlewares
]);

// Route to get data entities
Route::get("data", [
    'uses' => GetDataEntitiesController::class,
    'as' => RouteName::GET_DATA_ENTITIES->value,
    'middleware' => $middlewares
]);

// Route to search for specific entity data
Route::get("data/{entity}", [
    'uses' => SearchEntitiesController::class,
    'as' => RouteName::GET_ENTITY_DATA->value,
    'middleware' => $middlewares
]);

// Route to get the last action in the queue
Route::get("queue/actions/last", [
    'uses' => GetQueueLastActionController::class,
    'as' => RouteName::GET_SYNC_QUEUE_LAST_ACTION->value,
    'middleware' => $middlewares
]);

// Route to get hashes for a specific entity
Route::get("entity/{entity}/hashes", [
    'uses' => GetEntityHashesController::class,
    'as' => RouteName::GET_ENTITY_HASHES->value,
    'middleware' => $middlewares
]);

// Route to validate data entities
Route::post("validate/data", [
    'uses' => ValidateEntitiesDataController::class,
    'as' => RouteName::POST_VALIDATE_DATA->value,
    'middleware' => $middlewares
]);

// Route to validate hashing of data
Route::post("validate/hashing", [
    'uses' => ValidateHashingController::class,
    'as' => RouteName::POST_VALIDATE_HASHING->value,
    'middleware' => $middlewares
]);

// Route to upload a file
Route::post("upload/file", [
    'uses' => UploadFileController::class,
    'as' => RouteName::POST_UPLOAD_FILE->value,
    'middleware' => $middlewares
]);