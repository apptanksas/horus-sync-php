<?php

namespace AppTank\Horus\Illuminate\Http;

use AppTank\Horus\Core\Auth\UserAuth;
use AppTank\Horus\Core\Exception\NotAuthorizedException;
use AppTank\Horus\Core\Exception\UserNotAuthenticatedException;
use AppTank\Horus\Core\Exception\UserNotAuthorizedException;
use AppTank\Horus\HorusContainer;
use Illuminate\Http\JsonResponse;

/**
 * Clase abstracta Controller
 *
 * Proporciona métodos generales para manejar respuestas en controladores y gestiona la autenticación
 * y autorización de usuarios. También ofrece un mecanismo de manejo de errores estándar para
 * controladores derivados.
 *
 * @author John Ospina
 * @year 2024
 */
abstract class Controller
{
    /**
     * Maneja una solicitud, ejecutando un callback y gestionando posibles excepciones.
     *
     * @param callable $callback Función que se ejecutará al manejar la solicitud.
     * @return JsonResponse Retorna una respuesta JSON con el resultado de la operación o un error.
     */
    function handle(callable $callback): JsonResponse
    {
        try {
            $this->validateUserActingAs();
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

    /**
     * Genera una respuesta exitosa con código de estado 200.
     *
     * @param array $data Datos a incluir en la respuesta.
     * @return JsonResponse Retorna una respuesta JSON con los datos y código 200.
     */
    function responseSuccess(array $data): JsonResponse
    {
        return $this->response($data, 200);
    }

    /**
     * Genera una respuesta con código de estado 202 (Aceptado).
     *
     * @param array $data Datos opcionales a incluir en la respuesta.
     * @return JsonResponse Retorna una respuesta JSON con los datos y código 202.
     */
    function responseAccepted(array $data = []): JsonResponse
    {
        return $this->response($data, 202);
    }

    /**
     * Genera una respuesta de error con código de estado 400 (Solicitud incorrecta).
     *
     * @param string $message Mensaje de error a incluir en la respuesta.
     * @return JsonResponse Retorna una respuesta JSON con un mensaje de error y código 400.
     */
    function responseBadRequest(string $message): JsonResponse
    {
        return $this->response(["message" => $message], 400);
    }

    /**
     * Genera una respuesta de error con código de estado 401 (No autorizado).
     *
     * @return JsonResponse Retorna una respuesta JSON con un mensaje de "No autorizado" y código 401.
     */
    function responseUnauthorized(): JsonResponse
    {
        return $this->response(["message" => "Unauthorized"], 401);
    }

    /**
     * Genera una respuesta de error con código de estado 500 (Error interno del servidor).
     *
     * @return JsonResponse Retorna una respuesta JSON con un mensaje de "Error interno del servidor" y código 500.
     */
    function responseServerError(): JsonResponse
    {
        return $this->response(["message" => "Internal server error"], 500);
    }

    /**
     * Genera una respuesta JSON con un código de estado específico.
     *
     * @param array $data Datos a incluir en la respuesta.
     * @param int $statusCode Código de estado HTTP de la respuesta.
     * @return JsonResponse Retorna una respuesta JSON con los datos y el código de estado especificado.
     */
    private function response(array $data, int $statusCode): JsonResponse
    {
        return JsonResponse::fromJsonString(json_encode($data), $statusCode);
    }

    /**
     * Obtiene el usuario autenticado actualmente.
     *
     * @return UserAuth Retorna la instancia de `UserAuth` del usuario autenticado.
     * @throws UserNotAuthenticatedException Si no hay un usuario autenticado.
     */
    protected function getUserAuthenticated(): UserAuth
    {
        return HorusContainer::getInstance()->getUserAuthenticated() ??
            throw new UserNotAuthenticatedException();
    }

    /**
     * Valida si el usuario autenticado tiene permiso para actuar como otro usuario.
     *
     * @throws UserNotAuthorizedException Si el usuario no tiene permiso para actuar como el usuario especificado.
     */
    private function validateUserActingAs(): void
    {
        $userAuth = HorusContainer::getInstance()->getUserAuthenticated();

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
}