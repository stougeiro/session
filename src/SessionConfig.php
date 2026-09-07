<?php declare(strict_types=1);

    namespace STDW\Session;

    use STDW\Session\Spec\SessionConfigInterface;


    class SessionConfig implements SessionConfigInterface
    {
        protected string $handler;
        protected string $name;
        protected string $storage;
        protected array $cookie;
        protected array $gc;
        protected array $extra;


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


        public function handler(): string
        {
            return $this->handler;
        }

        public function name(): string
        {
            return $this->name;
        }

        public function storage(): string
        {
            return $this->storage;
        }

        public function cookie(): array
        {
            return $this->cookie;
        }

        public function gc(): array
        {
            return $this->gc;
        }

        public function extra(): array
        {
            return $this->extra;
        }

        protected function validateHandler(string $handler): string
        {
            $allowedHandlers = ['file', 'sqlite'];

            return in_array($handler, $allowedHandlers, true) ? $handler : 'file';
        }

        protected function validateName(string $name): string
        {
            if (preg_match('/^[A-Za-z0-9_-]+$/', $name)) {
                return $name;
            }

            return 'PHPSESSID';
        }

        protected function validateStorage(string $path): string
        {
            if (is_dir($path) && is_writable($path)) {
                return $path;
            }

            return $this->getDefaultSavePath();
        }

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

        protected function validateBool(mixed $value): bool
        {
            return filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? false;
        }

        protected function validateSameSite(string $value): string
        {
            $allowed = ['Lax', 'Strict', 'None'];

            return in_array($value, $allowed, true) ? $value : 'Lax';
        }

        protected function getDefaultSavePath(): string
        {
            return session_save_path() ?: sys_get_temp_dir();
        }
    }
