# Changelog

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