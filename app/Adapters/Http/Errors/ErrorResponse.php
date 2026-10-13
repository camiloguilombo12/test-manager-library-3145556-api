<?php

namespace App\Adapters\Http\Errors;

use App\Application\Errors\BookHasOpenLoans;
use App\Application\Errors\InvalidCredentials;
use App\Application\Errors\InvalidDateRange;
use App\Application\Errors\IsbnTaken;
use App\Application\Errors\MemberHasOverdue;
use App\Application\Errors\MemberLimitExceeded;
use App\Application\Errors\ResourceNotFound;
use App\Application\Errors\UsernameTaken;
use App\Domain\Errors\BookNotAvailable;
use App\Domain\Errors\DuplicateBookInLoan;
use App\Domain\Errors\InvalidValue;
use App\Domain\Errors\ItemAlreadyReturned;
use App\Domain\Errors\LoanItemNotFound;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

/**
 * Traduce cualquier error al cuerpo único del contrato: { code, message, details } (RNF-4).
 * Es el único lugar donde un error del dominio o de la aplicación se convierte en código HTTP (architecture.md §5).
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

            // Errores de negocio (api-contract.md §2)
            $exception instanceof InvalidCredentials => self::make(401, 'INVALID_CREDENTIALS', $exception->getMessage()),
            $exception instanceof ResourceNotFound,
            $exception instanceof LoanItemNotFound => self::make(404, 'NOT_FOUND', $exception->getMessage()),
            $exception instanceof UsernameTaken => self::make(409, 'USERNAME_TAKEN', $exception->getMessage()),
            $exception instanceof IsbnTaken => self::make(409, 'ISBN_TAKEN', $exception->getMessage()),
            $exception instanceof BookHasOpenLoans => self::make(409, 'BOOK_HAS_OPEN_LOANS', $exception->getMessage()),
            $exception instanceof BookNotAvailable => self::make(409, 'BOOK_NOT_AVAILABLE', $exception->getMessage(), [
                ['field' => 'book_id', 'issue' => 'sin ejemplares'],
            ]),
            $exception instanceof MemberHasOverdue => self::make(409, 'MEMBER_HAS_OVERDUE', $exception->getMessage()),
            $exception instanceof ItemAlreadyReturned => self::make(409, 'ITEM_ALREADY_RETURNED', $exception->getMessage()),
            $exception instanceof MemberLimitExceeded => self::make(422, 'MEMBER_LIMIT_EXCEEDED', $exception->getMessage(), [
                ['field' => 'items', 'issue' => "sin devolver: {$exception->currentCount}"],
            ]),
            $exception instanceof InvalidDateRange => self::make(422, 'INVALID_DATE_RANGE', $exception->getMessage()),
            $exception instanceof DuplicateBookInLoan => self::make(422, 'DUPLICATE_BOOK_IN_LOAN', $exception->getMessage()),
            $exception instanceof InvalidValue => self::make(422, 'VALIDATION_ERROR', $exception->getMessage(), [
                ['field' => $exception->field, 'issue' => $exception->getMessage()],
            ]),

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
