<?php

namespace AppTank\Horus\Illuminate\Transaction;

use AppTank\Horus\Core\Transaction\ITransactionHandler;
use AppTank\Horus\HorusContainer;
use Illuminate\Support\Facades\DB;

class EloquentTransactionHandler implements ITransactionHandler
{
    private const int ATTEMPTS = 5;

    function __construct(private readonly HorusContainer $container)
    {

    }

    function executeTransaction(callable $closure): mixed
    {
        $attempt = 1;
        for (; $attempt <= self::ATTEMPTS; $attempt++) {

            try {
                DB::connection($this->container->getConnectionName())->beginTransaction();
                $result = $closure();
                DB::connection($this->container->getConnectionName())->commit();
                return $result;
            } catch (\Exception $e) {
                report($e);
                DB::connection($this->container->getConnectionName())->rollBack();
                if ($attempt == self::ATTEMPTS) {
                    throw $e;
                }
            }
        }
        throw new \Exception();
    }
}