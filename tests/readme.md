# Horus Sync

Esta libreria permite definir una estructura de modelos datos sincronizables entre el servidor y un cliente. Debe usarse
con la libreria cliente de horus.

## Instalación

Esta libreria debe ser usada con la versión de Laravel 11 o superior.

```bash
composer require apptank/horus-sync
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
        HorusContainer::initialize([
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

### Middleware


# Rutas
