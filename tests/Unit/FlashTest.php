<?php

declare(strict_types=1);

use STDW\Session\Flash;
use Tests\Helpers\SessionTestHelper;

uses(SessionTestHelper::class);

beforeEach(function () {
    $this->tempDir = $this->getTempDir();
    $this->session = $this->createTestableSession([
        'storage' => $this->tempDir,
    ]);
    $this->session->start();

    $this->flash = new Flash($this->session);
});

afterEach(function () {
    $_SESSION = [];
    $this->cleanDir($this->tempDir);
});

it('creates flash instance', function () {
    expect($this->flash)->toBeInstanceOf(Flash::class);
});

it('implements FlashInterface', function () {
    expect($this->flash)->toBeInstanceOf(\STDW\Contract\Session\FlashInterface::class);
});

it('sets flash message', function () {
    $this->flash->set('success', 'Operation completed');

    $result = $this->flash->get('success');

    expect($result)->toBe('Operation completed');
});

it('gets flash message with default', function () {
    $result = $this->flash->get('nonexistent', 'default');

    expect($result)->toBe('default');
});

it('gets flash message returning null when no default', function () {
    $result = $this->flash->get('nonexistent');

    expect($result)->toBeNull();
});

it('overwrites existing flash message', function () {
    $this->flash->set('key', 'first_value');
    $this->flash->set('key', 'second_value');

    $result = $this->flash->get('key');

    expect($result)->toBe('second_value');
});

it('stores flash with correct internal structure', function () {
    $this->flash->set('message', 'hello');

    $flash = $_SESSION['__FLASH__'] ?? [];

    expect($flash)->toBeArray();
    expect($flash)->toHaveKey('message');
    expect($flash['message'])->toBeArray();
    expect($flash['message']['age'])->toBe(1);
    expect($flash['message']['value'])->toBe('hello');
});

it('decrements age on clear', function () {
    $this->flash->set('msg1', 'first');
    $this->flash->set('msg2', 'second');

    $this->flash->clear();

    $result1 = $this->flash->get('msg1');
    $result2 = $this->flash->get('msg2');

    expect($result1)->not->toBeNull();
    expect($result2)->not->toBeNull();
});

it('removes flash after age expires', function () {
    $this->flash->set('temporary', 'data');

    $this->flash->clear();
    $this->flash->clear();

    $result = $this->flash->get('temporary', 'gone');

    expect($result)->toBe('gone');
});

it('handles empty flash storage', function () {
    $result = $this->flash->get('anything', 'default');

    expect($result)->toBe('default');
});

it('clear does nothing when flash is empty', function () {
    $emptyFlash = new Flash($this->session);

    $emptyFlash->clear();

    expect(true)->toBeTrue();
});

it('stores multiple flash messages', function () {
    $this->flash->set('success', 'Saved!');
    $this->flash->set('error', 'Failed!');
    $this->flash->set('info', 'Notice');

    expect($this->flash->get('success'))->toBe('Saved!');
    expect($this->flash->get('error'))->toBe('Failed!');
    expect($this->flash->get('info'))->toBe('Notice');
});

it('stores different data types', function () {
    $this->flash->set('string', 'hello');
    $this->flash->set('int', 42);
    $this->flash->set('float', 3.14);
    $this->flash->set('bool', true);
    $this->flash->set('array', [1, 2, 3]);

    expect($this->flash->get('string'))->toBe('hello');
    expect($this->flash->get('int'))->toBe(42);
    expect($this->flash->get('float'))->toBe(3.14);
    expect($this->flash->get('bool'))->toBeTrue();
    expect($this->flash->get('array'))->toBe([1, 2, 3]);
});

it('persists flash across clear calls until expired', function () {
    $this->flash->set('key', 'value');

    $this->flash->clear();
    expect($this->flash->get('key'))->not->toBeNull();

    $this->flash->clear();
    expect($this->flash->get('key', 'expired'))->toBe('expired');
});
