<?php

namespace AppTank\Horus\Core\Entity;

enum EntityType: string
{
    case EDITABLE = "editable";
    case LOOKUP = "lookup";
}