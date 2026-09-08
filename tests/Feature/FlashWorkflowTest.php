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

it('complete flash workflow', function () {
    $flash = $this->createTestableFlash([
        'storage' => $this->tempDir,
    ]);

    $flash->set('success', 'Operation completed successfully');
    $flash->set('error', 'Something went wrong');
    $flash->set('info', 'Here is some information');

    expect($flash->get('success'))->toBe('Operation completed successfully');
    expect($flash->get('error'))->toBe('Something went wrong');
    expect($flash->get('info'))->toBe('Here is some information');

    $flash->clear();

    expect($flash->get('success'))->not->toBeNull();
    expect($flash->get('error'))->not->toBeNull();
    expect($flash->get('info'))->not->toBeNull();

    $flash->clear();

    expect($flash->get('success', 'expired'))->toBe('expired');
    expect($flash->get('error', 'expired'))->toBe('expired');
    expect($flash->get('info', 'expired'))->toBe('expired');
});

it('flash messages persist until cleared', function () {
    $flash = $this->createTestableFlash([
        'storage' => $this->tempDir,
    ]);

    $flash->set('message', 'persistent');

    expect($flash->get('message'))->toBe('persistent');

    expect($flash->get('message'))->toBe('persistent');

    $flash->clear();

    expect($flash->get('message'))->not->toBeNull();

    $flash->clear();

    expect($flash->get('message', 'gone'))->toBe('gone');
});

it('flash handles multiple messages of same type', function () {
    $flash = $this->createTestableFlash([
        'storage' => $this->tempDir,
    ]);

    $flash->set('msg1', 'First message');
    $flash->set('msg2', 'Second message');
    $flash->set('msg3', 'Third message');

    expect($flash->get('msg1'))->toBe('First message');
    expect($flash->get('msg2'))->toBe('Second message');
    expect($flash->get('msg3'))->toBe('Third message');
});

it('flash overwrites existing message', function () {
    $flash = $this->createTestableFlash([
        'storage' => $this->tempDir,
    ]);

    $flash->set('key', 'original');
    $flash->set('key', 'updated');

    expect($flash->get('key'))->toBe('updated');
});

it('flash clear decrements age correctly', function () {
    $flash = $this->createTestableFlash([
        'storage' => $this->tempDir,
    ]);

    $flash->set('message', 'test');

    $flash->clear();
    expect($flash->get('message'))->not->toBeNull();

    $flash->clear();
    expect($flash->get('message', 'expired'))->toBe('expired');
});

it('flash handles empty state gracefully', function () {
    $flash = $this->createTestableFlash([
        'storage' => $this->tempDir,
    ]);

    $flash->clear();

    expect($flash->get('anything', 'default'))->toBe('default');
});

it('flash stores different data types', function () {
    $flash = $this->createTestableFlash([
        'storage' => $this->tempDir,
    ]);

    $flash->set('string', 'hello');
    $flash->set('integer', 42);
    $flash->set('float', 3.14);
    $flash->set('boolean', true);
    $flash->set('array', [1, 2, 3]);
    $flash->set('nested', ['key' => 'value']);

    expect($flash->get('string'))->toBe('hello');
    expect($flash->get('integer'))->toBe(42);
    expect($flash->get('float'))->toBe(3.14);
    expect($flash->get('boolean'))->toBeTrue();
    expect($flash->get('array'))->toBe([1, 2, 3]);
    expect($flash->get('nested'))->toBe(['key' => 'value']);
});
