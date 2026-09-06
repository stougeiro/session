<?php declare(strict_types=1);

    namespace STDW\Session;

    use STDW\Contract\Session\SessionInterface;


    class Session implements SessionInterface
    {
        public function start(): void
        {

        }

        public function id(): string
        {
            return '';
        }

        public function has(string $key): bool
        {
            return false;
        }

        public function get(string $key, mixed $default = null): mixed
        {
            return $default;
        }

        public function set(string $key, mixed $value): void
        {

        }

        public function remove(string $key): void
        {

        }

        public function clear(): void
        {

        }

        public function destroy(): void
        {

        }
    }
