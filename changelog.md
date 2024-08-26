# Changelog

## 2024-08-24
Se agrega soporte para UserActingAs en la configuracion del usuario autenticado. 
Esto para indicar si el usuario tiene permisos para actuar como otro usuario y acceder a la entidades
donde se le ha otoragado permisos.

```php
HorusContainer::getInstance()->setUserAuthenticated(
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

## 2024-08-19
- Se agrega el metodo de **_entityExists_** en el repository de _**EntityRepository**_ para validar si una entitidad exists y le pertenece a un usuario. 

## 2024-08-14
- Se definen dos tipos de entidades sincronizables:
  - **EntitySynchronizable**: Entidad que solamente se puede leer y no tiene tiene cuenta de usuario asociada. 
  - **LookupSynchronizable**: Entidad que permite insertar, editar y eliminar registros asociados a una cuenta de usuario. 
- Se refactoriza el metodo de **SyncParameter::createRelationOneOfMany**.
- Se agrega soporte para el tipo de relacion oneOfOne.
```php
    SyncParameter::createRelationOneOfOne(relatedClass:[ClassOne:class], version:1);
```
## 2024-08-13   

Se agrega soporte para el tipo de parametro de **_Enum_**.
```php
    SyncParameter::createEnum(name:"enum_param", values:["value1","value2","value3"], version:1);
```

Se agrega soporte para el tipo de parametro de **_UUID_**.
```php
    SyncParameter::createUUID(name:"param_name", version:1, isNullable:true);
```