<?php

declare(strict_types=1);

return [
    'handler' => 'file',
    'name' => 'CUSTOM_SESSID',
    'cookie' => [
        'lifetime' => 3600,
        'same_site' => 'Strict',
    ],
    'garbage_collector' => [
        'maxlifetime' => 600,
    ],
    'extra' => [
        'regeneration' => true,
        'regeneration_time' => 300,
    ],
];
