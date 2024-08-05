<?php

namespace AppTank\Horus\Illuminate\Http;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

abstract class Controller
{

    abstract function __invoke(Request $request): JsonResponse;

    function responseSuccess(array $data): JsonResponse
    {
        return $this->response($data, 200);
    }

    function responseBadRequest(array $data): JsonResponse
    {
        return $this->response($data, 400);
    }

    function responseServerError(): JsonResponse
    {
        return $this->response(["message" => "Internal server error"], 500);
    }

    private function response(array $data, int $statusCode): JsonResponse
    {
        return JsonResponse::fromJsonString(json_encode($data), $statusCode);
    }
}