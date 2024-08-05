<?php

namespace AppTank\Horus\Domain\Entity;

enum SyncParameterType
{
    case PRIMARY_KEY_INTEGER;
    case PRIMARY_KEY_STRING;

    case INT;

    case FLOAT;

    case STRING;
    case TIMESTAMP;
    case RELATION_ONE_TO_MANY;

}
