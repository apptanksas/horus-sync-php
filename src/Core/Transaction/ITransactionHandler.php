<?php

namespace AppTank\Horus\Core\Transaction;

interface ITransactionHandler
{
    /**
     * Execute a transaction with support for multiple connections
     *
     * @param callable $closure
     * @return mixed
     */
    function executeTransaction(callable $closure): mixed;
}
