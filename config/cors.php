<?php

return [

    // el front corre en otro origen: sin esto, el navegador no le deja leer el nombre del recibo
    'exposed_headers' => ['Content-Disposition'],

];
