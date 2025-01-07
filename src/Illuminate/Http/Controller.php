<?php

namespace AppTank\Horus\Illuminate\Http;

use AppTank\Horus\Core\Auth\UserAuth;
use AppTank\Horus\Core\Exception\ClientException;
use AppTank\Horus\Core\Exception\NotAuthorizedException;
use AppTank\Horus\Core\Exception\UserNotAuthenticatedException;
use AppTank\Horus\Core\Exception\UserNotAuthorizedException;
use AppTank\Horus\Horus;
use Illuminate\Http\JsonResponse;

/**
 * @internal Abstract Controller Class
 *
 * Provides general methods for handling responses in controllers and manages user authentication
 * and authorization. It also offers a standard error handling mechanism for derived controllers.
 *
 * @package AppTank\Horus\Illuminate\Http
 */
abstract class Controller
{

    const string ERROR_MESSAGE_ATTRIBUTE_INVALID = "Attribute <<%s>> is invalid.";

    /**
     * Handles a request by executing a callback and managing possible exceptions.
     *
     * @param callable $callback Function to be executed to handle the request.
     * @return JsonResponse Returns a JSON response with the result of the operation or an error.
     */
    function handle(callable $callback): JsonResponse
    {
        try {
            $this->validateUserActingAs();
            return $callback();
        } catch (ClientException $e) {
            return $this->responseBadRequest($e->getMessage());
        } catch (\PDOException $e) {
            return $this->responseBadRequest($this->parseError($e, $e->getMessage()));
        } catch (\ErrorException $e) {
            report($e);
            return $this->responseBadRequest("Error in request data");
        } catch (NotAuthorizedException) {
            return $this->responseUnauthorized();
        } catch (\Throwable $e) {
            report($e);
            return $this->responseServerError();
        }
    }

    /**
     * Generates a successful response with a 200 status code.
     *
     * @param array $data Data to include in the response.
     * @return JsonResponse Returns a JSON response with the data and a 200 status code.
     */
    function responseSuccess(array $data): JsonResponse
    {
        return $this->response($data, 200);
    }

    /**
     * Generates a response with a 202 (Accepted) status code.
     *
     * @param array $data Optional data to include in the response.
     * @return JsonResponse Returns a JSON response with the data and a 202 status code.
     */
    function responseAccepted(array $data = []): JsonResponse
    {
        return $this->response($data, 202);
    }

    /**
     * Generates an error response with a 400 (Bad Request) status code.
     *
     * @param string $message Error message to include in the response.
     * @return JsonResponse Returns a JSON response with an error message and a 400 status code.
     */
    function responseBadRequest(string $message): JsonResponse
    {
        return $this->response(["message" => $message], 400);
    }

    /**
     * Generates an error response with a 404 (Not Found) status code.
     *
     * @param string $message Error message to include in the response.
     * @return JsonResponse Returns a JSON response with an error message and a 404 status code.
     */
    function responseNotFound(string $message): JsonResponse
    {
        return $this->response(["message" => $message], 404);
    }

    /**
     * Generates an error response with a 401 (Unauthorized) status code.
     *
     * @return JsonResponse Returns a JSON response with a "Unauthorized" message and a 401 status code.
     */
    function responseUnauthorized(): JsonResponse
    {
        return $this->response(["message" => "Unauthorized"], 401);
    }

    /**
     * Generates an error response with a 500 (Internal Server Error) status code.
     *
     * @return JsonResponse Returns a JSON response with an "Internal server error" message and a 500 status code.
     */
    function responseServerError(): JsonResponse
    {
        return $this->response(["message" => "Internal server error"], 500);
    }

    /**
     * Generates a JSON response with a specific status code.
     *
     * @param array $data Data to include in the response.
     * @param int $statusCode HTTP status code for the response.
     * @return JsonResponse Returns a JSON response with the data and the specified status code.
     */
    private function response(array $data, int $statusCode): JsonResponse
    {
        if (empty($data)) {
            return new JsonResponse(null, $statusCode);
        }
        return JsonResponse::fromJsonString(json_encode($data), $statusCode);
    }

    /**
     * Retrieves the currently authenticated user.
     *
     * @return UserAuth Returns the `UserAuth` instance of the authenticated user.
     * @throws UserNotAuthenticatedException If no user is authenticated.
     */
    protected function getUserAuthenticated(): UserAuth
    {
        return Horus::getInstance()->getUserAuthenticated() ??
            throw new UserNotAuthenticatedException();
    }

    /**
     * Validates if the authenticated user has permission to act as another user.
     *
     * @throws UserNotAuthorizedException If the user is not permitted to act as the specified user.
     */
    private function validateUserActingAs(): void
    {
        $userAuth = Horus::getInstance()->getUserAuthenticated();

        if (is_null($userAuth)) {
            return;
        }

        if (is_null($userActingAs = $userAuth->userActingAs)) {
            return;
        }

        if (in_array($userActingAs->userId, $userAuth->getUserOwnersId())) {
            return;
        }

        throw new UserNotAuthorizedException("User is not authorized to act as this user[$userActingAs->userId]");
    }

    private function parseError(\Throwable $e, string $message): string
    {
        $matches = [];
        if (preg_match("/Data truncated for column '(.+)'/i", $message, $matches)) {
            return sprintf(self::ERROR_MESSAGE_ATTRIBUTE_INVALID, $matches[1]);
        }
        if (preg_match("/CHECK constraint failed: (.+) \(Connection/i", $message, $matches)) {
            return sprintf(self::ERROR_MESSAGE_ATTRIBUTE_INVALID, $matches[1]);
        }
        report($e);
        return "Error in request data: Some attribute is invalid.";
    }

    protected function isNotUUID(string $fileId): bool
    {
        return !preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', $fileId);
    }
}
