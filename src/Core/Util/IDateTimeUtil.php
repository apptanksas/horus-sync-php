<?php

namespace AppTank\Horus\Core\Util;

interface IDateTimeUtil
{
    public function parseDatetime(string|int $datetime): \DateTime;

    public function getCurrent(): \DateTimeImmutable;
}