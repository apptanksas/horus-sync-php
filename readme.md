**Attention:** This library currently is testing stage until publish the version 1.0.0.


<p align="center">

<img src="https://raw.githubusercontent.com/apptanksas/horus-sync-php/master/assets/logo-horusync.svg" width="400" alt="Horusync Logo">
<br/>
<img src="https://github.com/apptanksas/horus-sync-php/actions/workflows/unit_tests.yml/badge.svg" alt="Build Status">
<img src="https://img.shields.io/packagist/v/apptanksas/horus-sync-php" alt="Latest Stable Version">
<img src="https://img.shields.io/packagist/l/apptanksas/horus-sync-php" alt="License">
</p>

# Horus Sync

This library provides an easy way to manage data synchronization between a remote database on a server and a local database on a mobile device. It defines a set of synchronizable entities by specifying their parameters and relationships with other entities.

### Features
* **Data Synchronization:** Enables synchronization of the defined entities' data between the local database and the remote database.
* **Schema Migration:** Allows retrieval of the schema for synchronizable entities and their relationships.
* **Integrity Validation:** Ensures the integrity of synchronized data.
* **Authentication and Permissions:** Defines an authenticated user and the permissions associated with the entities.
* **Middlewares:** Supports defining custom middlewares for synchronization routes.
* **UUID Support:** Allows specifying whether UUIDs or Int will be used as the primary key for entities.
* **Foreign Key Support:** Supports defining foreign keys in synchronizable entities with cascading delete functionality.
* **UserActingAs Support:** Indicates if a user has permission to act as another user and access the entities where permissions have been granted.
* **Support for Synchronizable Entity Types:** Supports defining entities as writable or readable, depending on whether they can be edited or not.

## Installation

This library must be used with Laravel version 11 or higher.

Use the following command to install the package using Composer:
```bash
composer require apptank/horusync
```

## Usage

All synchronizable models with editable records must inherit from `WritableEntitySynchronizable`, defining the properties they contain. If you want a read-only entity, you should inherit from `ReadableEntitySynchronizable`.

You can use an artisan command to generate a synchronizable model for you. You just need to specify the path where the model should be stored. The following example will create a model in `App/Models/Sync/MyModelSync`:


```bash
php artisan horus:entity MyModelSync
```

```php
namespace App\Models\Sync;

use AppTank\Horus\Core\Entity\SyncParameter;
use AppTank\Horus\Illuminate\Database\WritableEntitySynchronizable;

class MyModel extends WritableEntitySynchronizable
{
    public static function parameters(): array
    {
        return [
            // Define the parameters of the entity
            SyncParameter::createString("name", 1),
            SyncParameter::createInt("age", 1),
            SyncParameter::createPrimaryKeyString("email", 1),
            SyncParameter::createTimestamp("datetime", 1),
            SyncParameter::createBoolean("active", 1),
            SyncParameter::createFloat("price", 1),
            SyncParameter::createRelationOneToMany("children", [ChildModel::class], 1)
        ];
    }

    public static function getEntityName(): string
    {
        return "my_model";
    }

    public static function getVersionNumber(): int
    {
        return 1;
    }
}
```

If you want to create a read-only entity, you can add the `--readable` option to the artisan command.

```bash
php artisan horus:entity MyModelSync --readable
```

## Initialization

### Initialize Container

It is necessary to initialize the Horus container in the `AppServiceProvider` of Laravel by passing an array with the hierarchical map of entities.

* You can optionally define the database connection name and whether UUIDs or Int will be used as the primary key.

```php
class AppServiceProvider extends ServiceProvider
{

    public function register(): void
    {

    }

    public function boot(): void
    {
        Horus::initialize([
            MyModel::class => [ChildModel::class], // Entities Map
            "sync_database", // Connection Name
            false // Uses UUID
        ]);
        
    }

}
```

### Run Migrations

Once the Horus container has been initialized, you need to run the synchronization tables migrations.

```bash
php artisan migrate
```

### Middlewares

If you want to implement custom middlewares for the synchronization routes, you can do so in your Service Provider as follows:

```php

Horus::setMiddleware([MyMiddleware::class,'throttle:60,1']);

```

### Authentication and Permissions

The following code shows how to configure user authentication and the permissions associated with entities. Using the UserAuth class, you define an authenticated user along with the entities they have access to and the corresponding permissions.

```php
Horus::getInstance()->setUserAuthenticated(
 new UserAuth(
   "07a35af0-7317-41e4-99a3-e3583099aff2", // User Id Authenticated
   [ // Array of Entities Granted
   new EntityGranted(
   "971785f7-0f01-46cd-a3ce-af9ce6273d3d", // User Owner Id
   "animal", // Entity Name
    "9135e859-b053-4cfb-b701-d5f240b0aab1", // Entity Id
    // Set the permissions for the entity
   , new AccessLevel(Permission::READ, Permission::CREATE)),
    // User Acting As
   new UserAuth("b253a0e8-027b-463c-b87a-b18f09c99ddd")
   ]
 )
);
```

# Routes

### Migration Schema

Returns a migration schema for the synchronizable entities. It indicates the attributes of each entity and their relationships. It also includes the current version of the entity and the current version of each attribute to determine if the client's database needs to be migrated to a new version.

#### URL: GET

``` 
/sync/migration
 ```

#### Response

<details>
  <summary>Click here to see the response</summary>

```json
[
  {
    "entity": "parent_fake_entity",
    "attributes": [
      {
        "name": "id",
        "version": 1,
        "type": "primary_key_string",
        "nullable": false
      },
      {
        "name": "sync_owner_id",
        "version": 1,
        "type": "string",
        "nullable": false
      },
      {
        "name": "sync_hash",
        "version": 1,
        "type": "string",
        "nullable": false
      },
      {
        "name": "sync_created_at",
        "version": 1,
        "type": "timestamp",
        "nullable": false
      },
      {
        "name": "sync_updated_at",
        "version": 1,
        "type": "timestamp",
        "nullable": false
      },
      {
        "name": "name",
        "version": 1,
        "type": "string",
        "nullable": false
      },
      {
        "name": "color",
        "version": 2,
        "type": "timestamp",
        "nullable": false
      },
      {
        "name": "value_nullable",
        "version": 2,
        "type": "string",
        "nullable": true
      },
      {
        "name": "children",
        "version": 2,
        "type": "relation_one_to_many",
        "nullable": false,
        "related": [
          {
            "entity": "child_fake_entity",
            "attributes": [
              {
                "name": "id",
                "version": 1,
                "type": "primary_key_string",
                "nullable": false
              },
              {
                "name": "sync_owner_id",
                "version": 1,
                "type": "string",
                "nullable": false
              },
              {
                "name": "sync_hash",
                "version": 1,
                "type": "string",
                "nullable": false
              },
              {
                "name": "sync_created_at",
                "version": 1,
                "type": "timestamp",
                "nullable": false
              },
              {
                "name": "sync_updated_at",
                "version": 1,
                "type": "timestamp",
                "nullable": false
              },
              {
                "name": "primary_int_value",
                "version": 5,
                "type": "primary_key_integer",
                "nullable": false
              },
              {
                "name": "primary_string_value",
                "version": 5,
                "type": "primary_key_string",
                "nullable": false
              },
              {
                "name": "int_value",
                "version": 5,
                "type": "int",
                "nullable": true
              },
              {
                "name": "float_value",
                "version": 5,
                "type": "float",
                "nullable": false
              },
              {
                "name": "string_value",
                "version": 5,
                "type": "string",
                "nullable": false
              },
              {
                "name": "boolean_value",
                "version": 5,
                "type": "boolean",
                "nullable": false
              },
              {
                "name": "timestamp_value",
                "version": 5,
                "type": "timestamp",
                "nullable": false
              },
              {
                "name": "parent_id",
                "version": 5,
                "type": "string",
                "nullable": false
              }
            ],
            "current_version": 5
          }
        ]
      }
    ],
    "current_version": 2
  }
]
```

</details>

### Send Data to Synchronize

This endpoint receives an array of actions to be performed in the database. Each action must specify the action type, the entity it refers to, the data to be modified, and the date when the action was performed.


#### URL: POST

``` 
/sync/queue/actions 
```

#### Example request data

<details>
<summary>Click here to see the example request</summary>

```json
[
  {
    "action": "DELETE",
    "entity": "parent_fake_entity",
    "data": {
      "id": "3093a07a-543b-336b-9ca8-4c3bf207aeb5"
    },
    "actioned_at": 1124141410
  },
  {
    "action": "UPDATE",
    "entity": "parent_fake_entity",
    "data": {
      "id": "3093a07a-543b-336b-9ca8-4c3bf207aeb5",
      "attributes": {
        "name": "ernser.mazie",
        "color": "Thistle"
      }
    },
    "actioned_at": 1124140410
  },
  {
    "action": "INSERT",
    "entity": "parent_fake_entity",
    "data": {
      "id": "3093a07a-543b-336b-9ca8-4c3bf207aeb5",
      "name": "quitzon.gunnar",
      "color": "DarkGray"
    },
    "actioned_at": 1124139410
  }
]
```

</details>

#### Response: 201 - Accepted

## Get the synchronization actions performed in chronological order

#### URL: GET

``` 
/sync/queue/actions 
```

### Get the synchronized data of all entities

#### URL: GET

``` 
/sync/data
```

#### Optional parameters
* **after (timestamp:int):** Filters the data that has been synchronized after the indicated date.

## Get the synchronized data of a specific entity

#### URL: GET

``` 
/sync/{entity}
```

### Optional parameters
* **after (timestamp:int):** Filters the data that has been synchronized after the indicated date.
* **ids (array):** Filters the data that has been synchronized with the given ids.


## Get the last synchronization action performed

#### URL: GET

``` 
/sync/queue/actions/last
```

## Get the hashes of the synchronized records of an entity

#### URL: GET

``` 
/sync/entity/{entity}/hashes
```

#### Response

<details>
  <summary>Click here to see the example response</summary>

```json
[
  {
    "id": "fccd745b-780c-3b14-b657-ad05d86318e0",
    "sync_hash": "26dbdd1c5dff8da8d79747da5aab53b6052beb5247c8f2464ee1b771f818aa81"
  },
  {
    "id": "6de8f02c-ec7a-3df8-ac2e-e3542c5e4693",
    "sync_hash": "06b8ad4f55cc4724beda154ecb140592c08813eac762a531dcc489b360cef633"
  },
  {
    "id": "31af3908-454e-3070-a2f7-a79f12a17c9a",
    "sync_hash": "0de9e664000ee76acdc2c705ca0446b32738ae781b797d3c347e34c547ccae4e"
  }
]
```
</details>


## Validate Synchronized Data Integrity

This endpoint receives an array of entities and their hashes, and returns an array with each entity and whether the hash matches the hash of the entity in the database, indicating that the data integrity is correct.


#### URL: POST

``` 
/sync/validate/data
```

#### Request data de ejemplo

<details>
<summary>Click here to see the example request</summary>

```json
[
  {
    "entity": "parent_fake_entity",
    "hash": "2d971864ba0f14de5ad0be8f5ea6e0bc99685fdbb1f1495a8d8557ffdc572012"
  }
]
```

#### Example response

<details>
    <summary>Click here to see the example response</summary>
    
```json
[
  {
    "entity": "parent_fake_entity",
    "hash": {
      "expected": "2d971864ba0f14de5ad0be8f5ea6e0bc99685fdbb1f1495a8d8557ffdc572012",
      "obtained": "2d971864ba0f14de5ad0be8f5ea6e0bc99685fdbb1f1495a8d8557ffdc572012",
      "matched": true
    }
  }
]
```
</details>

## Validate Entity Hashing Algorithm

This endpoint receives an array of attributes and a hash indicating the client-side hash of these attributes. The server performs the same process and compares the obtained hash with the hash received from the client to validate that the hashing algorithm is the same.

#### URL: POST

``` 
/sync/validate/hashing
```

#### Example request data
```json
{
  "data": {
    "z1": "7e998082-a377-3485-b5ed-b225b0c409e9",
    "age": 85,
    "mood": "b176423b-b393-3e21-81de-6b1d58c54c66",
    "date": 1723558663
  },
  "hash": "17ffc52d4338881a6091ee80a4ef08db3901a86bf41636fa431a8ff5de3a6cf8"
}
```

#### Example response

```json
{
  "expected": "17ffc52d4338881a6091ee80a4ef08db3901a86bf41636fa431a8ff5de3a6cf8",
  "obtained": "17ffc52d4338881a6091ee80a4ef08db3901a86bf41636fa431a8ff5de3a6cf8",
  "matched": true
}
```