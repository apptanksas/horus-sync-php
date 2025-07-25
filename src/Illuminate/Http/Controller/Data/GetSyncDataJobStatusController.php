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
 * Controller for handling requests to get the status of a synchronization job.
 *
 * @package AppTank\Horus\Illuminate\Http\Controller\Data
 *
 * @author John Ospina
 * Year: 2025
 */
class GetSyncDataJobStatusController extends Controller
{
    private readonly SearchSyncDataStatus $useCase;

    /**
     * Constructor for GetSyncDataJobStatusController.
     *
     * @param SyncJobRepository $syncJobRepository Repository for sync job operations.
     */
    function __construct(SyncJobRepository $syncJobRepository)
    {
        $this->useCase = new SearchSyncDataStatus($syncJobRepository);
    }

    /**
     * Handle the request to get sync job status.
     *
     * @param string $syncId The synchronization job ID.
     * @return JsonResponse JSON response with sync job status or error.
     */
    function __invoke(string $syncId): JsonResponse
    {
        return $this->handle(function () use ($syncId) {

            if ($this->isNotUUID($syncId)) {
                return $this->responseBadRequest('Invalid sync ID, must be a valid UUID');
            }

            try {
                return $this->responseSuccess($this->useCase->__invoke($syncId));
            } catch (ClientException $e) {
                return $this->responseNotFound($e->getMessage());
            }
        });
    }
} 