<?php

namespace AppTank\Horus\Illuminate\Http\Controller\Data;

use AppTank\Horus\Application\Sync\SearchSyncDataStatus;
use AppTank\Horus\Core\Exception\ClientException;
use AppTank\Horus\Core\Repository\SyncJobRepository;
use AppTank\Horus\Illuminate\Http\Controller;
use Illuminate\Http\JsonResponse;

/**
 * @internal Class GetSyncDataJobStatusController
 *
 * This controller handles HTTP requests to get the status of a sync data job. It uses the `SearchSyncDataStatus` 
 * use case to search for the job and return its status information.
 *
 * @package AppTank\Horus\Illuminate\Http\Controller\Data
 *
 * @author John Ospina
 * Year: 2024
 */
class GetSyncDataJobStatusController extends Controller
{
    private readonly SearchSyncDataStatus $useCase;

    /**
     * Constructor for GetSyncDataJobStatusController.
     *
     * @param SyncJobRepository $syncJobRepository Repository for sync job operations.
     */
    function __construct(
        SyncJobRepository $syncJobRepository
    )
    {
        $this->useCase = new SearchSyncDataStatus($syncJobRepository);
    }

    /**
     * Handle the incoming request to get sync data job status.
     *
     * @param string $syncId The sync job ID to search for.
     * @return JsonResponse JSON response with the job status or error message.
     */
    function __invoke(string $syncId): JsonResponse
    {
        return $this->handle(function () use ($syncId) {

            if ($this->isNotUUID($syncId)) {
                return $this->responseBadRequest('Invalid sync ID, must be a valid UUID');
            }

            try {
                $result = $this->useCase->__invoke($syncId);
                return $this->responseSuccess($result);
            } catch (ClientException $e) {
                return $this->responseNotFound($e->getMessage());
            }
        });
    }
} 