<?php

namespace App\Application\Ports;

/**
 * Verifica el token de acceso que llega en cada petición (CA-01.3).
 */
interface TokenVerifier
{
    /**
     * Devuelve los datos del token, o null si es inválido o está vencido.
     */
    public function verify(string $token): ?TokenClaims;
}
