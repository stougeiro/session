<?php declare(strict_types=1);

    namespace STDW\Session;

    use STDW\Contract\Session\FlashInterface;
    use STDW\Contract\Session\SessionInterface;


    class Flash implements FlashInterface
    {
        public function __construct(
            protected SessionInterface $session)
        { }


        public function get(string $key, mixed $default = null): mixed
        {
            return $default;
        }

        public function set(string $key, mixed $value): void
        {

        }

        public function clear(): void
        {

        }
    }
