<?php declare(strict_types=1);

    namespace STDW\Session;


    class SessionConfig
    {
        protected array $config;


        public function __construct(array $config)
        {
            $this->config = [
                'name' => $this->validateName($config['name'] ?? null),

                'storage' => $this->validateStorage($config['storage'] ?? null),

                'cookie' => [
                    'lifetime' => $this->validateInt($config['cookie']['lifetime'] ?? 0, min: 0),
                    'secure' => $this->validateBool($config['cookie']['secure'] ?? false),
                    'same_site' => $this->validateSameSite($config['cookie']['same_site'] ?? 'Lax'),
                ],

                'garbage_collector' => [
                    'maxlifetime' => $this->validateInt($config['garbage_collector']['maxlifetime'] ?? 1800, min: 1),
                    'probability' => $this->validateInt($config['garbage_collector']['probability'] ?? 1, min: 0),
                    'divisor' => $this->validateInt($config['garbage_collector']['divisor'] ?? 100, min: 1),
                ],

                'extra' => [
                    'regeneration' => $this->validateBool($config['extra']['regeneration'] ?? false),
                    'regeneration_time' => $this->validateInt($config['extra']['regeneration_time'] ?? 900, min: 1),
                ],
            ];
        }


        public function name(): string
        {
            return $this->config['name'];
        }

        public function storage(): string
        {
            return $this->config['storage'];
        }

        public function cookie(): array
        {
            return $this->config['cookie'];
        }

        public function gc(): array
        {
            return $this->config['garbage_collector'];
        }

        public function extra(): array
        {
            return $this->config['extra'];
        }


        protected function validateName(?string $name): string
        {
            if (is_null($name) || ! preg_match('/^[A-Za-z0-9_-]+$/', $name)) {
                return 'PHPSESSID';
            }

            return $name;
        }

        protected function validateStorage(?string $path): string
        {
            if (
                     is_null($path)
                || ! is_string($path)
                || ! is_dir($path)
                || ! is_writable($path)
            ) {
                return $this->getDefaultSavePath();
            }

            return $path;
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
