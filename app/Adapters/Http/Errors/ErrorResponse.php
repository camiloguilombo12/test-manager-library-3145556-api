<?php

namespace App\Adapters\Http\Errors;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

/**
 * Traduce cualquier error al cuerpo único del contrato: { code, message, details } (RNF-4).
 * Nunca incluye trazas, SQL ni rutas del servidor.
 */
final class ErrorResponse
{
    public static function fromThrowable(Throwable $exception): JsonResponse
    {
        return match (true) {
            $exception instanceof ValidationException => self::validation($exception),
            $exception instanceof AuthenticationException => self::make(401, 'UNAUTHENTICATED', 'Debes iniciar sesión para continuar.'),
            // Un archivo más grande de lo permitido es un error de validación (spec-review.md, A-09)
            $exception instanceof PostTooLargeException => self::make(422, 'VALIDATION_ERROR', 'El archivo supera el tamaño permitido.'),
            $exception instanceof HttpExceptionInterface => self::fromHttpStatus($exception->getStatusCode()),
            default => self::make(500, 'INTERNAL_ERROR', 'Ocurrió un error inesperado.'),
        };
    }

    public static function make(int $status, string $code, string $message, array $details = []): JsonResponse
    {
        return response()->json([
            'code' => $code,
            'message' => $message,
            'details' => $details,
        ], $status);
    }

    private static function validation(ValidationException $exception): JsonResponse
    {
        $details = [];

        foreach ($exception->errors() as $field => $issues) {
            foreach ($issues as $issue) {
                $details[] = ['field' => $field, 'issue' => $issue];
            }
        }

        return self::make(422, 'VALIDATION_ERROR', 'Hay campos inválidos en la petición.', $details);
    }

    private static function fromHttpStatus(int $status): JsonResponse
    {
        return match ($status) {
            401 => self::make(401, 'UNAUTHENTICATED', 'Debes iniciar sesión para continuar.'),
            403 => self::make(403, 'FORBIDDEN_ROLE', 'Tu rol no permite esta operación.'),
            404 => self::make(404, 'NOT_FOUND', 'El recurso no existe.'),
            405 => self::make(405, 'METHOD_NOT_ALLOWED', 'Método no permitido en esta ruta.'),
            default => self::make(500, 'INTERNAL_ERROR', 'Ocurrió un error inesperado.'),
        };
    }
}
