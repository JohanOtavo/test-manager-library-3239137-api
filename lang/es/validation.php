<?php

/*
 * Solo las reglas que este proyecto usa. Los mensajes al usuario van en español
 * (artículo XI); los nombres de campo se quedan en inglés porque son del contrato.
 */

return [
    'required' => 'El campo :attribute es obligatorio.',
    'string' => 'El campo :attribute debe ser texto.',
    'integer' => 'El campo :attribute debe ser un número entero.',
    'array' => 'El campo :attribute debe ser una lista.',
    'uuid' => 'El campo :attribute debe ser un identificador válido.',
    'date' => 'El campo :attribute debe ser una fecha válida.',
    'min' => [
        'string' => 'El campo :attribute debe tener al menos :min caracteres.',
        'array' => 'El campo :attribute necesita al menos :min elemento(s).',
        'numeric' => 'El campo :attribute debe ser al menos :min.',
    ],
    'max' => [
        'string' => 'El campo :attribute admite hasta :max caracteres.',
        'array' => 'El campo :attribute admite hasta :max elemento(s).',
        'numeric' => 'El campo :attribute no puede pasar de :max.',
    ],
];
