<?php

// Copiá este archivo como config.php y completá los datos reales.
// config.php está en .gitignore, así que no se sube al repositorio.

return [
    'smtp' => [
        'host'   => 'a0171211.ferozo.com',
        'port'   => 465,
        'secure' => 'ssl', // 'ssl' para puerto 465, 'tls' para puerto 587
        'user'   => 'invitaciones@fidelio.com.ar',
        'pass'   => 'TU_CONTRASEÑA_AQUI',
    ],
    'from' => [
        'email' => 'invitaciones@fidelio.com.ar',
        'name'  => 'Invitación XV Alma',
    ],
    'to' => [
        ['email' => 'remitente@gmail.com', 'name' => ''],
    ],
    'cc' => [
        ['email' => 'invitaciones@fidelio.com.ar', 'name' => 'Invitaciones Fidelio'],
    ],
];
