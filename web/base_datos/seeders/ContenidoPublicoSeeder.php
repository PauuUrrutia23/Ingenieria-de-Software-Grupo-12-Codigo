<?php

namespace Database\Seeders;

use App\Models\Administrador;
use App\Models\Contenido;
use Illuminate\Database\Seeder;

class ContenidoPublicoSeeder extends Seeder
{
    public function run(): void
    {
        $admin = Administrador::first();
        if (!$admin) {
            return;
        }

        $preguntas = [
            [
                '¿Qué madera utilizan en sus productos?',
                'Pino radiata con tratamiento de impregnación Vacío-Presión. El preservante penetra en la madera y la protege contra hongos e insectos, lo que alarga su vida útil en uso estructural y a la intemperie.',
            ],
            [
                '¿Qué son los conectores metálicos y cuándo se usan?',
                'Son placas y piezas de acero galvanizado que unen cerchas, entramados de muro y secciones prefabricadas. Se dimensionan según el cálculo estructural de cada proyecto, no se despachan como artículo suelto.',
            ],
            [
                '¿Trabajan con viviendas industrializadas y proyectos DS19?',
                'Sí. Fabricamos panelería y volumetría prefabricada para conjuntos habitacionales, incluida la línea DS19, con montaje en terreno coordinado con la constructora.',
            ],
            [
                '¿Hacen despachos fuera de la Región Metropolitana?',
                'Sí. Tenemos obras entregadas en las regiones Metropolitana, del Maule, del Biobío y de La Araucanía. El plazo y el costo de traslado se confirman al cotizar según destino y volumen.',
            ],
            [
                '¿Qué normativa cumplen sus productos?',
                'La madera estructural se rige por NCh 1198 y los conectores se especifican bajo criterios ANSI/TPI. Las certificaciones vigentes y sus organismos están publicadas en la sección Certificaciones de este sitio.',
            ],
            [
                '¿Cómo solicito una cotización?',
                'Complete el formulario de contacto con los datos de su proyecto. Recibirá un correo de confirmación con su número de seguimiento y el equipo comercial revisará el requerimiento para responderle.',
            ],
        ];

        foreach ($preguntas as $indice => [$pregunta, $respuesta]) {
            Contenido::firstOrCreate(
                ['seccion' => 'faq', 'titulo' => $pregunta],
                [
                    'cuerpo' => $respuesta,
                    'orden' => $indice + 1,
                    'activo' => true,
                    'id_admin' => $admin->id_admin,
                ]
            );
        }

        // titulo = quién opina, cuerpo = testimonio
        $opiniones = [
            [
                'Jefe de obra · Constructora habitacional, Región Metropolitana',
                'La panelería llegó dimensionada y marcada, así que el montaje en terreno avanzó sin ajustes improvisados.',
            ],
            [
                'Gerente de operaciones · Agroindustria, Región del Biobío',
                'Necesitábamos un galpón de gran luz y las cerchas respondieron. La estructura pasó su primer invierno sin observaciones.',
            ],
            [
                'Encargado de montaje · Construcción industrial, Región del Maule',
                'El acompañamiento técnico durante el diseño nos permitió resolver las uniones antes de llegar a obra, no encima de ella.',
            ],
            [
                'Dirección técnica · Proyecto DS19, Región de La Araucanía',
                'Valoramos tener en un mismo proveedor la madera impregnada y los conectores: simplificó la coordinación y las revisiones.',
            ],
            [
                'Administrador de contrato · Obra pública, Región Metropolitana',
                'Los despachos llegaron partida por partida según lo acordado, lo que nos permitió cerrar el cronograma sin reprogramar el montaje.',
            ],
            [
                'Socio fundador · Constructora de terminaciones, Región del Biobío',
                'La escalera prefabricada llegó lista para instalar y las fijaciones venían consideradas en el mismo despacho.',
            ],
        ];

        foreach ($opiniones as $indice => [$autor, $testimonio]) {
            Contenido::firstOrCreate(
                ['seccion' => 'opiniones', 'titulo' => $autor],
                [
                    'cuerpo' => $testimonio,
                    'orden' => $indice + 1,
                    'activo' => true,
                    'id_admin' => $admin->id_admin,
                ]
            );
        }
    }
}
