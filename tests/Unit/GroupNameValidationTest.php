<?php

namespace Tests\Unit;

use App\Services\LdapService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class GroupNameValidationTest extends TestCase
{
    #[DataProvider('nombresValidos')]
    public function test_acepta_nombres_validos(string $cn): void
    {
        $this->assertTrue(LdapService::isValidGroupName($cn));
    }

    #[DataProvider('nombresInvalidos')]
    public function test_rechaza_nombres_invalidos(string $cn): void
    {
        $this->assertFalse(LdapService::isValidGroupName($cn));
    }

    public static function nombresValidos(): array
    {
        return [
            'minúsculas'        => ['admins'],
            'mayúsculas'        => ['Wekan'],
            'con guion'         => ['admin-global'],
            'con guion bajo'    => ['admin_global'],
            'con números'       => ['grupo2'],
            'solo un carácter'  => ['a'],
            '64 caracteres'     => [str_repeat('a', 64)],
        ];
    }

    public static function nombresInvalidos(): array
    {
        return [
            'vacío'                  => [''],
            'espacio'                => ['mis admins'],
            'punto'                  => ['a.b'],
            'coma (inyección de DN)' => ['admins,ou=users'],
            'igual (inyección de DN)' => ['cn=admins'],
            'asterisco (filtro)'     => ['*'],
            'paréntesis (filtro)'    => ['a)(cn=*'],
            'barra'                  => ['a/b'],
            'tilde'                  => ['administración'],
            'ñ'                      => ['niño'],
            'salto de línea final'   => ["admins\n"],
            '65 caracteres'          => [str_repeat('a', 65)],
        ];
    }

    public function test_valida_uids(): void
    {
        $this->assertTrue(LdapService::isValidUid('admin'));
        $this->assertTrue(LdapService::isValidUid('j.garcia-2'));
        $this->assertFalse(LdapService::isValidUid('a b'));
        $this->assertFalse(LdapService::isValidUid('a,ou=x'));
        $this->assertFalse(LdapService::isValidUid('*'));
        $this->assertFalse(LdapService::isValidUid(''));
    }
}
