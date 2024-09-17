
<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/apptanksas/horus-sync-php/actions/workflows/unit_tests.yml/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>


# Horus Sync

Esta libreria permite definir una estructura de modelos datos sincronizables entre el servidor y un cliente. Debe usarse
con la libreria cliente de horus.

## Instalación

Esta libreria debe ser usada con la versión de Laravel 11 o superior.

```bash
composer require apptank/horus-sync
```

Agregar en su archivo de composer.json

```json
  "repositories": [
    {
      "type": "vcs",
      "url": "https://github.com/apptanksas/horus-sync-php.git"
    }
  ]
```

## Modo de uso

Todos los modelos sincronizables deben heredar de `AppTank\Horus\Illuminate\Database\EntitySynchronizable` definiendo
las propiedades que la componen.

Puede usar un comando de artisan para generar un modelo sincronizable por ti. Solo deben indicar la ruta en donde debes
guardar el modelo. El ejemplo
a continuación creara un modelo en App/Models/Sync/MyModelSync

```bash
php artisan horus:entity MyModelSync
```

```php
namespace App\Models\Sync;

use AppTank\Horus\Core\Entity\SyncParameter;
use AppTank\Horus\Illuminate\Database\EntitySynchronizable;

class MyModel extends EntitySynchronizable
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

## Inicialización

### Inicializar container

Es necesario inicializar el contenedor de Horus en el AppServiceProvider de laravel pasando como parametro un array con
el
mapa jeraquico de entidades.

* Puedes definir el nombre de la conexión a la base de datos y si se usara UUID en vez de Int como clave primaria de
  forma opcional.

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

### Ejecutar migraciones

Una vez que se ha inicializado el contenedor de Horus, es necesario ejecutar las migraciones de las tablas de
sincronización.

```bash
php artisan migrate
```

### Middlewares

Si quieres implementar middleware personalizados paras las rutas de sincronización, puedes hacerlo de la siguiente forma
en tu Service Provider:

```php

Horus::setMiddleware([MyMiddleware::class,'throttle:60,1']);

```

### Autenticación y permisos

El siguiente código muestra cómo se configura la autenticación de un usuario y los permisos asociados a las entidades.
Utilizando la clase **UserAuth**, se define un usuario autenticado junto con las entidades a las que tiene acceso y los permisos correspondientes.

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

# Rutas

### Esquema de migración

Devuelve un esquema de migración de las entidades sincronizables. Indicando los atributos de cada entidad
y sus relaciones. Ademas de la versión actual de la entidad y cual es la versión actual de cada atributo, para saber
si se debe migrar la base de datos del cliente a una nueva versión.

#### URL: GET

``` 
/sync/migration
 ```

#### Respuesta

<details>
  <summary>Haz clic aquí ver la respuesta</summary>

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

### Enviar datos a sincronizar

Este endpoint recibe un array de acciones a realizar en la base de datos. Cada acción debe tener un tipo de acción, la
entidad a la que se refiere y los datos a modificar. Ademas de la fecha en la que se realizo la acción.

#### URL: POST

``` 
/sync/queue/actions 
```

#### Request data de ejemplo

<details>
<summary>Haz click aqui para ver la request</summary>

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

## Obtener las acciones de sincronización realizas en orden cronoógico

#### URL: GET

``` 
/sync/queue/actions 
```

### Obtener los datos sincronizados de todas las entidades

#### URL: GET

``` 
/sync/data
```

#### Parametros opcionales
* **after (timestamp:int):** Filtra los datos que se han sincronizado después de la fecha  indicada.


## Obtener los datos sincronizados de una entidad especifica

#### URL: GET

``` 
/sync/{entity}
```
#### Parametros opcionales
* **after (timestamp:int):** Filtra los datos que se han sincronizado después de la fecha  indicada.
* **ids (array):** Filtra los datos que se han sincronizado con los ids indicados.


## Obtener la última acción sincronizada

#### URL: GET

``` 
/sync/queue/actions/last
```

## Obtener los hashes de los registros sincronizados de una entidad

#### URL: GET

``` 
/sync/entity/{entity}/hashes
```

#### Respuesta

<details>
  <summary>Haz clic aquí ver la respuesta de ejemplo</summary>

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


## Validar la integridad de los datos sincronizados

Este endpoint recibe un array de entidades y sus hashes, y devuelve un array con cada entidad y si el hash coincide con el
hash de la entidad en la base de datos significa que la integridad de los datos esta correcta.


#### URL: POST

``` 
/sync/validate/data
```

#### Request data de ejemplo

<details>
<summary>Haz click aqui para ver la request</summary>

```json
[
  {
    "entity": "parent_fake_entity",
    "hash": "2d971864ba0f14de5ad0be8f5ea6e0bc99685fdbb1f1495a8d8557ffdc572012"
  }
]
```

#### Respuesta de ejemplo

<details>
    <summary>Haz clic aquí ver la respuesta de ejemplo</summary>
    
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

## Validar el algoritmo de hashing de las entidades

Este endpoint recibe un array de atributos y un hash indicando el hash de salida del algoritmo del cliente para hashear esos atributos. 
El servidor realiza el mismo proceso y compara el hash obtenido con el hash recibido del cliente, para validar que el algoritmo de hashing sea el mismo.

#### URL: POST

``` 
/sync/validate/hashing
```

#### Request data de ejemplo
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

#### Respuesta de ejemplo

```json
{
  "expected": "17ffc52d4338881a6091ee80a4ef08db3901a86bf41636fa431a8ff5de3a6cf8",
  "obtained": "17ffc52d4338881a6091ee80a4ef08db3901a86bf41636fa431a8ff5de3a6cf8",
  "matched": true
}
```