<?php

namespace AppTank\Horus\Core\File;

use AppTank\Horus\Core\Auth\UserAuth;
use AppTank\Horus\Core\Entity\EntityReference;

interface IFileReferenceValidator
{
    function validate(UserAuth $userAuth, string $referenceFile, EntityReference $entityReference): void;
}