<?php

declare(strict_types=1);

namespace Tests\Helpers;

use STDW\Session\Flash;
use STDW\Session\Handler\FileSessionHandler;
use STDW\Session\Handler\SqliteSessionHandler;
use STDW\Session\Session;
use STDW\Session\SessionConfig;

trait SessionTestHelper
{
    protected function createSessionConfig(array $overrides = []): SessionConfig
    {
        return new SessionConfig(array_merge([
            'handler' => 'file',
            'name' => 'TEST_SESSID',
            'storage' => $this->getTempDir(),
        ], $overrides));
    }

    protected function createSession(array $configOverrides = []): Session
    {
        return new Session($this->createSessionConfig($configOverrides));
    }

    protected function createTestableSession(array $configOverrides = []): TestableSession
    {
        return new TestableSession($this->createSessionConfig($configOverrides));
    }

    protected function createFlash(array $configOverrides = []): Flash
    {
        return new Flash($this->createSession($configOverrides));
    }

    protected function createTestableFlash(array $configOverrides = []): Flash
    {
        return new Flash($this->createTestableSession($configOverrides));
    }

    protected function createFileHandler(?string $path = null): FileSessionHandler
    {
        return new FileSessionHandler($path ?? $this->getTempDir());
    }

    protected function createSqliteHandler(?string $path = null): SqliteSessionHandler
    {
        return new SqliteSessionHandler($path ?? $this->getTempDir());
    }

    protected function getTempDir(): string
    {
        $dir = sys_get_temp_dir() . '/stougeiro_session_test_' . uniqid();

        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        return $dir;
    }

    protected function cleanDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = glob($dir . '/*');

        foreach ($files as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }

        @rmdir($dir);
    }
}
