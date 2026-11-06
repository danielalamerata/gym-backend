<?php

return [

    // datos del gimnasio que se muestran en los recibos; cada instalación pone los suyos en el .env
    'nombre' => env('GIMNASIO_NOMBRE'),
    'direccion' => env('GIMNASIO_DIRECCION'),
    'telefono' => env('GIMNASIO_TELEFONO'),

    // opcional: ruta del logo (PNG o JPG) dentro de storage/app/private; necesita la extensión gd
    'logo' => env('GIMNASIO_LOGO'),

];
