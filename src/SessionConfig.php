<?php declare(strict_types=1);

    namespace STDW\Session;

    use STDW\Session\Spec\SessionConfigInterface;


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

        /** @var array<string, mixed>
         */
        protected array $cookie;

        /** @var array<string, mixed>
         */
        protected array $gc;

        /** @var array<string, mixed>
         */
        protected array $extra;


        /** @param array $config 
         */
        public function __construct(array $config)
        {
            $handler = $config['handler'] ?? '';
            $name = $config['name'] ?? '';
            $storage = $config['storage'] ?? '';

            $this->handler = $this->validateHandler($handler);
            $this->name = $this->validateName($name);
            $this->storage = $this->validateStorage($storage);

            $cookieLifetime = $config['cookie']['lifetime'] ?? 0;
            $cookieSameSite = $config['cookie']['same_site'] ?? '';

            $this->cookie = [
                'lifetime' => $this->validateInt($cookieLifetime, min: 0, max: 604800),
                'same_site' => $this->validateSameSite($cookieSameSite),
            ];

            $gcMaxLifetime = $config['garbage_collector']['maxlifetime'] ?? 1200;
            $gcProbability = $config['garbage_collector']['probability'] ?? 1;
            $gcDivisor = $config['garbage_collector']['divisor'] ?? 100;

            $this->gc = [
                'maxlifetime' => $this->validateInt($gcMaxLifetime, min: 1, max: 1800),
                'probability' => $this->validateInt($gcProbability, min: 1, max: 100),
                'divisor' => $this->validateInt($gcDivisor, min: 1, max: 100),
            ];

            $regeneration = $config['extra']['regeneration'] ?? false;
            $regenerationTime = $config['extra']['regeneration_time'] ?? 600;

            $this->extra = [
                'regeneration' => $this->validateBool($regeneration),
                'regeneration_time' => $this->validateInt($regenerationTime, min: 1, max: 900),
            ];
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

        /** @return array<string, mixed> 
         */
        public function cookie(): array
        {
            return $this->cookie;
        }

        /** @return array<string, mixed> 
         */
        public function gc(): array
        {
            return $this->gc;
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
         * @param mixed $value 
         * @return bool 
         */
        protected function validateBool(mixed $value): bool
        {
            return filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? false;
        }

        /**
         * @param string $value 
         * @return string 
         */
        protected function validateSameSite(string $value): string
        {
            $allowed = ['Lax', 'Strict', 'None'];

            return in_array($value, $allowed, true) ? $value : 'Lax';
        }

        /** @return string 
         */
        protected function getDefaultSavePath(): string
        {
            return session_save_path() ?: sys_get_temp_dir();
        }
    }
