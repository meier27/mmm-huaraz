<?php

namespace Database\Seeders;

use App\Enums\GameItemType;
use App\Models\GameItem;
use Illuminate\Database\Seeder;

/**
 * Ejemplos para los juegos bíblicos: hechos bíblicos conocidos, cada uno con
 * la cita donde se lee. Solo se cargan si aún no hay ningún contenido de juegos
 * (lo que se borre en Panel › Juegos no vuelve). Se editan o eliminan desde el panel.
 */
class GameSeeder extends Seeder
{
    /** [pregunta, respuesta correcta, [incorrectas], cita] */
    private const QUESTIONS = [
        ['¿Quién construyó el arca por mandato de Dios?', 'Noé', ['Moisés', 'Abraham', 'David'], 'Génesis 6:13-14'],
        ['¿Cuántos días y cuántas noches llovió sobre la tierra en el diluvio?', '40', ['7', '12', '100'], 'Génesis 7:12'],
        ['¿En qué día reposó Dios de toda la obra que había hecho?', 'El séptimo', ['El primero', 'El sexto', 'El tercero'], 'Génesis 2:2'],
        ['¿En qué ciudad nació Jesús?', 'Belén de Judea', ['Nazaret', 'Jerusalén', 'Capernaum'], 'Mateo 2:1'],
        ['¿Quién bautizó a Jesús en el Jordán?', 'Juan el Bautista', ['Pedro', 'Andrés', 'Elías'], 'Mateo 3:13'],
        ['¿Quién fue echado en el foso de los leones?', 'Daniel', ['José', 'Elías', 'Jeremías'], 'Daniel 6:16'],
        ['¿Con qué derribó David a Goliat?', 'Con una honda y una piedra', ['Con una espada', 'Con una lanza', 'Con un arco'], '1 Samuel 17:49-50'],
        ['¿Cuántos días y noches estuvo Jonás en el vientre del pez?', 'Tres', ['Uno', 'Siete', 'Cuarenta'], 'Jonás 1:17'],
        ['¿Quién negó a Jesús tres veces antes de que el gallo cantara?', 'Pedro', ['Judas', 'Juan', 'Tomás'], 'Lucas 22:61-62'],
        ['¿A quién dio Dios las dos tablas del testimonio en el monte Sinaí?', 'Moisés', ['Aarón', 'Josué', 'Samuel'], 'Éxodo 31:18'],
        ['¿Cuántos apóstoles escogió Jesús?', 'Doce', ['Siete', 'Diez', 'Setenta'], 'Lucas 6:13'],
        ['¿Camino a qué ciudad se le apareció el Señor a Saulo?', 'Damasco', ['Antioquía', 'Roma', 'Jericó'], 'Hechos 9:3'],
        ['¿En qué lugar convirtió Jesús el agua en vino?', 'Caná de Galilea', ['Betania', 'Nazaret', 'Jericó'], 'Juan 2:1-11'],
        ['¿Cuántos días llevaba Lázaro en el sepulcro cuando llegó Jesús?', 'Cuatro', ['Dos', 'Tres', 'Siete'], 'Juan 11:17'],
        ['¿Con cuántos panes alimentó Jesús a unos cinco mil hombres?', 'Cinco', ['Siete', 'Doce', 'Tres'], 'Mateo 14:17-21'],
        ['¿Cómo se llamaba la madre de Samuel?', 'Ana', ['Sara', 'Raquel', 'Rut'], '1 Samuel 1:20'],
    ];

    /** [palabra, pista, cita] */
    private const WORDS = [
        ['Noé', 'Construyó el arca de madera de gofer', 'Génesis 6:14'],
        ['Edén', 'Huerto donde Dios puso al hombre', 'Génesis 2:8'],
        ['Arca', 'En ella se salvaron Noé y su casa', 'Génesis 7:1'],
        ['Moisés', 'Recibió las tablas del testimonio', 'Éxodo 31:18'],
        ['Sinaí', 'Monte al que descendió Jehová', 'Éxodo 19:20'],
        ['Maná', 'Pan que Israel comió en el desierto', 'Éxodo 16:31'],
        ['Rut', 'Dijo: «tu Dios será mi Dios»', 'Rut 1:16'],
        ['David', 'Pastor que venció al gigante', '1 Samuel 17:50'],
        ['Goliat', 'Gigante filisteo de Gat', '1 Samuel 17:4'],
        ['Elías', 'Subió al cielo en un torbellino', '2 Reyes 2:11'],
        ['Daniel', 'Dios cerró la boca de los leones por él', 'Daniel 6:22'],
        ['Jonás', 'Estuvo tres días en el vientre del pez', 'Jonás 1:17'],
        ['Belén', 'Ciudad donde nació Jesús', 'Mateo 2:1'],
        ['Jesús', '«Llamarás su nombre…»', 'Mateo 1:21'],
        ['Jordán', 'Río donde Jesús fue bautizado', 'Mateo 3:13'],
        ['Pedro', 'Apóstol que anduvo sobre las aguas', 'Mateo 14:29'],
        ['Pablo', 'Saulo, también llamado…', 'Hechos 13:9'],
        ['Gracia', '«Por … sois salvos por la fe»', 'Efesios 2:8'],
        ['Amor', '«Dios es …»', '1 Juan 4:8'],
        ['Cruz', 'Cristo fue obediente hasta la muerte de…', 'Filipenses 2:8'],
    ];

    public function run(): void
    {
        if (GameItem::query()->exists()) {
            return;
        }

        foreach (self::QUESTIONS as $i => [$prompt, $answer, $options, $reference]) {
            GameItem::create(['type' => GameItemType::Question, 'prompt' => $prompt, 'answer' => $answer, 'options' => $options, 'reference' => $reference, 'is_published' => true, 'sort_order' => $i]);
        }

        foreach (self::WORDS as $i => [$answer, $prompt, $reference]) {
            GameItem::create(['type' => GameItemType::Word, 'prompt' => $prompt, 'answer' => $answer, 'reference' => $reference, 'is_published' => true, 'sort_order' => $i]);
        }
    }
}
