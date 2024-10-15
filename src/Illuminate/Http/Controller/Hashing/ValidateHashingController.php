<?php

namespace AppTank\Horus\Illuminate\Http\Controller\Hashing;

use AppTank\Horus\Core\Hasher;
use AppTank\Horus\Illuminate\Http\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @internal Class ValidateHashingController
 *
 * This controller handles HTTP requests to validate data hashing. It calculates the hash of the provided data using
 * the `Hasher` class and compares it with the expected hash provided in the request. It returns a JSON response indicating
 * whether the calculated hash matches the expected hash.
 *
 * @package AppTank\Horus\Illuminate\Http\Controller
 */
class ValidateHashingController extends Controller
{
    /**
     * Handle the incoming request to validate hashing of data.
     *
     * @param Request $request The HTTP request object.
     * @return JsonResponse JSON response with the result of the hash validation.
     */
    function __invoke(Request $request): JsonResponse
    {
        return $this->handle(function () use ($request) {

            $data = $request->get("data");

            if (empty($data)) {
                return $this->responseBadRequest("Data is required");
            }

            $hashExpected = $request->get("hash");

            $hashObtained = Hasher::hash($data);

            return $this->responseSuccess([
                "expected" => $hashExpected,
                "obtained" => $hashObtained,
                "matched" => $hashExpected === $hashObtained
            ]);
        });
    }
}
