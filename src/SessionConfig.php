<?php declare(strict_types=1);

    namespace STDW\Session;

    use STDW\Contract\Session\SessionConfigInterface;


    class SessionConfig implements SessionConfigInterface
    {
        /** @var string
         */
        protected string $handler;

        /** @var string
         */
        protected string $name;

        /** @var string
         */
        protected string $storage;

        /** @var int
         */
        protected int $cookieLifetime;

        /** @var string
         */
        protected string $cookieSameSite;

        /** @var int
         */
        protected int $gcMaxLifetime;

        /** @var int
         */
        protected int $gcProbability;

        /** @var int
         */
        protected int $gcDivisor;

        /** @var int
         */
        protected int $guardRegeneration;

        /** @var array<string, mixed>
         */
        protected array $extra;


        /**
         * @param array{
         *    handler?: string,
         *    name?: string,
         *    storage?: string,
         *    cookie?: array{lifetime?: int, same_site?: 'Lax'|'Strict'|'None'},
         *    garbage_collector?: array{maxlifetime?: int, probability?: int, divisor?: int},
         *    guard?: array{regeneration?: int},
         *    extra?: array<string, mixed>
         * } $config
         */
        public function __construct(array $config)
        {
            $defaults = [
                'handler' => 'file',
                'name' => '',
                'storage' => '',

                'cookie' => [
                    'lifetime' => 0,
                    'same_site' => 'Lax',
                ],

                'gc' => [
                    'maxlifetime' => 1200,
                    'probability' => 1,
                    'divisor' => 100,
                ],

                'guard' => [
                    'regeneration' => 600,
                ],

                'extra' => [],
            ];

            $config = array_replace_recursive($defaults, $config);

            $this->handler = $this->validateHandler($config['handler']);
            $this->name = $this->validateName($config['name']);
            $this->storage = $this->validateStorage($config['storage']);

            $this->cookieLifetime = $this->validateInt($config['cookie']['lifetime'], min: 0, max: 604800);
            $this->cookieSameSite = $this->validateSameSite($config['cookie']['same_site']);

            $this->gcMaxLifetime = $this->validateInt($config['gc']['maxlifetime'], min: 1, max: 1800);
            $this->gcProbability = $this->validateInt($config['gc']['probability'], min: 1, max: 100);
            $this->gcDivisor = $this->validateInt($config['gc']['divisor'], min: 1, max: 100);

            $this->guardRegeneration = $this->validateInt($config['guard']['regeneration'], min: 1, max: 900);

            $this->extra = $config['extra'];
        }


        /** @return string
         */
        public function handler(): string
        {
            return $this->handler;
        }

        /** @return string
         */
        public function name(): string
        {
            return $this->name;
        }

        /** @return string
         */
        public function storage(): string
        {
            return $this->storage;
        }

        /** @return int
         */
        public function cookieLifetime(): int
        {
            return $this->cookieLifetime;
        }

        /** @return 'Lax'|'Strict'|'None'
         */
        public function cookieSameSite(): string
        {
            return $this->cookieSameSite;
        }

        /** @return int
         */
        public function gcMaxLifetime(): int
        {
            return $this->gcMaxLifetime;
        }

        /** @return int
         */
        public function gcProbability(): int
        {
            return $this->gcProbability;
        }

        /** @return int
         */
        public function gcDivisor(): int
        {
            return $this->gcDivisor;
        }

        /** @return int
         */
        public function guardRegeneration(): int
        {
            return $this->guardRegeneration;
        }

        /** @return array<string, mixed>
         */
        public function extra(): array
        {
            return $this->extra;
        }

        /**
         * @param string $handler 
         * @return string 
         */
        protected function validateHandler(string $handler): string
        {
            $allowedHandlers = ['file', 'sqlite'];

            return in_array($handler, $allowedHandlers, true) ? $handler : 'file';
        }

        /**
         * @param string $name 
         * @return string 
         */
        protected function validateName(string $name): string
        {
            if (preg_match('/^[A-Za-z0-9_-]+$/', $name)) {
                return $name;
            }

            return 'PHPSESSID';
        }

        /**
         * @param string $path 
         * @return string 
         */
        protected function validateStorage(string $path): string
        {
            if (is_dir($path) && is_writable($path)) {
                return $path;
            }

            return $this->getDefaultSavePath();
        }

        /**
         * @param int $value 
         * @param null|int $min 
         * @param null|int $max 
         * @return int 
         */
        protected function validateInt(int $value, ?int $min = null, ?int $max = null): int
        {
            if ( ! is_null($min) && $value < $min) {
                $value = $min;
            }

            if ( ! is_null($max) && $value > $max) {
                $value = $max;
            }

            return $value;
        }

        /**
         * @param string $value 
         * @return 'Lax'|'Strict'|'None' 
         */
        protected function validateSameSite(string $value): string
        {
            $allowed = ['Lax', 'Strict', 'None'];
            $value = ucfirst(strtolower($value));

            return in_array($value, $allowed, true) ? $value : 'Lax';
        }

        /** @return string 
         */
        protected function getDefaultSavePath(): string
        {
            return session_save_path() ?: sys_get_temp_dir();
        }
    }
