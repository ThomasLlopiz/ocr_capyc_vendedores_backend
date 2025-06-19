<?php

return [
    'required'   => 'El campo :attribute es obligatorio.',
    'email'      => 'El :attribute debe ser una dirección de correo válida.',
    'unique'     => 'El :attribute ya está registrado.',
    'confirmed'  => 'La confirmación de :attribute no coincide.',
    'min'        => [
        'string' => 'El :attribute debe tener al menos :min caracteres.',
    ],
    'size'       => [
        'string' => 'El :attribute debe tener exactamente :size caracteres.',
    ],
    'attributes' => [
        'name'     => 'nombre',
        'email'    => 'correo',
        'password' => 'contraseña',
        'code'     => 'código',
    ],
];
