<?php

declare(strict_types=1);

use STDW\Session\Handler\SqliteSessionHandler;
use Tests\Helpers\SessionTestHelper;

uses(SessionTestHelper::class);

beforeEach(function () {
    $this->tempDir = $this->getTempDir();
    $this->handler = $this->createSqliteHandler($this->tempDir);
});

afterEach(function () {
    $this->cleanDir($this->tempDir);
});

it('creates handler instance', function () {
    expect($this->handler)->toBeInstanceOf(SqliteSessionHandler::class);
});

it('implements SessionHandlerInterface', function () {
    expect($this->handler)->toBeInstanceOf(\SessionHandlerInterface::class);
});

it('creates sqlite database file', function () {
    $dbFile = $this->tempDir . '/session.sqlite';

    expect(is_file($dbFile))->toBeTrue();
});

it('creates sessions table', function () {
    $dbFile = $this->tempDir . '/session.sqlite';
    $pdo = new PDO('sqlite:' . $dbFile);

    $tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN);

    expect($tables)->toContain('sessions');

    $pdo = null;
});

it('open returns true', function () {
    $result = $this->handler->open($this->tempDir, 'test_name');

    expect($result)->toBeTrue();
});

it('read returns false for nonexistent session', function () {
    $result = $this->handler->read('nonexistent_id');

    expect($result)->toBeFalse();
});

it('write and read session data', function () {
    $id = 'sqlite_test_' . uniqid();
    $data = 'serialized_session_data';

    $this->handler->write($id, $data);
    $this->handler->close();

    $result = $this->handler->read($id);

    expect($result)->toBe($data);

    $this->handler->destroy($id);
});

it('write returns true on success', function () {
    $id = 'sqlite_test_' . uniqid();

    $result = $this->handler->write($id, 'data');

    expect($result)->toBeTrue();

    $this->handler->close();
    $this->handler->destroy($id);
});

it('write updates existing data', function () {
    $id = 'sqlite_test_' . uniqid();

    $this->handler->write($id, 'first_data');
    $this->handler->write($id, 'second_data');
    $this->handler->close();

    $result = $this->handler->read($id);

    expect($result)->toBe('second_data');

    $this->handler->destroy($id);
});

it('close returns true', function () {
    $result = $this->handler->close();

    expect($result)->toBeTrue();
});

it('destroy removes session record', function () {
    $id = 'sqlite_test_' . uniqid();

    $this->handler->write($id, 'data');
    $this->handler->close();

    $result = $this->handler->read($id);
    expect($result)->toBe('data');

    $this->handler->destroy($id);

    $handler2 = $this->createSqliteHandler($this->tempDir);
    $result = $handler2->read($id);
    expect($result)->toBeFalse();
});

it('destroy returns true for nonexistent session', function () {
    $result = $this->handler->destroy('nonexistent_id');

    expect($result)->toBeTrue();
});

it('gc removes expired records', function () {
    $id1 = 'expired_' . uniqid();
    $id2 = 'recent_' . uniqid();

    $this->handler->write($id1, 'old_data');
    $this->handler->write($id2, 'new_data');
    $this->handler->close();

    $dbFile = $this->tempDir . '/session.sqlite';
    $pdo = new PDO('sqlite:' . $dbFile);

    $oldTime = time() - 3600;
    $pdo->exec("UPDATE sessions SET timestamp = {$oldTime} WHERE id = '{$id1}'");

    $removed = $this->handler->gc(1800);

    expect($removed)->toBeGreaterThanOrEqual(1);

    $handler2 = $this->createSqliteHandler($this->tempDir);
    $result1 = $handler2->read($id1);
    expect($result1)->toBeFalse();

    $result2 = $handler2->read($id2);
    expect($result2)->toBe('new_data');

    $this->handler->destroy($id2);

    $pdo = null;
});

it('gc returns count of removed records', function () {
    $id1 = 'expired1_' . uniqid();
    $id2 = 'expired2_' . uniqid();

    $this->handler->write($id1, 'data1');
    $this->handler->write($id2, 'data2');
    $this->handler->close();

    $dbFile = $this->tempDir . '/session.sqlite';
    $pdo = new PDO('sqlite:' . $dbFile);

    $oldTime = time() - 3600;
    $pdo->exec("UPDATE sessions SET timestamp = {$oldTime} WHERE id IN ('{$id1}', '{$id2}')");

    $removed = $this->handler->gc(1800);

    expect($removed)->toBeGreaterThanOrEqual(2);

    $this->cleanDir($this->tempDir);

    $pdo = null;
});

it('gc does not remove recent records', function () {
    $id = 'recent_' . uniqid();

    $this->handler->write($id, 'data');
    $this->handler->close();

    $removed = $this->handler->gc(1800);

    expect($removed)->toBe(0);

    $result = $this->handler->read($id);
    expect($result)->toBe('data');

    $this->handler->destroy($id);
});

it('handles empty data', function () {
    $id = 'empty_' . uniqid();

    $this->handler->write($id, '');
    $this->handler->close();

    $result = $this->handler->read($id);

    expect($result)->toBe('');

    $this->handler->destroy($id);
});

it('handles special characters in data', function () {
    $id = 'special_' . uniqid();
    $data = 'áéíóú ñ ç 漢字 🔒';

    $this->handler->write($id, $data);
    $this->handler->close();

    $result = $this->handler->read($id);

    expect($result)->toBe($data);

    $this->handler->destroy($id);
});

it('handles large data', function () {
    $id = 'large_' . uniqid();
    $data = str_repeat('A', 1024 * 1024);

    $this->handler->write($id, $data);
    $this->handler->close();

    $result = $this->handler->read($id);

    expect($result)->toBe($data);

    $this->handler->destroy($id);
});

it('stores timestamp with session data', function () {
    $id = 'timestamp_test_' . uniqid();

    $before = time();
    $this->handler->write($id, 'data');
    $this->handler->close();
    $after = time();

    $dbFile = $this->tempDir . '/session.sqlite';
    $pdo = new PDO('sqlite:' . $dbFile);

    $stmt = $pdo->prepare("SELECT timestamp FROM sessions WHERE id = :id");
    $stmt->execute(['id' => $id]);
    $timestamp = (int) $stmt->fetchColumn();

    expect($timestamp)->toBeGreaterThanOrEqual($before);
    expect($timestamp)->toBeLessThanOrEqual($after);

    $this->handler->destroy($id);

    $pdo = null;
});
