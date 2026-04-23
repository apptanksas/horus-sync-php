<p align="center">

<img src="https://raw.githubusercontent.com/apptanksas/horus-sync-php/master/assets/logo-horusync.svg" width="400" alt="Horusync Logo">
<br/>
<img src="https://github.com/apptanksas/horus-sync-php/actions/workflows/unit_tests.yml/badge.svg" alt="Build Status">
<img src="https://img.shields.io/packagist/v/apptank/horusync" alt="Latest Stable Version">
<img src="https://img.shields.io/github/license/apptanksas/horus-sync-php" alt="License">
<a href="https://deepwiki.com/apptanksas/horus-sync-php"><img src="https://deepwiki.com/badge.svg" alt="Ask DeepWiki"></a>
</p>

**Please note:** This library currently is in testing stage until publish the version 1.0.0.

# Horus Sync

This library provides an easy way to manage data synchronization between a remote database on a server and a local
database on a mobile device. It defines a set of synchronizable entities by specifying their parameters and
relationships with other entities.

### Features

* **Data Synchronization:** Enables synchronization of the defined entities' data between the local database and the
  remote database.
* **Schema Migration:** Allows retrieval of the schema for synchronizable entities and their relationships.
* **Integrity Validation:** Ensures the integrity of synchronized data.
* **Authentication and Permissions:** Defines an authenticated user and the permissions associated with the entities.
* **Upload Files:** Allows uploading files to the server.
* **Middlewares:** Supports defining custom middlewares for synchronization routes.
* **UUID Support:** Allows specifying whether UUIDs or Int will be used as the primary key for entities.
* **Foreign Key Support:** Supports defining foreign keys in synchronizable entities with cascading delete
  functionality.
* **UserActingAs Support:** Indicates if a user has permission to act as another user and access the entities where
  permissions have been granted.
* **Support for Synchronizable Entity Types:** Supports defining entities as writable or readable, depending on whether
  they can be edited or not.

## 📦 Installation

This library must be used with Laravel version 11 or higher.

Use the following command to install the package using Composer:

```bash
composer require apptank/horusync
```

## 🔨 Usage

All synchronizable models with editable records must inherit from `WritableEntitySynchronizable`, defining the
properties they contain. If you want a read-only entity, you should inherit from `ReadableEntitySynchronizable`.

You can use an artisan command to generate a synchronizable model for you. You just need to specify the path where the
model should be stored. The following example will create a model in `App/Models/Sync/MyModelSync`:

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

## 🚀 Quick Start

### 1. Initialization

It is necessary to initialize the Horus container in the `AppServiceProvider` of Laravel by passing an array with the
hierarchical map of entities.

You can optionally

* Define the database connection name
* Whether UUIDs or Int will be used as the primary key.
* Define a prefix for the tables of entities in the database.
* Enable or disable the validation of access to entities. Improve performance by disabling this option.
* Prefix for the tables of entities in the database.
* Define the entity restrictions.

```php
class AppServiceProvider extends ServiceProvider
{

    public function boot(): void
    {
       // Define the hierarchical map of entities
       $entityMap = [
            MyModel::class => [
                ChildModel::class
            ]
       ];
        
       // Define the configuration
       $config = new Config(
            validateAccess: true,
            connectionName: "sync_database",
            usesUUIDs: true,
            prefixTables: 'hs',
            entityRestrictions: [
                new MaxCountEntityRestriction("user_id","entity_name", maxCount: 10)
            ],
        );
       
         // Initialize Horus
        Horus::initialize($entityMap)->setConfig($config);
    }

}
```

### 2. Run Migrations

Once the Horus container has been initialized, you need to run the synchronization tables migrations.

```bash
php artisan migrate
```

## ⬆️ Enable upload files

To enable the upload files feature, you need setup a file handler in horus initialization, implementing
the `IFileHandler` interface.

```php
Horus::setFileHandler($this->app->make(IFileHandler::class));
```

In your writable entity synchronizable model, you can define the file attribute using
the `SyncParameter::createReferenceFile` method.

```php
class UserImageModel extends WritableEntitySynchronizable
{
    public static function parameters(): array
    {
        return [
            // User Id
            SyncParameter::createUUIDForeignKey("user_id", 1,"users"),
            // Reference to the image file
            SyncParameter::createReferenceFile("image", 1),
        ];
    }
}
```

### Local File Handler example

The following example shows how to implement a file handler for local file storage using Laravel's storage system.

```php

class LocalFileHandler implements IFileHandler
{
    /**
     * @throws \Exception
     */
    function upload(int|string $userOwnerId, string $fileId, string $path, UploadedFile $file): FileUploaded
    {
        $filename = basename($path);
        $basePath = dirname($path);

        $pathResult = $file->storeAs($basePath, $filename);

        if (!$pathResult) {
            throw new \Exception("Error uploading file");
        }

        $mimeType = $file->getMimeType();
        $publicUrl = $this->generateUrl($pathResult);

        return new FileUploaded(
            $fileId,
            $mimeType,
            $pathResult,
            $publicUrl,
            $userOwnerId
        );
    }

    function delete(string $pathFile): bool
    {
        return Storage::delete($pathFile);
    }

    function getMimeTypesAllowed(): array
    {
        return MimeType::IMAGES;
    }

    function copy(string $pathFrom, string $pathTo): bool
    {
        return Storage::copy($pathFrom, $pathTo);
    }

    function generateUrl(string $path): string
    {
        return Storage::url($path);
    }
}
```

### Prune files

If you want to delete files that are no longer referenced by any entity, you can use the following command:

```bash
php artisan horus:prune --expirationDays=7
```

* **expirationDays:** The number of days after which files will be deleted.

## ↔️ Middlewares

If you want to implement custom middlewares for the synchronization routes, you can do so in your Service Provider as
follows:

```php

Horus::setMiddleware([MyMiddleware::class,'throttle:60,1']);

```

## 🔒 Authentication and Permissions

The following code shows how to configure user authentication and the permissions associated with entities. Using the
UserAuth class, you define an authenticated user along with the entities they have access to and the corresponding
permissions.

```php
Horus::getInstance()->setUserAuthenticated(
 new UserAuth(
   "07a35af0-7317-41e4-99a3-e3583099aff2", // User Id Authenticated
   [ // Array of Entities Granted
   new EntityGranted(
   "971785f7-0f01-46cd-a3ce-af9ce6273d3d", // User Owner Id
   "entity_name", // Entity Name
    "9135e859-b053-4cfb-b701-d5f240b0aab1", // Entity Id
    // Set the permissions for the entity
   , new AccessLevel(Permission::READ, Permission::CREATE)),
    // User Acting As
   new UserAuth("b253a0e8-027b-463c-b87a-b18f09c99ddd")
   ]
 )
);
```

#### (Recommend) Setup entity granted on validate entity was granted to void sync issues.
```php
$config = new Config(true);
$config->setupOnValidateEntityWasGranted(function () {
    return [
        new EntityGranted($userOwnerId,
        new EntityReference($entityName, $entityId), AccessLevel::all())
    ];
});
Horus::getInstance()->setConfig($config);
```


### ⛔ Entity Restrictions

If you want to apply a restriction to an entity, you can use the next restriction classes:

* **MaxCountEntityRestriction**: Restricts the number of records that can be synchronized for an entity to specific user.
* **FilterEntityRestriction**: This restriction allows you to filter the records from an entity can be obtained given a set of
  parameters. This is useful when you want to limit the data that can be downloaded to client based on certain criteria.
* **LimitCountEntityParentRetrieveRestriction**: This restriction allows you to limit the number of parent records that can be retrieved by user owner for a specific entity.
* **LimitCountEntityChildrenRetrieveRestriction**: This restriction allows you to limit the number of children records that can be retrieved from a parent entity by user owner for a specific entity.
* **ExternalEntityFilterRestriction**: This restriction allows you to apply complex filtering logic to entities after they are retrieved from the database. It supports filtering based on custom callable functions and transforming specific entity parameters using value transformers. This is useful when you need advanced filtering that cannot be expressed through simple parameter filters.

#### Setup after initialization

By default, the setup is set the Config class in the Horus initialization, but if you want to change the restrictions after you can use the next code:

```php 
Horus::getInstance()->setEntityRestrictions([
    new MaxCountEntityRestriction("user_id","entity_name", maxCount: 10),
    new FilterEntityRestriction("entity_name", [
       new ParameterFilter("country", "CO")
    ]),
    new ExternalEntityFilterRestriction("entity_name", filterFunction: function (EntityData $entityData) {
        // Filter entities based on custom logic
        return $entityData->getData()["active"] === false; // true to filter out, false to keep
    }),
    new ExternalEntityFilterRestriction("entity_name", filterFunction: function (EntityData $entityData) {
        return false; // Don't filter any entity
    }, parameterValueTransformers: [
        new ParameterValueTransformer("name", function (EntityData $entityData, string $parameterName, mixed $value) {
            return strtoupper($value); // Transform the name to uppercase
        })
    ])
]);
```

### 🍒 Shared entities

If you want to share records from specific entities, you can use the method `setSharedEntities` or `setupOnSharedEntities` in the Horus instance like this:

```php
Horus::getInstance()->setSharedEntities([
    new EntityReference("entity_name", "entity_id")
]);
```

(Recommend) This way, only setup the shared entities when Horus needs it.

```php
Horus::getInstance()->setupOnSharedEntities(function(){
    return [new EntityReference("entity_name", "entity_id")];
});
```

### ✉️ Events

If you want to listen to events that occur during synchronization, you can use the following code:

#### Insert Event
```php
Event::listen("horus.sync.insert", function (string $entityName, array $data) {
    // Your code here
});
```

#### Update Event
```php
Event::listen("horus.sync.update", function (string $entityName, array $data) {
    // Your code here
});
```

#### Delete Event
```php
Event::listen("horus.sync.delete", function (string $entityName, array $data) {
    // Your code here
});
```