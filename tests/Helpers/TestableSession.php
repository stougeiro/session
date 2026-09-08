<?php

declare(strict_types=1);

namespace Tests\Helpers;

use STDW\Session\Session;

class TestableSession extends Session
{
    protected function doSessionStart(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            $_SESSION = [];
        }
    }

    protected function doSessionDestroy(): void
    {
        $_SESSION = [];
    }

    protected function isSessionStatusNone(): bool
    {
        return true;
    }

    protected function isSessionStatusActive(): bool
    {
        return true;
    }

    protected function getSessionId(): string
    {
        return 'test_session_id';
    }

    protected function doSessionRegenerateId(): void
    {
        // no-op
    }
}
