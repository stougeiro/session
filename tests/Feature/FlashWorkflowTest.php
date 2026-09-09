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
