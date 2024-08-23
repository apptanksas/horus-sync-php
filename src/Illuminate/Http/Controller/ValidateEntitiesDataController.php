<?php

namespace AppTank\Horus\Illuminate\Http\Controller;

use AppTank\Horus\Application\Validate\ValidateEntitiesData;
use AppTank\Horus\Core\Model\EntityHash;
use AppTank\Horus\Core\Model\EntityHashValidation;
use AppTank\Horus\Core\Repository\EntityRepository;
use AppTank\Horus\Illuminate\Http\Controller;
use Illuminate\Http\Request;

class ValidateEntitiesDataController extends Controller
{

    private readonly ValidateEntitiesData $useCase;

    function __construct(EntityRepository $repository)
    {
        $this->useCase = new ValidateEntitiesData($repository);
    }

    function __invoke(Request $request)
    {
        return $this->handle(function () use ($request) {
            $data = $request->all();

            return $this->responseSuccess(
                $this->parseResponse(
                    $this->useCase->__invoke(
                        $this->getUserAuthenticated(), array_map(fn($item) => $this->parseEntityHash($item), $data)
                    ))
            );
        });
    }

    private function parseEntityHash(array $itemData): EntityHash
    {
        return new EntityHash(
            entityName: $itemData["entity"],
            hash: $itemData["hash"]
        );
    }

    /**
     * @param EntityHashValidation[] $validations
     * @return array
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