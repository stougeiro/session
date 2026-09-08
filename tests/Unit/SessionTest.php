<?php

declare(strict_types=1);

use STDW\Session\Session;
use STDW\Session\SessionConfig;
use Tests\Helpers\SessionTestHelper;
use Tests\Helpers\TestableSession;

uses(SessionTestHelper::class);

beforeEach(function () {
    $this->tempDir = $this->getTempDir();
    $this->session = $this->createTestableSession([
        'storage' => $this->tempDir,
    ]);
});

afterEach(function () {
    $_SESSION = [];
    $this->cleanDir($this->tempDir);
});

it('creates session instance', function () {
    expect($this->session)->toBeInstanceOf(TestableSession::class);
});

it('extends Session class', function () {
    expect($this->session)->toBeInstanceOf(Session::class);
});

it('implements SessionInterface', function () {
    expect($this->session)->toBeInstanceOf(\STDW\Contract\Session\SessionInterface::class);
});

it('returns session id as string', function () {
    $this->session->start();

    $id = $this->session->id();

    expect($id)->toBeString();
});

it('checks if key exists', function () {
    $this->session->start();

    expect($this->session->has('nonexistent'))->toBeFalse();

    $this->session->set('test_key', 'test_value');

    expect($this->session->has('test_key'))->toBeTrue();
});

it('gets value with default', function () {
    $this->session->start();

    $result = $this->session->get('nonexistent', 'default_value');

    expect($result)->toBe('default_value');
});

it('gets existing value', function () {
    $this->session->start();

    $this->session->set('test_key', 'test_value');

    $result = $this->session->get('test_key');

    expect($result)->toBe('test_value');
});

it('gets null as default when not specified', function () {
    $this->session->start();

    $result = $this->session->get('nonexistent');

    expect($result)->toBeNull();
});

it('does not access reserved key _last_activity_', function () {
    $this->session->start();

    $result = $this->session->get('_last_activity_', 'default');

    expect($result)->toBe('default');
});

it('does not access reserved key _last_regeneration_', function () {
    $this->session->start();

    $result = $this->session->get('_last_regeneration_', 'default');

    expect($result)->toBe('default');
});

it('sets value', function () {
    $this->session->start();

    $this->session->set('key', 'value');

    expect($_SESSION['key'])->toBe('value');
});

it('throws exception when setting reserved key _last_activity_', function () {
    $this->session->start();

    $this->session->set('_last_activity_', time());
})->throws(\RuntimeException::class, "Cannot write to reserved session key '_last_activity_'");

it('throws exception when setting reserved key _last_regeneration_', function () {
    $this->session->start();

    $this->session->set('_last_regeneration_', time());
})->throws(\RuntimeException::class, "Cannot write to reserved session key '_last_regeneration_'");

it('removes key', function () {
    $this->session->start();

    $this->session->set('key', 'value');
    expect($this->session->has('key'))->toBeTrue();

    $this->session->remove('key');
    expect($this->session->has('key'))->toBeFalse();
});

it('throws exception when removing reserved key _last_activity_', function () {
    $this->session->start();

    $this->session->remove('_last_activity_');
})->throws(\RuntimeException::class, "Cannot remove reserved session key '_last_activity_'");

it('throws exception when removing reserved key _last_regeneration_', function () {
    $this->session->start();

    $this->session->remove('_last_regeneration_');
})->throws(\RuntimeException::class, "Cannot remove reserved session key '_last_regeneration_'");

it('clears session preserving reserved keys', function () {
    $this->session->start();

    $this->session->set('key1', 'value1');
    $this->session->set('key2', 'value2');

    $this->session->clear();

    expect($this->session->has('key1'))->toBeFalse();
    expect($this->session->has('key2'))->toBeFalse();
    expect($this->session->has('_last_activity_'))->toBeTrue();
});

it('sets last activity on clear', function () {
    $this->session->start();

    $before = time();
    $this->session->clear();
    $after = time();

    $lastActivity = $this->session->getLastActivity();

    expect($lastActivity)->toBeGreaterThanOrEqual($before);
    expect($lastActivity)->toBeLessThanOrEqual($after);
});

it('gets last activity with default zero', function () {
    $this->session->start();

    $lastActivity = $this->session->getLastActivity();

    expect($lastActivity)->toBeInt();
});

it('sets and gets last activity', function () {
    $this->session->start();

    $time = 1234567890;
    $this->session->setLastActivity($time);

    expect($this->session->getLastActivity())->toBe($time);
});

it('gets last activity handling non-int value', function () {
    $this->session->start();

    $_SESSION['_last_activity_'] = '12345';

    expect($this->session->getLastActivity())->toBe(12345);
});

it('gets last activity returning zero for invalid value', function () {
    $this->session->start();

    $_SESSION['_last_activity_'] = 'invalid';

    expect($this->session->getLastActivity())->toBe(0);
});

it('gets and sets last regeneration', function () {
    $this->session->start();

    $time = 1234567890;
    $this->session->setLastRegeneration($time);

    expect($this->session->getLastRegeneration())->toBe($time);
});

it('gets last regeneration with default zero', function () {
    $this->session->start();

    $lastRegeneration = $this->session->getLastRegeneration();

    expect($lastRegeneration)->toBeInt();
});

it('gets last regeneration handling non-int value', function () {
    $this->session->start();

    $_SESSION['_last_regeneration_'] = '99999';

    expect($this->session->getLastRegeneration())->toBe(99999);
});

it('gets last regeneration returning zero for invalid value', function () {
    $this->session->start();

    $_SESSION['_last_regeneration_'] = 'invalid';

    expect($this->session->getLastRegeneration())->toBe(0);
});

it('starts session', function () {
    $this->session->start();

    expect($_SESSION)->toBeArray();
});

it('can start session multiple times without error', function () {
    $this->session->start();
    $this->session->start();

    expect($_SESSION)->toBeArray();
});

it('destroys active session', function () {
    $this->session->start();
    $this->session->set('key', 'value');

    expect($_SESSION)->toHaveKey('key');

    $this->session->destroy();

    expect($_SESSION)->not->toHaveKey('key');
});

it('destroy does nothing when session not active', function () {
    $_SESSION = [];

    $this->session->destroy();

    expect($_SESSION)->toBeEmpty();
});

it('stores and retrieves multiple values', function () {
    $this->session->start();

    $this->session->set('string', 'hello');
    $this->session->set('int', 42);
    $this->session->set('float', 3.14);
    $this->session->set('bool', true);
    $this->session->set('array', [1, 2, 3]);

    expect($this->session->get('string'))->toBe('hello');
    expect($this->session->get('int'))->toBe(42);
    expect($this->session->get('float'))->toBe(3.14);
    expect($this->session->get('bool'))->toBeTrue();
    expect($this->session->get('array'))->toBe([1, 2, 3]);
});

it('throws exception for disabled session', function () {
    $config = new SessionConfig([
        'storage' => $this->tempDir,
    ]);

    expect(fn() => new Session($config))->not->toThrow(\RuntimeException::class);
});
