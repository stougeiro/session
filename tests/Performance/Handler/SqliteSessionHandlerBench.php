<?php

declare(strict_types=1);

use Tests\Helpers\SessionTestHelper;

uses(SessionTestHelper::class);

beforeEach(function () {
    $this->tempDir = $this->getTempDir();
    $this->handler = $this->createSqliteHandler($this->tempDir);
});

afterEach(function () {
    $this->handler->close();
    $this->cleanDir($this->tempDir);
});

it('benchmarks write+close under 10ms avg', function () {
    $times = [];

    for ($i = 0; $i < 100; $i++) {
        $start = hrtime(true);
        $this->handler->write("bench_{$i}", str_repeat('x', 1024));
        $this->handler->close();
        $times[] = hrtime(true) - $start;
    }

    $avg = array_sum($times) / count($times) / 1e6;

    expect($avg)->toBeLessThan(10);
});

it('benchmarks read cache miss under 5ms avg', function () {
    for ($i = 0; $i < 50; $i++) {
        $this->handler->write("bench_{$i}", str_repeat('y', 1024));
    }
    $this->handler->close();

    $times = [];

    for ($i = 0; $i < 50; $i++) {
        $start = hrtime(true);
        $this->handler->read("bench_{$i}");
        $times[] = hrtime(true) - $start;
    }

    $avg = array_sum($times) / count($times) / 1e6;

    expect($avg)->toBeLessThan(5);
});

it('benchmarks read cache hit under 0.5ms avg', function () {
    $this->handler->write('cached_id', str_repeat('z', 1024));
    $this->handler->close();

    $this->handler->read('cached_id');

    $times = [];

    for ($i = 0; $i < 200; $i++) {
        $start = hrtime(true);
        $this->handler->read('cached_id');
        $times[] = hrtime(true) - $start;
    }

    $avg = array_sum($times) / count($times) / 1e6;

    expect($avg)->toBeLessThan(0.5);
});

it('benchmarks gc 500 records under 100ms', function () {
    for ($i = 0; $i < 500; $i++) {
        $this->handler->write("gc_{$i}", 'data');
    }
    $this->handler->close();

    $start = hrtime(true);
    $this->handler->gc(1);
    $elapsed = (hrtime(true) - $start) / 1e6;

    expect($elapsed)->toBeLessThan(100);
});

it('benchmarks destroy under 5ms avg', function () {
    for ($i = 0; $i < 50; $i++) {
        $this->handler->write("destroy_{$i}", 'data');
    }
    $this->handler->close();

    $times = [];

    for ($i = 0; $i < 50; $i++) {
        $start = hrtime(true);
        $this->handler->destroy("destroy_{$i}");
        $times[] = hrtime(true) - $start;
    }

    $avg = array_sum($times) / count($times) / 1e6;

    expect($avg)->toBeLessThan(5);
});

it('benchmarks updateTimestamp under 5ms avg', function () {
    $this->handler->write('ts_id', 'data');
    $this->handler->close();

    $times = [];

    for ($i = 0; $i < 100; $i++) {
        $start = hrtime(true);
        $this->handler->updateTimestamp('ts_id', 'data');
        $times[] = hrtime(true) - $start;
    }

    $avg = array_sum($times) / count($times) / 1e6;

    expect($avg)->toBeLessThan(5);
});
