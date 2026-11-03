<?php

namespace Tests\Unit;

use App\Support\AlgoritmoHash;
use PHPUnit\Framework\TestCase;

// Dimensión Técnica 1.1 y RT-01: Argon2id si el PHP del hosting lo soporta; si no, bcrypt.
class AlgoritmoHashTest extends TestCase
{
    public function test_usa_argon2id_cuando_el_php_lo_soporta()
    {
        $this->assertSame('argon2id', AlgoritmoHash::elegir(['2y', 'argon2i', 'argon2id']));
    }

    public function test_usa_bcrypt_cuando_el_php_no_tiene_argon2id()
    {
        $this->assertSame('bcrypt', AlgoritmoHash::elegir(['2y']));
    }
}
