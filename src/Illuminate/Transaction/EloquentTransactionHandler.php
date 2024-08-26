<?php

namespace AppTank\Horus\Illuminate\Transaction;

use AppTank\Horus\Core\Transaction\ITransactionHandler;
use Illuminate\Support\Facades\DB;

/**
 * @internal Class EloquentTransactionHandler
 *
 * This class handles database transactions using Eloquent ORM. It provides a method to execute a transaction with retry logic.
 *
 */
class EloquentTransactionHandler implements ITransactionHandler
{
    private const int ATTEMPTS = 5;

    /**
     * EloquentTransactionHandler constructor.
     *
     * @param string|null $connectionName The name of the database connection to use. If null, the default connection is used.
     */
    function __construct(private readonly ?string $connectionName = null)
    {

    }

    /**
     * Executes a transaction with retry logic.
     *
     * This method attempts to execute the given closure within a database transaction. If an exception is thrown, it will
     * retry the transaction up to a maximum number of attempts (defined by the ATTEMPTS constant). If all attempts fail,
     * the exception will be re-thrown.
     *
     * @param callable $closure The closure to execute within the transaction.
     *
     * @return mixed The result of the closure execution.
     *
     * @throws \Exception If all attempts fail, the last caught exception is re-thrown.
     */
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
