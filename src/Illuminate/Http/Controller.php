<?php

namespace AppTank\Horus\Illuminate\Http;

use AppTank\Horus\HorusContainer;
use Illuminate\Http\JsonResponse;

abstract class Controller
{

    function handle(callable $callback): JsonResponse
    {
        try {
            return $callback();
        } catch (\PDOException $e) {
            return $this->responseBadRequest("Error in request data: Entity attributes are invalid");
        } catch (\ErrorException $e) {
            return $this->responseBadRequest("Error in request data");
        } catch (\Throwable $e) {
            if ($e->getCode() == 401) {
                return $this->responseUnauthorized();
            }
            report($e);
            return $this->responseServerError();
        }
    }

    function responseSuccess(array $data): JsonResponse
    {
        return $this->response($data, 200);
    }

    function responseAccepted(array $data = []): JsonResponse
    {
        return $this->response($data, 202);
    }

    function responseBadRequest(string $message): JsonResponse
    {
        return $this->response(["message" => $message], 400);
    }

    function responseUnauthorized(): JsonResponse
    {
        return $this->response(["message" => "Unauthorized"], 401);
    }

    function responseServerError(): JsonResponse
    {
        return $this->response(["message" => "Internal server error"], 500);
    }

    private function response(array $data, int $statusCode): JsonResponse
    {
        return JsonResponse::fromJsonString(json_encode($data), $statusCode);
    }

    protected function getAuthenticatedUserId(): string|int
    {
        return HorusContainer::getInstance()->getAuthenticatedUserId() ??
            throw new \DomainException("User not authenticated", 401);
    }
}