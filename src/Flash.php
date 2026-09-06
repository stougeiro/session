<?php declare(strict_types=1);

    namespace STDW\Session;

    use STDW\Contract\Session\FlashInterface;
    use STDW\Contract\Session\SessionInterface;


    class Flash implements FlashInterface
    {
        protected const FLASH_KEY = '__FLASH__';


        public function __construct(
            protected SessionInterface $session)
        { }


        public function get(string $key, mixed $default = null): mixed
        {
            $flash = $this->read();

            if ( ! isset($flash[$key])) {
                return $default;
            }

            return $flash[$key]['value'];
        }

        public function set(string $key, mixed $value): void
        {
            $flash = $this->read();
            $flash[$key] = [
                'age' => 1,
                'value' => $value,
            ];

            $this->write($flash);
        }

        public function clear(): void
        {
            $flash = $this->read();

            if (empty($flash)) {
                return;
            }

            foreach ($flash as $key => &$item) {
                $item['age']--;

                if ($item['age'] < 0) {
                    unset($flash[$key]);
                }
            }

            $this->write($flash);
        }


        protected function read(): array
        {
            return $this->session->get(self::FLASH_KEY, []);
        }

        protected function write(array $flash): void
        {
            $this->session->set(self::FLASH_KEY, $flash);
        }
    }
