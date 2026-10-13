<?php

namespace App\Domain\Entities;

use App\Domain\Errors\BookNotAvailable;
use App\Domain\Errors\InvalidValue;
use App\Domain\Errors\StockOutOfRange;
use App\Domain\ValueObjects\Isbn;
use App\Domain\ValueObjects\Quantity;
use DateTimeImmutable;

/**
 * Raíz del agregado libro. Defiende: disponibles entre 0 y total, total >= 1 y título no vacío (architecture.md §3).
 */
final class Book
{
    private function __construct(
        public readonly string $id,
        private string $title,
        private string $author,
        public readonly Isbn $isbn,
        private string $categoryId,
        private int $totalCopies,
        private int $availableCopies,
        private ?string $coverKey,
        private ?DateTimeImmutable $deletedAt,
        private int $version,
    ) {}

    /**
     * Libro nuevo: los disponibles arrancan iguales al total (CA-03.1).
     */
    public static function create(string $id, string $title, string $author, Isbn $isbn, string $categoryId, Quantity $totalCopies): self
    {
        return new self(
            $id,
            self::requireText('title', $title, 'El título no puede estar vacío.'),
            self::requireText('author', $author, 'El autor no puede estar vacío.'),
            $isbn,
            $categoryId,
            $totalCopies->value,
            $totalCopies->value,
            null,
            null,
            1,
        );
    }

    /**
     * Reconstruye un libro guardado, tal como lo entrega la persistencia.
     */
    public static function restore(
        string $id,
        string $title,
        string $author,
        Isbn $isbn,
        string $categoryId,
        int $totalCopies,
        int $availableCopies,
        ?string $coverKey,
        ?DateTimeImmutable $deletedAt,
        int $version,
    ): self {
        return new self($id, $title, $author, $isbn, $categoryId, $totalCopies, $availableCopies, $coverKey, $deletedAt, $version);
    }

    /**
     * Descuenta un ejemplar al prestar; nunca deja disponibles por debajo de 0 (CA-06.3).
     */
    public function withdraw(): void
    {
        if ($this->availableCopies === 0) {
            throw new BookNotAvailable($this->id, $this->title);
        }

        $this->availableCopies--;
    }

    /**
     * Suma un ejemplar al devolver; nunca deja disponibles por encima del total (CA-07.3).
     */
    public function restock(): void
    {
        if ($this->availableCopies === $this->totalCopies) {
            throw new StockOutOfRange("El libro {$this->id} ya tiene todos sus ejemplares disponibles.");
        }

        $this->availableCopies++;
    }

    /**
     * Cambiar el total ajusta los disponibles en la misma diferencia y nunca los deja negativos (CA-03.3).
     */
    public function changeTotalCopies(Quantity $newTotal): void
    {
        $newAvailable = $this->availableCopies + ($newTotal->value - $this->totalCopies);

        if ($newAvailable < 0) {
            throw new InvalidValue('total_copies', 'El nuevo total deja menos ejemplares de los que están prestados.');
        }

        $this->totalCopies = $newTotal->value;
        $this->availableCopies = $newAvailable;
    }

    /**
     * Reemplaza los datos editables del libro (PUT /books/{id}); el ISBN no se cambia (spec-review.md A-10).
     */
    public function updateDetails(string $title, string $author, string $categoryId): void
    {
        $this->title = self::requireText('title', $title, 'El título no puede estar vacío.');
        $this->author = self::requireText('author', $author, 'El autor no puede estar vacío.');
        $this->categoryId = $categoryId;
    }

    /**
     * Baja lógica (ADR-003). Quien llama ya comprobó que no tiene préstamos abiertos (CA-03.5).
     */
    public function markAsDeleted(DateTimeImmutable $at): void
    {
        $this->deletedAt ??= $at;
    }

    public function isActive(): bool
    {
        return $this->deletedAt === null;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function author(): string
    {
        return $this->author;
    }

    public function categoryId(): string
    {
        return $this->categoryId;
    }

    public function totalCopies(): int
    {
        return $this->totalCopies;
    }

    public function availableCopies(): int
    {
        return $this->availableCopies;
    }

    public function coverKey(): ?string
    {
        return $this->coverKey;
    }

    public function deletedAt(): ?DateTimeImmutable
    {
        return $this->deletedAt;
    }

    /**
     * Versión leída de la base; la persistencia la usa para la concurrencia optimista (ADR-002).
     */
    public function version(): int
    {
        return $this->version;
    }

    private static function requireText(string $field, string $value, string $message): string
    {
        $trimmed = trim($value);

        if ($trimmed === '') {
            throw new InvalidValue($field, $message);
        }

        return $trimmed;
    }
}
