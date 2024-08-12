<?php

namespace AppTank\Horus\Illuminate\Http\Controller;

use AppTank\Horus\Core\Hasher;
use AppTank\Horus\Illuminate\Http\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ValidateHashingController extends Controller
{
    function __invoke(Request $request): JsonResponse
    {
        return $this->handle(function () use ($request) {

            $data = $request->get("data");

            if (empty($data))
                return $this->responseBadRequest("Data is required");

            $hashExpected = $request->get("hash");

            $hashObtained = Hasher::hash($data);

            return $this->responseSuccess([
                "expected" => $hashExpected,
                "obtained" => $hashObtained,
                "matched" => $hashExpected == $hashObtained
            ]);
        });
    }
}