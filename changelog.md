# Changelog

## 2024-08-13   

Se agrega soporte para el tipo de parametro de **_Enum_**.
```php
    SyncParameter::createEnum(name:"enum_param", values:["value1","value2","value3"], version:1);
```

Se agrega soporte para el tipo de parametro de **_UUID_**.
```php
    SyncParameter::createUUID(name:"param_name", version:1, isNullable:true);
```