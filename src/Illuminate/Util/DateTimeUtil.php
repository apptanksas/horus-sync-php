<?php

namespace AppTank\Horus\Illuminate\Util;

use AppTank\Horus\Core\Util\IDateTimeUtil;
use Carbon\Carbon;

class DateTimeUtil implements IDateTimeUtil
{

    public function parseDatetime(int|string $datetime): \DateTime
    {
        // Validate if datetime is a timestamp in milliseconds
        if (is_int($datetime) && strlen($datetime) > 10) {
            return Carbon::createFromTimestampMsUTC($datetime);
        }

        // Validate if datetime is a timestamp in seconds
        if (is_int($datetime)) {
            return Carbon::createFromTimestampUTC($datetime);
        }

        return Carbon::parse($datetime);
    }

    public function getCurrent(): \DateTimeImmutable
    {
        return Carbon::now("UTC")->toDateTimeImmutable();
    }
}