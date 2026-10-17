<?php

/*
|--------------------------------------------------------------------------
| Biblia (API de esbiblia.net) y Bethel Televisión
|--------------------------------------------------------------------------
|
| Verificado el 15/10/2026:
| - esbiblia.net/api ofrece las versiones "rvr" (Reina-Valera 1909, dominio
|   público), "rva" (Reina-Valera Actualizada, derechos reservados) y "JER"
|   (Biblia de Jerusalén, derechos reservados). Su documentación menciona la
|   RVR1960, pero la API NO la sirve. No publica términos de uso ni límites.
| - Canal oficial de YouTube de Bethel Televisión: @Betheloficial
|   (enlazado desde betheltv.tv y pe.mmmoficial.org).
|
| El catálogo de libros (identificadores y nombres tal como los devuelve la
| API) y el número de capítulos del canon de 66 libros son datos estables y
| se guardan aquí para no depender de la red en cada página.
|
*/

return [
    'base_url' => env('BIBLE_API_URL', 'https://esbiblia.net/api'),
    'timeout' => (int) env('BIBLE_API_TIMEOUT', 8),
    'default_version' => 'rvr',

    'versions' => [
        'rvr' => ['name' => 'Reina-Valera 1909', 'short' => 'RVR1909', 'cacheable' => true],
        'rva' => ['name' => 'Reina-Valera Actualizada', 'short' => 'RVA', 'cacheable' => false],
        'JER' => ['name' => 'Biblia de Jerusalén', 'short' => 'BJ', 'cacheable' => false],
    ],

    'bethel_channel_id' => 'UCMibkP6B0XmLUHgOwfTUIvQ',
    'bethel_tv_url' => 'https://betheltv.tv/',
    'bethel_radio_url' => 'https://bethelradio.fm/',

    // id de la API => [nombre, testamento, capítulos]
    'books' => [
        'GEN' => ['Génesis', 'AT', 50], 'EXO' => ['Éxodo', 'AT', 40], 'LEV' => ['Levítico', 'AT', 27], 'NUM' => ['Números', 'AT', 36],
        'DEU' => ['Deuteronomio', 'AT', 34], 'JOS' => ['Josué', 'AT', 24], 'JUE' => ['Jueces', 'AT', 21], 'RUT' => ['Rut', 'AT', 4],
        '1SA' => ['1 Samuel', 'AT', 31], '2SA' => ['2 Samuel', 'AT', 24], '1RE' => ['1 Reyes', 'AT', 22], '2RE' => ['2 Reyes', 'AT', 25],
        '1CR' => ['1 Crónicas', 'AT', 29], '2CR' => ['2 Crónicas', 'AT', 36], 'ESD' => ['Esdras', 'AT', 10], 'NEH' => ['Nehemías', 'AT', 13],
        'EST' => ['Ester', 'AT', 10], 'JOB' => ['Job', 'AT', 42], 'SAL' => ['Salmos', 'AT', 150], 'PRO' => ['Proverbios', 'AT', 31],
        'ECL' => ['Eclesiastés', 'AT', 12], 'CAN' => ['Cantares', 'AT', 8], 'ISA' => ['Isaías', 'AT', 66], 'JER' => ['Jeremías', 'AT', 52],
        'LAM' => ['Lamentaciones', 'AT', 5], 'EZE' => ['Ezequiel', 'AT', 48], 'DAN' => ['Daniel', 'AT', 12], 'OSE' => ['Oseas', 'AT', 14],
        'JOE' => ['Joel', 'AT', 3], 'AMO' => ['Amós', 'AT', 9], 'ABD' => ['Abdías', 'AT', 1], 'JON' => ['Jonás', 'AT', 4],
        'MIQ' => ['Miqueas', 'AT', 7], 'NAH' => ['Nahúm', 'AT', 3], 'HAB' => ['Habacuc', 'AT', 3], 'SOF' => ['Sofonías', 'AT', 3],
        'HAG' => ['Hageo', 'AT', 2], 'ZAC' => ['Zacarías', 'AT', 14], 'MAL' => ['Malaquías', 'AT', 4],
        'MAT' => ['Mateo', 'NT', 28], 'MR' => ['Marcos', 'NT', 16], 'LUC' => ['Lucas', 'NT', 24], 'JHN' => ['Juan', 'NT', 21],
        'HCH' => ['Hechos', 'NT', 28], 'ROM' => ['Romanos', 'NT', 16], '1CO' => ['1 Corintios', 'NT', 16], '2CO' => ['2 Corintios', 'NT', 13],
        'GAL' => ['Gálatas', 'NT', 6], 'EFE' => ['Efesios', 'NT', 6], 'FIL' => ['Filipenses', 'NT', 4], 'COL' => ['Colosenses', 'NT', 4],
        '1TE' => ['1 Tesalonicenses', 'NT', 5], '2TE' => ['2 Tesalonicenses', 'NT', 3], '1TI' => ['1 Timoteo', 'NT', 6], '2TI' => ['2 Timoteo', 'NT', 4],
        'TIT' => ['Tito', 'NT', 3], 'FLM' => ['Filemón', 'NT', 1], 'HEB' => ['Hebreos', 'NT', 13], 'SAN' => ['Santiago', 'NT', 5],
        '1PE' => ['1 Pedro', 'NT', 5], '2PE' => ['2 Pedro', 'NT', 3], '1JN' => ['1 Juan', 'NT', 5], '2JN' => ['2 Juan', 'NT', 1],
        '3JN' => ['3 Juan', 'NT', 1], 'JUD' => ['Judas', 'NT', 1], 'APO' => ['Apocalipsis', 'NT', 22],
    ],
];
