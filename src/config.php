<?php declare(strict_types=1);

    return
    [
        'name' => 'PHPSESSID',

        'save_path' => session_save_path(),

        'cookie' =>
        [
            'secure' => false, // https only

            'same_site' => 'Lax', // Supported: Lax, Strict, None

            'lifetime' => 0, 
        ],

        'garbage_collector' =>
        [
            'maxlifetime' => 1800, // 30min

            'probability' => 1,

            'divisor' => 100,
        ],

        'extra' =>
        [
            'regeneration' => false,

            'regeneration_time' => 1200, // 20min
        ],
    ];