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

    public function publicGetLastActivity(): int
    {
        return $this->getLastActivity();
    }

    public function publicSetLastActivity(int $time): void
    {
        $this->setLastActivity($time);
    }

    public function publicGetLastRegeneration(): int
    {
        return $this->getLastRegeneration();
    }

    public function publicSetLastRegeneration(int $time): void
    {
        $this->setLastRegeneration($time);
    }
}
