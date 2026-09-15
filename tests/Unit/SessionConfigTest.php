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
    expect($config->cookieLifetime())->toBe(0);
    expect($config->cookieSameSite())->toBe('Lax');
    expect($config->gcMaxLifetime())->toBe(1200);
    expect($config->gcProbability())->toBe(1);
    expect($config->gcDivisor())->toBe(100);
    expect($config->guardRegeneration())->toBe(600);
    expect($config->extra())->toBe([]);
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

    expect($config->cookieLifetime())->toBe(3600);
    expect($config->cookieSameSite())->toBe('Strict');
});

it('validates cookie lifetime min', function () {
    $config = new SessionConfig([
        'cookie' => [
            'lifetime' => -1,
        ],
    ]);

    expect($config->cookieLifetime())->toBe(0);
});

it('validates cookie lifetime max', function () {
    $config = new SessionConfig([
        'cookie' => [
            'lifetime' => 999999,
        ],
    ]);

    expect($config->cookieLifetime())->toBe(604800);
});

it('normalizes same_site value', function () {
    $config = new SessionConfig([
        'cookie' => [
            'same_site' => 'strict',
        ],
    ]);

    expect($config->cookieSameSite())->toBe('Strict');
});

it('falls back to Lax for invalid same_site', function () {
    $config = new SessionConfig([
        'cookie' => [
            'same_site' => 'invalid',
        ],
    ]);

    expect($config->cookieSameSite())->toBe('Lax');
});

it('creates config with custom garbage collector settings', function () {
    $config = new SessionConfig([
        'gc' => [
            'maxlifetime' => 600,
            'probability' => 5,
            'divisor' => 50,
        ],
    ]);

    expect($config->gcMaxLifetime())->toBe(600);
    expect($config->gcProbability())->toBe(5);
    expect($config->gcDivisor())->toBe(50);
});

it('validates gc maxlifetime min', function () {
    $config = new SessionConfig([
        'gc' => [
            'maxlifetime' => 0,
        ],
    ]);

    expect($config->gcMaxLifetime())->toBe(1);
});

it('validates gc maxlifetime max', function () {
    $config = new SessionConfig([
        'gc' => [
            'maxlifetime' => 9999,
        ],
    ]);

    expect($config->gcMaxLifetime())->toBe(1800);
});

it('validates gc probability bounds', function () {
    $config = new SessionConfig([
        'gc' => [
            'probability' => 150,
        ],
    ]);

    expect($config->gcProbability())->toBe(100);
});

it('validates gc divisor bounds', function () {
    $config = new SessionConfig([
        'gc' => [
            'divisor' => 0,
        ],
    ]);

    expect($config->gcDivisor())->toBe(1);
});

it('creates config with custom guard regeneration', function () {
    $config = new SessionConfig([
        'guard' => [
            'regeneration' => 300,
        ],
    ]);

    expect($config->guardRegeneration())->toBe(300);
});

it('validates guard regeneration min', function () {
    $config = new SessionConfig([
        'guard' => [
            'regeneration' => 0,
        ],
    ]);

    expect($config->guardRegeneration())->toBe(1);
});

it('validates guard regeneration max', function () {
    $config = new SessionConfig([
        'guard' => [
            'regeneration' => 9999,
        ],
    ]);

    expect($config->guardRegeneration())->toBe(900);
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
    expect($config->cookieLifetime())->toBe(3600);
    expect($config->cookieSameSite())->toBe('Strict');
    expect($config->gcMaxLifetime())->toBe(600);
    expect($config->guardRegeneration())->toBe(300);
});
