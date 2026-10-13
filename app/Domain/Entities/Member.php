<?php

namespace App\Domain\Entities;

use App\Domain\Errors\InvalidValue;

/**
 * Persona que pide libros prestados. No inicia sesión y no se elimina (CA-05.3).
 */
final class Member
{
    private function __construct(
        public readonly string $id,
        public readonly string $fullName,
        public readonly string $documentNumber,
    ) {}

    public static function create(string $id, string $fullName, string $documentNumber): self
    {
        $name = trim($fullName);
        $document = trim($documentNumber);

        if ($name === '') {
            throw new InvalidValue('full_name', 'El nombre del socio no puede estar vacío.');
        }

        if ($document === '') {
            throw new InvalidValue('document_number', 'El documento del socio es obligatorio.');
        }

        return new self($id, $name, $document);
    }
}
