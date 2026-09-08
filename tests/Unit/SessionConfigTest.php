<?php

declare(strict_types=1);

use STDW\Session\SessionConfig;
use Tests\Helpers\SessionTestHelper;

uses(SessionTestHelper::class);

beforeEach(function () {
    $this->tempDir = $this->getTempDir();
});

afterEach(function () {
    $this->cleanDir($this->tempDir);
});

it('creates config with default values', function () {
    $config = new SessionConfig([]);

    expect($config->handler())->toBe('file');
    expect($config->name())->toBe('PHPSESSID');
    expect($config->cookie())->toBe([
        'lifetime' => 0,
        'same_site' => 'Lax',
    ]);
    expect($config->gc())->toBe([
        'maxlifetime' => 1200,
        'probability' => 1,
        'divisor' => 100,
    ]);
    expect($config->extra())->toBe([
        'regeneration' => false,
        'regeneration_time' => 600,
    ]);
});

it('creates config with custom handler file', function () {
    $config = new SessionConfig([
        'handler' => 'file',
        'storage' => $this->tempDir,
    ]);

    expect($config->handler())->toBe('file');
});

it('creates config with custom handler sqlite', function () {
    $config = new SessionConfig([
        'handler' => 'sqlite',
        'storage' => $this->tempDir,
    ]);

    expect($config->handler())->toBe('sqlite');
});

it('falls back to file handler for invalid handler', function () {
    $config = new SessionConfig([
        'handler' => 'invalid',
    ]);

    expect($config->handler())->toBe('file');
});

it('creates config with valid name', function () {
    $config = new SessionConfig([
        'name' => 'MY_SESSION',
    ]);

    expect($config->name())->toBe('MY_SESSION');
});

it('falls back to PHPSESSID for invalid name', function () {
    $config = new SessionConfig([
        'name' => 'invalid name with spaces!',
    ]);

    expect($config->name())->toBe('PHPSESSID');
});

it('accepts underscore and hyphen in name', function () {
    $config = new SessionConfig([
        'name' => 'MY_SESSION-ID',
    ]);

    expect($config->name())->toBe('MY_SESSION-ID');
});

it('uses valid storage path', function () {
    $config = new SessionConfig([
        'storage' => $this->tempDir,
    ]);

    expect($config->storage())->toBe($this->tempDir);
});

it('falls back to default storage for invalid path', function () {
    $config = new SessionConfig([
        'storage' => '/nonexistent/path/that/does/not/exist',
    ]);

    expect($config->storage())->not->toBe('/nonexistent/path/that/does/not/exist');
});

it('creates config with custom cookie settings', function () {
    $config = new SessionConfig([
        'cookie' => [
            'lifetime' => 3600,
            'same_site' => 'Strict',
        ],
    ]);

    expect($config->cookie())->toBe([
        'lifetime' => 3600,
        'same_site' => 'Strict',
    ]);
});

it('validates cookie lifetime min', function () {
    $config = new SessionConfig([
        'cookie' => [
            'lifetime' => -1,
        ],
    ]);

    expect($config->cookie()['lifetime'])->toBe(0);
});

it('validates cookie lifetime max', function () {
    $config = new SessionConfig([
        'cookie' => [
            'lifetime' => 999999,
        ],
    ]);

    expect($config->cookie()['lifetime'])->toBe(604800);
});

it('normalizes same_site value', function () {
    $config = new SessionConfig([
        'cookie' => [
            'same_site' => 'strict',
        ],
    ]);

    expect($config->cookie()['same_site'])->toBe('Strict');
});

it('falls back to Lax for invalid same_site', function () {
    $config = new SessionConfig([
        'cookie' => [
            'same_site' => 'invalid',
        ],
    ]);

    expect($config->cookie()['same_site'])->toBe('Lax');
});

it('creates config with custom garbage collector settings', function () {
    $config = new SessionConfig([
        'garbage_collector' => [
            'maxlifetime' => 600,
            'probability' => 5,
            'divisor' => 50,
        ],
    ]);

    expect($config->gc())->toBe([
        'maxlifetime' => 600,
        'probability' => 5,
        'divisor' => 50,
    ]);
});

it('validates gc maxlifetime min', function () {
    $config = new SessionConfig([
        'garbage_collector' => [
            'maxlifetime' => 0,
        ],
    ]);

    expect($config->gc()['maxlifetime'])->toBe(1);
});

it('validates gc maxlifetime max', function () {
    $config = new SessionConfig([
        'garbage_collector' => [
            'maxlifetime' => 9999,
        ],
    ]);

    expect($config->gc()['maxlifetime'])->toBe(1800);
});

it('validates gc probability bounds', function () {
    $config = new SessionConfig([
        'garbage_collector' => [
            'probability' => 150,
        ],
    ]);

    expect($config->gc()['probability'])->toBe(100);
});

it('validates gc divisor bounds', function () {
    $config = new SessionConfig([
        'garbage_collector' => [
            'divisor' => 0,
        ],
    ]);

    expect($config->gc()['divisor'])->toBe(1);
});

it('creates config with custom extra settings', function () {
    $config = new SessionConfig([
        'extra' => [
            'regeneration' => true,
            'regeneration_time' => 300,
        ],
    ]);

    expect($config->extra())->toBe([
        'regeneration' => true,
        'regeneration_time' => 300,
    ]);
});

it('validates regeneration_time min', function () {
    $config = new SessionConfig([
        'extra' => [
            'regeneration_time' => 0,
        ],
    ]);

    expect($config->extra()['regeneration_time'])->toBe(1);
});

it('validates regeneration_time max', function () {
    $config = new SessionConfig([
        'extra' => [
            'regeneration_time' => 9999,
        ],
    ]);

    expect($config->extra()['regeneration_time'])->toBe(900);
});

it('creates config from fixture default', function () {
    $fixture = require __DIR__ . '/../Fixtures/config/default.php';
    $config = new SessionConfig($fixture);

    expect($config->handler())->toBe('file');
});

it('creates config from fixture sqlite', function () {
    $fixture = require __DIR__ . '/../Fixtures/config/sqlite.php';
    $config = new SessionConfig($fixture);

    expect($config->handler())->toBe('sqlite');
});

it('creates config from fixture custom', function () {
    $fixture = require __DIR__ . '/../Fixtures/config/custom.php';
    $config = new SessionConfig(array_merge($fixture, [
        'storage' => $this->tempDir,
    ]));

    expect($config->handler())->toBe('file');
    expect($config->name())->toBe('CUSTOM_SESSID');
    expect($config->cookie()['lifetime'])->toBe(3600);
    expect($config->cookie()['same_site'])->toBe('Strict');
    expect($config->gc()['maxlifetime'])->toBe(600);
    expect($config->extra()['regeneration'])->toBeTrue();
    expect($config->extra()['regeneration_time'])->toBe(300);
});
