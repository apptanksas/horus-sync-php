<?php

namespace AppTank\Horus\Core\Transaction;

/**
 * @internal Interface ITransactionHandler
 *
 * Defines a contract for handling transactions, with support for multiple database connections.
 * The implementing class should provide the ability to execute a transaction within a closure.
 *
 * @package AppTank\Horus\Core\Transaction
 */
interface ITransactionHandler
{
    /**
     * Executes a transaction with support for multiple connections.
     *
     * This method accepts a closure that contains the operations to be executed within the transaction.
     * It ensures that all operations are completed successfully, or rolls back the transaction in case of failure.
     *
     * @param callable $closure The closure that contains the operations to be executed within the transaction.
     * @return mixed The result of the closure execution.
     *
     * @throws \Exception If an error occurs during the transaction.
     */
    function executeTransaction(callable $closure): mixed;
}
