<?php

declare(strict_types=1);

use Tests\Helpers\SessionTestHelper;

uses(SessionTestHelper::class);

beforeEach(function () {
    $this->tempDir = $this->getTempDir();
});

afterEach(function () {
    $this->cleanDir($this->tempDir);
});

it('benchmarks session start under 20ms', function () {
    $times = [];

    for ($i = 0; $i < 20; $i++) {
        $session = $this->createSession(['storage' => $this->tempDir]);

        $start = hrtime(true);
        $session->start();
        $times[] = hrtime(true) - $start;

        $session->destroy();
    }

    $avg = array_sum($times) / count($times) / 1e6;

    expect($avg)->toBeLessThan(20);
});

it('benchmarks get/set pair under 1ms', function () {
    $session = $this->createSession(['storage' => $this->tempDir]);
    $session->start();

    $times = [];

    for ($i = 0; $i < 500; $i++) {
        $start = hrtime(true);
        $session->set("key_{$i}", "value_{$i}");
        $session->get("key_{$i}");
        $times[] = hrtime(true) - $start;
    }

    $avg = array_sum($times) / count($times) / 1e6;

    expect($avg)->toBeLessThan(1);

    $session->destroy();
});

it('benchmarks handleActivity no-expiry under 1ms', function () {
    $session = $this->createTestableSession([
        'storage' => $this->tempDir,
        'guard' => ['regeneration' => 0],
    ]);
    $session->start();

    $reflection = new ReflectionClass($session);
    $method = $reflection->getMethod('handleActivity');
    $method->setAccessible(true);

    $times = [];

    for ($i = 0; $i < 200; $i++) {
        $start = hrtime(true);
        $method->invoke($session);
        $times[] = hrtime(true) - $start;
    }

    $avg = array_sum($times) / count($times) / 1e6;

    expect($avg)->toBeLessThan(1);

    $session->destroy();
});

it('benchmarks handleRegeneration no-regen under 0.5ms', function () {
    $session = $this->createTestableSession([
        'storage' => $this->tempDir,
        'guard' => ['regeneration' => 900],
    ]);
    $session->start();

    $reflection = new ReflectionClass($session);
    $method = $reflection->getMethod('handleRegeneration');
    $method->setAccessible(true);

    $times = [];

    for ($i = 0; $i < 200; $i++) {
        $start = hrtime(true);
        $method->invoke($session);
        $times[] = hrtime(true) - $start;
    }

    $avg = array_sum($times) / count($times) / 1e6;

    expect($avg)->toBeLessThan(0.5);

    $session->destroy();
});
