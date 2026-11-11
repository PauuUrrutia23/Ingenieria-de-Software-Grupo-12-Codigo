<?php

namespace Tests\Feature;

use Tests\TestCase;

// RF02 y RNF18: la página de Términos y Política de Privacidad publica la versión 4 revisada por el equipo legal.
class TerminosVersion4Test extends TestCase
{
    public function test_la_pagina_de_terminos_publica_la_version_4_revisada()
    {
        $this->get('/terminos')->assertOk()
            ->assertSee('Versión 4 · 11 de noviembre de 2026')
            ->assertSeeInOrder(['Parte I — Términos y Condiciones de uso del sitio', 'Parte II — Política de Privacidad'])
            ->assertSee('El responsable de los datos personales que se reciben por este sitio es Guillermo Enrique Cáceres Vergara')
            ->assertSee('San Bernardo')
            ->assertDontSee('San bernardo')
            ->assertSee('Este correo aparece también en el pie de página del sitio.')
            ->assertSee('Responderemos en un plazo máximo de 30 días corridos.')
            ->assertDontSee('Las consultas se conservan mientras sean necesarias')
            ->assertDontSee('Ambos datos aparecen');
    }

    // RNF18: el pie de página muestra el correo de contacto con que se ejercen los derechos sobre los datos.
    public function test_el_pie_de_pagina_muestra_el_correo_de_contacto()
    {
        $html = (string) $this->get('/')->assertOk()->getContent();
        preg_match('#<footer\b.*?</footer>#s', $html, $pie);

        $this->assertStringContainsString('href="mailto:contacto@ingeconltda.cl"', $pie[0] ?? '');
        $this->assertStringContainsString('contacto@ingeconltda.cl</a>', $pie[0] ?? '');
    }
}
