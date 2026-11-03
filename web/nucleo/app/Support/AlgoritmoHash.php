<?php

namespace App\Support;

// Diagrama de Componentes: Argon2id «Entity» — Hashing (fallback bcrypt).
final class AlgoritmoHash
{
    // Recibe password_algos() del PHP en uso.
    public static function elegir(array $algoritmos): string
    {
        return in_array('argon2id', $algoritmos, true) ? 'argon2id' : 'bcrypt';
    }
}
