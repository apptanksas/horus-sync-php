<?php

namespace AppTank\Horus\Illuminate\Console;

use AppTank\Horus\Core\File\IFileHandler;
use AppTank\Horus\Horus;
use Illuminate\Console\Command;

class PruneFilesUploadedCommand extends Command
{
    protected $name = "horus:prune";


    protected $description = "Prune files uploaded that are no longer needed.";

    private IFileHandler $fileHandler;

    public function __construct()
    {
        parent::__construct();
        $this->fileHandler = Horus::getFileHandler();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {

    }

}