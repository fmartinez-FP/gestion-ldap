<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Salvaguarda: los tests migran y vacían la base de datos, así que nunca
     * deben ejecutarse contra la BD real. Se comprueba antes de que arranque
     * cualquier trait (RefreshDatabase incluido).
     */
    public function createApplication()
    {
        $app = parent::createApplication();

        if ($app['config']->get('database.default') !== 'sqlite') {
            throw new \RuntimeException(
                'Los tests solo pueden ejecutarse con SQLite (revisa phpunit.xml). '
                . 'Conexión actual: ' . $app['config']->get('database.default')
            );
        }

        return $app;
    }
}
