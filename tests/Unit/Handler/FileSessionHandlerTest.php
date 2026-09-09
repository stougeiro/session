<?php

declare(strict_types=1);

use STDW\Session\Handler\FileSessionHandler;
use Tests\Helpers\SessionTestHelper;

uses(SessionTestHelper::class);

beforeEach(function () {
    $this->tempDir = $this->getTempDir();
    $this->handler = $this->createFileHandler($this->tempDir);
});

afterEach(function () {
    $this->cleanDir($this->tempDir);
});

it('creates handler instance', function () {
    expect($this->handler)->toBeInstanceOf(FileSessionHandler::class);
});

it('implements SessionHandlerInterface', function () {
    expect($this->handler)->toBeInstanceOf(\SessionHandlerInterface::class);
});

it('implements SessionUpdateTimestampHandlerInterface', function () {
    expect($this->handler)->toBeInstanceOf(\SessionUpdateTimestampHandlerInterface::class);
});

it('creates directory if not exists', function () {
    $newDir = $this->tempDir . '/new_dir';

    $handler = $this->createFileHandler($newDir);

    expect(is_dir($newDir))->toBeTrue();

    $this->cleanDir($newDir);
});

it('open returns true', function () {
    $result = $this->handler->open($this->tempDir, 'test_name');

    expect($result)->toBeTrue();
});

it('read returns empty string for nonexistent session', function () {
    $result = $this->handler->read('nonexistent_id');

    expect($result)->toBe('');
});

it('write and read session data', function () {
    $id = 'test_session_' . uniqid();
    $data = 'serialized_session_data';

    $this->handler->write($id, $data);
    $this->handler->close();

    $result = $this->handler->read($id);

    expect($result)->toBe($data);

    $this->handler->destroy($id);
});

it('write returns true on success', function () {
    $id = 'test_session_' . uniqid();

    $result = $this->handler->write($id, 'data');

    expect($result)->toBeTrue();

    $this->handler->close();
    $this->handler->destroy($id);
});

it('write overwrites existing data', function () {
    $id = 'test_session_' . uniqid();

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

it('destroy removes session file', function () {
    $id = 'test_session_' . uniqid();

    $this->handler->write($id, 'data');
    $this->handler->close();

    $file = $this->tempDir . '/sess_' . $id;
    expect(is_file($file))->toBeTrue();

    $this->handler->destroy($id);

    expect(is_file($file))->toBeFalse();
});

it('destroy returns true for nonexistent session', function () {
    $result = $this->handler->destroy('nonexistent_id');

    expect($result)->toBeTrue();
});

it('gc removes expired files', function () {
    $id1 = 'test_expired_' . uniqid();
    $id2 = 'test_recent_' . uniqid();

    $this->handler->write($id1, 'old_data');
    $this->handler->write($id2, 'new_data');
    $this->handler->close();

    $file1 = $this->tempDir . '/sess_' . $id1;
    $file2 = $this->tempDir . '/sess_' . $id2;

    touch($file1, time() - 3600);

    $removed = $this->handler->gc(1800);

    expect($removed)->toBeGreaterThanOrEqual(1);
    expect(is_file($file1))->toBeFalse();
    expect(is_file($file2))->toBeTrue();

    $this->handler->destroy($id2);
});

it('gc returns count of removed files', function () {
    $id1 = 'expired1_' . uniqid();
    $id2 = 'expired2_' . uniqid();

    $this->handler->write($id1, 'data1');
    $this->handler->write($id2, 'data2');
    $this->handler->close();

    $file1 = $this->tempDir . '/sess_' . $id1;
    $file2 = $this->tempDir . '/sess_' . $id2;

    touch($file1, time() - 3600);
    touch($file2, time() - 3600);

    $removed = $this->handler->gc(1800);

    expect($removed)->toBeGreaterThanOrEqual(2);

    $this->cleanDir($this->tempDir);
});

it('gc does not remove recent files', function () {
    $id = 'recent_' . uniqid();

    $this->handler->write($id, 'data');
    $this->handler->close();

    $file = $this->tempDir . '/sess_' . $id;

    $removed = $this->handler->gc(1800);

    expect($removed)->toBe(0);
    expect(is_file($file))->toBeTrue();

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

it('stores sessions with correct file naming', function () {
    $id = 'naming_test_' . uniqid();

    $this->handler->write($id, 'data');
    $this->handler->close();

    $expectedFile = $this->tempDir . '/sess_' . $id;
    expect(is_file($expectedFile))->toBeTrue();

    $this->handler->destroy($id);
});

it('validateId returns true for valid ID', function () {
    expect($this->handler->validateId('abc123'))->toBeTrue();
    expect($this->handler->validateId('a-b-c-d'))->toBeTrue();
    expect($this->handler->validateId('1234567890'))->toBeTrue();
    expect($this->handler->validateId('A1B2C3'))->toBeTrue();
});

it('validateId returns false for invalid ID', function () {
    expect($this->handler->validateId('../etc/passwd'))->toBeFalse();
    expect($this->handler->validateId('id with spaces'))->toBeFalse();
    expect($this->handler->validateId('id@#$%'))->toBeFalse();
    expect($this->handler->validateId('id/path/traversal'))->toBeFalse();
});

it('validateId returns false for ID too long', function () {
    expect($this->handler->validateId(str_repeat('a', 129)))->toBeFalse();
});

it('validateId accepts max length ID', function () {
    expect($this->handler->validateId(str_repeat('a', 128)))->toBeTrue();
});

it('updateTimestamp touches existing file', function () {
    $id = 'touch_test_' . uniqid();

    $this->handler->write($id, 'data');
    $this->handler->close();

    $file = $this->tempDir . '/sess_' . $id;
    $oldTime = filemtime($file) - 3600;
    touch($file, $oldTime);

    $result = $this->handler->updateTimestamp($id, 'data');

    expect($result)->toBeTrue();
    expect(filemtime($file))->toBeGreaterThanOrEqual(time() - 2);

    $this->handler->destroy($id);
});

it('updateTimestamp returns true for nonexistent file', function () {
    $result = $this->handler->updateTimestamp('nonexistent', 'data');

    expect($result)->toBeTrue();
});
