<?php

declare(strict_types=1);

use Tests\Helpers\SessionTestHelper;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(Tests\TestCase::class)->in('Feature', 'Unit');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Global Helpers
|--------------------------------------------------------------------------
|
| Here you can expose global helpers to help reduce the number of lines of code
| in your test files.
|
*/

uses(SessionTestHelper::class);

function createTestSession(array $overrides = []): \STDW\Session\Session
{
    return (new class {
        use SessionTestHelper;
    })->createSession($overrides);
}

function createTestableSession(array $overrides = []): \Tests\Helpers\TestableSession
{
    return (new class {
        use SessionTestHelper;
    })->createTestableSession($overrides);
}

function createTestFlash(array $overrides = []): \STDW\Session\Flash
{
    return (new class {
        use SessionTestHelper;
    })->createFlash($overrides);
}

function createTestableFlash(array $overrides = []): \STDW\Session\Flash
{
    return (new class {
        use SessionTestHelper;
    })->createTestableFlash($overrides);
}

function tempDir(): string
{
    return (new class {
        use SessionTestHelper;
    })->getTempDir();
}

function cleanDir(string $dir): void
{
    (new class {
        use SessionTestHelper;
    })->cleanDir($dir);
}
