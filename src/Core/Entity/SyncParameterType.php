<?php

namespace AppTank\Horus\Core\Entity;

enum SyncParameterType: string
{
    case PRIMARY_KEY_INTEGER = "primary_key_integer";
    case PRIMARY_KEY_STRING = "primary_key_string";

    case INT = "int";

    case FLOAT = "float";

    case BOOLEAN = "boolean";

    case STRING = "string";
    case TIMESTAMP = "timestamp";
    case RELATION_ONE_TO_MANY = "relation_one_to_many";

}
