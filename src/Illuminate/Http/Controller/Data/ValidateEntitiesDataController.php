<?php

namespace AppTank\Horus\Illuminate\Http\Controller\Data;

use AppTank\Horus\Application\Validate\ValidateEntitiesData;
use AppTank\Horus\Core\Mapper\EntityMapper;
use AppTank\Horus\Core\Model\EntityHash;
use AppTank\Horus\Core\Model\EntityHashValidation;
use AppTank\Horus\Core\Repository\EntityAccessValidatorRepository;
use AppTank\Horus\Core\Repository\EntityRepository;
use AppTank\Horus\Illuminate\Http\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @internal Class ValidateEntitiesDataController
 *
 * This controller handles HTTP requests to validate the hash values of data entities. It uses the `ValidateEntitiesData`
 * use case to perform the validation based on the provided entity hash data. The controller processes incoming validation
 * requests and returns the results in a JSON response.
 *
 * @package AppTank\Horus\Illuminate\Http\Controller
 */
class ValidateEntitiesDataController extends Controller
{
    private readonly ValidateEntitiesData $useCase;

    /**
     * Constructor for ValidateEntitiesDataController.
     *
     * @param EntityRepository $repository Repository for entity-related operations.
     */
    function __construct(EntityRepository                $repository,
                         EntityAccessValidatorRepository $accessValidatorRepository,
                         EntityMapper                    $entityMapper)
    {
        $this->useCase = new ValidateEntitiesData($repository, $accessValidatorRepository, $entityMapper);
    }

    /**
     * Handle the incoming request to validate data entities.
     *
     * @param Request $request The HTTP request object.
     * @return JsonResponse JSON response with the validation results.
     */
    function __invoke(Request $request): JsonResponse
    {
        return $this->handle(function () use ($request) {
            $data = $request->all();

            return $this->responseSuccess(
                $this->parseResponse(
                    $this->useCase->__invoke(
                        $this->getUserAuthenticated(),
                        array_map(fn($item) => $this->parseEntityHash($item), $data)
                    )
                )
            );
        });
    }

    /**
     * Convert an array of item data into an EntityHash object.
     *
     * @param array $itemData Array containing entity name and hash.
     * @return EntityHash An EntityHash object representing the data.
     */
    private function parseEntityHash(array $itemData): EntityHash
    {
        return new EntityHash(
            entityName: $itemData["entity"],
            hash: $itemData["hash"]
        );
    }

    /**
     * Convert an array of EntityHashValidation objects into a structured array.
     *
     * @param EntityHashValidation[] $validations Array of validation results.
     * @return array Structured array of validation results.
     */
    private function parseResponse(array $validations): array
    {
        $output = [];

        foreach ($validations as $validation) {
            $output[] = [
                "entity" => $validation->entityName,
                "hash" => [
                    "expected" => $validation->hashValidation->expected,
                    "obtained" => $validation->hashValidation->obtained,
                    "matched" => $validation->hashValidation->matched
                ]
            ];
        }

        return $output;
    }
}
