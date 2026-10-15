<?php

// Traducción parcial: se amplía a medida que se usan nuevas reglas.
// Las claves ausentes recurren al idioma de respaldo (inglés).
return [
    'boolean' => 'El campo :attribute debe ser verdadero o falso.',
    'confirmed' => 'La confirmación de :attribute no coincide.',
    'email' => 'El campo :attribute debe ser un correo electrónico válido.',
    'integer' => 'El campo :attribute debe ser un número entero.',
    'max' => [
        'numeric' => 'El campo :attribute no debe ser mayor que :max.',
        'string' => 'El campo :attribute no debe tener más de :max caracteres.',
    ],
    'min' => [
        'numeric' => 'El campo :attribute debe ser al menos :min.',
        'string' => 'El campo :attribute debe tener al menos :min caracteres.',
    ],
    'required' => 'El campo :attribute es obligatorio.',
    'string' => 'El campo :attribute debe ser un texto.',
    'unique' => 'El valor de :attribute ya está en uso.',

    'attributes' => [
        'email' => 'correo electrónico',
        'name' => 'nombre',
        'password' => 'contraseña',
    ],
];
