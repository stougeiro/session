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

it('both handlers support same interface', function () {
    $fileHandler = $this->createFileHandler($this->tempDir);
    $sqliteHandler = $this->createSqliteHandler($this->tempDir);

    expect($fileHandler)->toBeInstanceOf(\SessionHandlerInterface::class);
    expect($sqliteHandler)->toBeInstanceOf(\SessionHandlerInterface::class);
});

it('both handlers write and read data', function () {
    $fileHandler = $this->createFileHandler($this->tempDir);
    $sqliteHandler = $this->createSqliteHandler($this->tempDir);

    $fileId = 'file_test_' . uniqid();
    $sqliteId = 'sqlite_test_' . uniqid();
    $data = 'test_session_data';

    $fileHandler->write($fileId, $data);
    $sqliteHandler->write($sqliteId, $data);
    $fileHandler->close();
    $sqliteHandler->close();

    $fileResult = $fileHandler->read($fileId);
    $sqliteResult = $sqliteHandler->read($sqliteId);

    expect($fileResult)->toBe($data);
    expect($sqliteResult)->toBe($data);

    $fileHandler->destroy($fileId);
    $sqliteHandler->destroy($sqliteId);
});

it('both handlers destroy sessions', function () {
    $fileHandler = $this->createFileHandler($this->tempDir);
    $sqliteHandler = $this->createSqliteHandler($this->tempDir);

    $fileId = 'file_destroy_' . uniqid();
    $sqliteId = 'sqlite_destroy_' . uniqid();

    $fileHandler->write($fileId, 'data');
    $sqliteHandler->write($sqliteId, 'data');
    $fileHandler->close();
    $sqliteHandler->close();

    $fileHandler->destroy($fileId);
    $sqliteHandler->destroy($sqliteId);

    expect($fileHandler->read($fileId))->toBe('');
    expect($sqliteHandler->read($sqliteId))->toBeFalse();
});

it('both handlers garbage collect expired sessions', function () {
    $fileHandler = $this->createFileHandler($this->tempDir);
    $sqliteHandler = $this->createSqliteHandler($this->tempDir);

    $fileId = 'file_gc_' . uniqid();
    $sqliteId = 'sqlite_gc_' . uniqid();

    $fileHandler->write($fileId, 'data');
    $sqliteHandler->write($sqliteId, 'data');
    $fileHandler->close();
    $sqliteHandler->close();

    $fileGc = $fileHandler->gc(1);
    $sqliteGc = $sqliteHandler->gc(1);

    expect($fileGc)->toBeInt();
    expect($sqliteGc)->toBeInt();

    $fileHandler->destroy($fileId);
    $sqliteHandler->destroy($sqliteId);
});

it('both handlers close successfully', function () {
    $fileHandler = $this->createFileHandler($this->tempDir);
    $sqliteHandler = $this->createSqliteHandler($this->tempDir);

    expect($fileHandler->close())->toBeTrue();
    expect($sqliteHandler->close())->toBeTrue();
});
