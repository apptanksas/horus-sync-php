<?php

namespace AppTank\Horus\Illuminate\Http\Controller\Data;

use AppTank\Horus\Application\Sync\StartGenerateSyncDataJob;
use AppTank\Horus\Core\Bus\IJobDispatcher;
use AppTank\Horus\Core\Repository\SyncJobRepository;
use AppTank\Horus\Illuminate\Http\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @internal Class PostStartSyncDataJobController
 *
 * This controller handles HTTP requests to start a sync data generation job. It uses the `StartGenerateSyncDataJob` 
 * use case to initiate the job and save it to the repository.
 *
 * @package AppTank\Horus\Illuminate\Http\Controller\Data
 *
 * @author John Ospina
 * Year: 2024
 */
class PostStartSyncDataJobController extends Controller
{
    private readonly StartGenerateSyncDataJob $useCase;

    /**
     * Constructor for PostStartSyncDataJobController.
     *
     * @param SyncJobRepository $syncJobRepository Repository for sync job operations.
     * @param IJobDispatcher    $jobDispatcher Job dispatcher for handling background jobs.
     */
    function __construct(
        SyncJobRepository $syncJobRepository,
        IJobDispatcher    $jobDispatcher
    )
    {
        $this->useCase = new StartGenerateSyncDataJob($syncJobRepository, $jobDispatcher);
    }

    /**
     * Handle the incoming request to start a sync data generation job.
     *
     * @param Request $request The HTTP request object.
     * @return JsonResponse JSON response with the job result or error message.
     */
    function __invoke(Request $request): JsonResponse
    {
        return $this->handle(function () use ($request) {

            $syncId = $request->input('sync_id');

            if (!$syncId) {
                return $this->responseBadRequest('No sync ID was provided');
            }

            if ($this->isNotUUID($syncId)) {
                return $this->responseBadRequest('Invalid sync ID, must be a valid UUID');
            }

            $this->useCase->__invoke(
                $this->getUserAuthenticated(),
                $syncId
            );

            return $this->responseAccepted([
                'sync_id' => $syncId
            ]);
        });
    }
} 