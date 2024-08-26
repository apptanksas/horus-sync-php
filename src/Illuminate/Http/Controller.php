<?php

namespace AppTank\Horus\Illuminate\Http;

use AppTank\Horus\Core\Auth\UserAuth;
use AppTank\Horus\Core\Exception\NotAuthorizedException;
use AppTank\Horus\Core\Exception\UserNotAuthenticatedException;
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
        } catch (NotAuthorizedException) {
            return $this->responseUnauthorized();
        } catch (\Throwable $e) {
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

    protected function getUserAuthenticated(): UserAuth
    {
        return HorusContainer::getInstance()->getUserAuthenticated() ??
            throw new UserNotAuthenticatedException();
    }
}