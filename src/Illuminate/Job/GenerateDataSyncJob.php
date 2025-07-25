<?php

namespace AppTank\Horus\Illuminate\Job;

use AppTank\Horus\Core\Model\SyncJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateDataSyncJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    function __construct()
    {

    }

    public function handle(SyncJob $job): void
    {

    }
}