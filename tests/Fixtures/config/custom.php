<?php

declare(strict_types=1);

return [
    'handler' => 'file',
    'name' => 'CUSTOM_SESSID',
    'cookie' => [
        'lifetime' => 3600,
        'same_site' => 'Strict',
    ],
    'gc' => [
        'maxlifetime' => 600,
    ],
    'guard' => [
        'regeneration' => 300,
    ],
];
