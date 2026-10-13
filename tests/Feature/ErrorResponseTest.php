<?php

namespace Tests\Feature;

use App\Adapters\Http\Errors\ErrorResponse;
use App\Application\Errors\InvalidCredentials;
use App\Application\Errors\InvalidDateRange;
use App\Application\Errors\IsbnTaken;
use App\Application\Errors\MemberHasOverdue;
use App\Application\Errors\MemberLimitExceeded;
use App\Application\Errors\ResourceNotFound;
use App\Domain\Errors\BookNotAvailable;
use App\Domain\Errors\DuplicateBookInLoan;
use App\Domain\Errors\InvalidValue;
use App\Domain\Errors\ItemAlreadyReturned;
use App\Domain\Errors\StockOutOfRange;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;
use Throwable;

class ErrorResponseTest extends TestCase
{
    /**
     * @return array<string, array{Throwable, int, string}>
     */
    public static function businessErrors(): array
    {
        return [
            'credenciales inválidas' => [new InvalidCredentials(), 401, 'INVALID_CREDENTIALS'],
            'no encontrado' => [new ResourceNotFound(), 404, 'NOT_FOUND'],
            'isbn repetido' => [new IsbnTaken(), 409, 'ISBN_TAKEN'],
            'sin ejemplares' => [new BookNotAvailable('book-1', 'Libro'), 409, 'BOOK_NOT_AVAILABLE'],
            'socio con vencidos' => [new MemberHasOverdue(), 409, 'MEMBER_HAS_OVERDUE'],
            'ya devuelto' => [new ItemAlreadyReturned('item-1'), 409, 'ITEM_ALREADY_RETURNED'],
            'límite del socio' => [new MemberLimitExceeded(2), 422, 'MEMBER_LIMIT_EXCEEDED'],
            'libro repetido' => [new DuplicateBookInLoan('book-1'), 422, 'DUPLICATE_BOOK_IN_LOAN'],
            'rango invertido' => [new InvalidDateRange('La fecha final no puede ser anterior a la inicial.'), 422, 'INVALID_DATE_RANGE'],
            'valor inválido' => [new InvalidValue('isbn', 'El ISBN debe tener 13 dígitos.'), 422, 'VALIDATION_ERROR'],
            'defecto interno' => [new StockOutOfRange('detalle interno'), 500, 'INTERNAL_ERROR'],
        ];
    }

    #[DataProvider('businessErrors')]
    public function test_business_error_maps_to_contract_code(Throwable $error, int $status, string $code): void
    {
        $response = ErrorResponse::fromThrowable($error);
        $body = $response->getData(true);

        $this->assertSame($status, $response->getStatusCode());
        $this->assertSame($code, $body['code']);
        $this->assertSame(['code', 'message', 'details'], array_keys($body));
    }

    public function test_internal_error_never_exposes_details(): void
    {
        $body = ErrorResponse::fromThrowable(new StockOutOfRange('detalle interno'))->getData(true);

        $this->assertSame('Ocurrió un error inesperado.', $body['message']);
        $this->assertSame([], $body['details']);
    }
}
