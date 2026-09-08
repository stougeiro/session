<?php declare(strict_types=1);

    namespace STDW\Session;

    use STDW\Contract\Session\FlashInterface;
    use STDW\Contract\Session\SessionInterface;


    class Flash implements FlashInterface
    {
        /** @var string The reserved session key for storing flash messages.
         */
        protected const FLASH_KEY = '__FLASH__';


        /** @param SessionInterface $session 
         */
        public function __construct(
            protected SessionInterface $session)
        { }


        /**
         * @param string $key 
         * @param mixed $default 
         * @return mixed 
         */
        public function get(string $key, mixed $default = null): mixed
        {
            $flash = $this->read();

            if ( ! isset($flash[$key])) {
                return $default;
            }

            return $flash[$key]['value'];
        }

        /**
         * @param string $key 
         * @param mixed $value 
         * @return void 
         */
        public function set(string $key, mixed $value): void
        {
            $flash = $this->read();
            $flash[$key] = [
                'age' => 1,
                'value' => $value,
            ];

            $this->write($flash);
        }

        /** @return void 
         */
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


        /** @return array<string, array{age: int, value: mixed}> 
         */
        protected function read(): array
        {
            /** @var array<string, array{age: int, value: mixed}> */
            return $this->session->get(self::FLASH_KEY, []);
        }

        /**
         * @param array<string, array{age: int, value: mixed}> $flash 
         * @return void 
         */
        protected function write(array $flash): void
        {
            $this->session->set(self::FLASH_KEY, $flash);
        }
    }
