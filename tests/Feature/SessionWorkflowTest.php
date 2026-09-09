<?php

declare(strict_types=1);

use Tests\Helpers\SessionTestHelper;

uses(SessionTestHelper::class);

beforeEach(function () {
    $this->tempDir = $this->getTempDir();
    $_SESSION = [];
});

afterEach(function () {
    $_SESSION = [];
    $this->cleanDir($this->tempDir);
});

it('complete session workflow with file handler', function () {
    $session = $this->createTestableSession([
        'handler' => 'file',
        'storage' => $this->tempDir,
    ]);

    $session->start();

    $session->set('user_id', 123);
    $session->set('user_name', 'John Doe');
    $session->set('preferences', ['theme' => 'dark']);

    expect($session->get('user_id'))->toBe(123);
    expect($session->get('user_name'))->toBe('John Doe');
    expect($session->get('preferences'))->toBe(['theme' => 'dark']);

    expect($session->has('user_id'))->toBeTrue();
    expect($session->has('nonexistent'))->toBeFalse();

    $session->remove('user_name');
    expect($session->has('user_name'))->toBeFalse();

    $session->clear();
    expect($session->has('user_id'))->toBeFalse();
    expect($session->has('preferences'))->toBeFalse();
    expect($session->has('_last_activity_'))->toBeTrue();

    $session->destroy();

    expect($_SESSION)->not->toHaveKey('user_id');
});

it('complete session workflow with sqlite handler', function () {
    $session = $this->createTestableSession([
        'handler' => 'sqlite',
        'storage' => $this->tempDir,
    ]);

    $session->start();

    $session->set('user_id', 456);
    $session->set('user_name', 'Jane Smith');

    expect($session->get('user_id'))->toBe(456);
    expect($session->get('user_name'))->toBe('Jane Smith');

    $session->remove('user_name');
    expect($session->has('user_name'))->toBeFalse();

    $session->clear();
    expect($session->has('user_id'))->toBeFalse();

    $session->destroy();

    expect($_SESSION)->toBeEmpty();
});

it('session persists data correctly', function () {
    $session = $this->createTestableSession([
        'storage' => $this->tempDir,
    ]);

    $session->start();
    $session->set('key', 'persistent_value');

    expect($session->get('key'))->toBe('persistent_value');

    $session->set('key', 'updated_value');
    expect($session->get('key'))->toBe('updated_value');

    $session->destroy();
});

it('session handles activity timeout', function () {
    $session = $this->createTestableSession([
        'storage' => $this->tempDir,
        'garbage_collector' => [
            'maxlifetime' => 1,
        ],
    ]);

    $session->start();
    $session->set('user_id', 123);
    $session->set('user_name', 'John Doe');

    $_SESSION['_last_activity_'] = time() - 10;

    $session2 = $this->createTestableSession([
        'storage' => $this->tempDir,
        'garbage_collector' => [
            'maxlifetime' => 1,
        ],
    ]);

    $session2->start();

    expect($_SESSION)->toBeArray();
    expect($_SESSION)->not->toHaveKey('user_id');
    expect($_SESSION)->not->toHaveKey('user_name');
    expect($_SESSION)->toHaveKey('_last_activity_');

    $session2->destroy();
});

it('session regeneration works', function () {
    $session = $this->createTestableSession([
        'storage' => $this->tempDir,
        'extra' => [
            'regeneration' => true,
            'regeneration_time' => 1,
        ],
    ]);

    $session->start();
    $session->set('key', 'value');

    $oldId = $session->id();

    $_SESSION['_last_regeneration_'] = time() - 10;

    $session2 = $this->createTestableSession([
        'storage' => $this->tempDir,
        'extra' => [
            'regeneration' => true,
            'regeneration_time' => 1,
        ],
    ]);

    $session2->start();

    expect($_SESSION)->toBeArray();
    expect($_SESSION)->toHaveKey('_last_regeneration_');

    $session2->destroy();
});
