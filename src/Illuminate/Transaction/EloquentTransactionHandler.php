<?php

namespace AppTank\Horus\Illuminate\Transaction;

use AppTank\Horus\Core\Transaction\ITransactionHandler;
use Illuminate\Support\Facades\DB;

class EloquentTransactionHandler implements ITransactionHandler
{
    private const int ATTEMPTS = 5;

    function __construct(private readonly ?string $connectionName = null)
    {

    }

    function executeTransaction(callable $closure): mixed
    {
        $attempt = 1;
        for (; $attempt <= self::ATTEMPTS; $attempt++) {

            try {
                DB::connection($this->connectionName)->beginTransaction();
                $result = $closure();
                DB::connection($this->connectionName)->commit();
                return $result;
            } catch (\Exception $e) {
                report($e);
                DB::connection($this->connectionName)->rollBack();
                if ($attempt == self::ATTEMPTS) {
                    throw $e;
                }
            }
        }
        throw new \Exception();
    }
}