<?php declare(strict_types=1);

    namespace STDW\Session;

    use STDW\Contract\Session\SessionInterface;
    use STDW\Session\Spec\SessionConfigInterface;
    use STDW\Session\Handler\FileSessionHandler;
    use STDW\Session\Handler\SqliteSessionHandler;

    use SessionHandlerInterface;
    use RuntimeException;


    class Session implements SessionInterface
    {
        /** @var array<string> The reserved session keys that cannot be accessed or modified directly.
         */
        private array $reservedKeys = [
            '_last_activity_',
            '_last_regeneration_',
        ];


        /**
         * @param SessionConfigInterface $config 
         * @throws RuntimeException 
         */
        public function __construct(
            protected SessionConfigInterface $config
        ) {
            if ($this->isSessionStatusDisabled()) {
                throw new RuntimeException('Session is disabled in the current PHP configuration');
            }
        }


        /** @return void 
         */
        public function start(): void
        {
            if ($this->isSessionStatusNone()) {
                $this->doSessionStart();
            }

            $this->handleActivity();
            $this->handleRegeneration();
        }

        /** @return string 
         */
        public function id(): string
        {
            return $this->getSessionId();
        }

        /**
         * @param string $key 
         * @return bool 
         */
        public function has(string $key): bool
        {
            return array_key_exists($key, $_SESSION);
        }

        /**
         * @param string $key 
         * @param mixed $default 
         * @return mixed 
         */
        public function get(string $key, mixed $default = null): mixed
        {
            if (in_array($key, $this->reservedKeys, true)) {
                return $default;
            }

            return $_SESSION[$key] ?? $default;
        }

        /**
         * @param string $key 
         * @param mixed $value 
         * @return void 
         * @throws RuntimeException 
         */
        public function set(string $key, mixed $value): void
        {
            if (isset($this->reservedKeys[$key])) {
                throw new RuntimeException("Cannot write to reserved session key '{$key}'");
            }

            $_SESSION[$key] = $value;
        }

        /**
         * @param string $key 
         * @return void 
         * @throws RuntimeException 
         */
        public function remove(string $key): void
        {
            if (isset($this->reservedKeys[$key])) {
                throw new RuntimeException("Cannot remove reserved session key '{$key}'");
            }

            unset($_SESSION[$key]);
        }

        /** @return void 
         */
        public function clear(): void
        {
            $keys = array_keys($_SESSION);

            foreach ($keys as $key) {
                if ( ! in_array($key, $this->reservedKeys, true)) {
                    unset($_SESSION[$key]);
                }
            }

            $this->setLastActivity(time());
        }

        /** @return void 
         */
        public function destroy(): void
        {
            if ($this->isSessionStatusActive()) {
                $this->doSessionDestroy();
            }
        }


        /** @return bool 
         */
        protected function isSessionStatusDisabled(): bool
        {
            return session_status() === PHP_SESSION_DISABLED;
        }

        /** @return bool 
         */
        protected function isSessionStatusNone(): bool
        {
            return session_status() === PHP_SESSION_NONE;
        }

        /** @return bool 
         */
        protected function isSessionStatusActive(): bool
        {
            return session_status() === PHP_SESSION_ACTIVE;
        }

        /** @return string 
         */
        protected function getSessionId(): string
        {
            return session_id() ?: '';
        }

        /** @return void 
         */
        protected function applyHandlerSettings(): void
        {
            $handler = $this->createHandler(
                $this->config->handler(),
                $this->config->storage()
            );

            session_set_save_handler($handler, true);
        }

        /** @return void 
         */
        protected function applyIniSettings(): void
        {
            $name = $this->config->name();
            $storage = $this->config->storage();

            /** @var array{maxlifetime: int, probability: int, divisor: int} $gc */
            $gc = $this->config->gc();

            ini_set('session.name', $name);

            ini_set('session.save_path', $storage);

            ini_set('session.gc_maxlifetime', $gc['maxlifetime']);
            ini_set('session.gc_probability', $gc['probability']);
            ini_set('session.gc_divisor', $gc['divisor']);

            ini_set('session.use_strict_mode', '1');
            ini_set('session.use_cookies', '1');
            ini_set('session.use_only_cookies', '1');
            ini_set('session.use_trans_sid', '0');

            session_cache_limiter('nocache');
        }

        /** @return void 
         */
        protected function applyCookieSettings(): void
        {
            /** @var array{lifetime: int, same_site: 'Lax'|'Strict'|'None'} $cookie */
            $cookie = $this->config->cookie();

            session_set_cookie_params([
                'lifetime' => $cookie['lifetime'],
                'path'     => '/',
                'domain'   => '',
                'secure'   => $this->isHttps(),
                'httponly' => true,
                'samesite' => $this->resolveSameSite($cookie['same_site']),
            ]);
        }

        /** @return void 
         */
        protected function doSessionStart(): void
        {
            $this->applyHandlerSettings();
            $this->applyIniSettings();
            $this->applyCookieSettings();

            session_start();

            $this->setLastActivity(time());
            $this->setLastRegeneration(time());
        }

        /**
         * @param string $type 
         * @param string $path 
         * @return SessionHandlerInterface 
         */
        protected function createHandler(string $type, string $path): SessionHandlerInterface
        {
            return match ($type) {
                'sqlite' => new SqliteSessionHandler($path),
                default  => new FileSessionHandler($path),
            };
        }

        /** @return void 
         */
        protected function handleActivity(): void
        {
            $now = time();
            $last = $this->getLastActivity();
            $timeout = $this->config->gc()['maxlifetime'];

            if (($now - $last) > $timeout) {
                $this->doSessionDestroy();
                $this->doSessionStart();
            }

            $this->setLastActivity($now);
        }

        /** @return void 
         */
        protected function handleRegeneration(): void
        {
            $extra = $this->config->extra();

            if ( ! $extra['regeneration']) {
                return;
            }

            $now = time();
            $last = $this->getLastRegeneration();

            if (($now - $last) > $extra['regeneration_time']) {
                $this->doSessionRegenerateId();
                $this->setLastRegeneration($now);
            }
        }

        /** @return void 
         */
        protected function doSessionRegenerateId(): void
        {
            session_regenerate_id(true);
        }

        /** @return void 
         */
        protected function doSessionDestroy(): void
        {
            session_destroy();
        }

        /** @return bool 
         */
        protected function isHttps(): bool
        {
            $https = $_SERVER['HTTPS'] ?? null;
            $port = $_SERVER['SERVER_PORT'] ?? null;

            $isHttp = ($https !== null && (empty($https) || $https === 'off'))
                   || ($port !== null && $port !== 443);

            return ! $isHttp;
        }

        /**
         * @param 'Lax'|'Strict'|'None' $sameSite 
         * @return 'Lax'|'Strict'|'None' 
         */
        protected function resolveSameSite(string $sameSite): string
        {
            if ($sameSite === 'None' && ! $this->isHttps()) {
                return 'Lax';
            }

            return $sameSite;
        }

        /** @return int 
         */
        public function getLastActivity(): int
        {
            $last = $_SESSION['_last_activity_'] ?? 0;

            if (is_int($last)) {
                return $last;
            }

            if (is_numeric($last)) {
                return (int) $last;
            }

            return 0;
        }

        /**
         * @param int $time 
         * @return void 
         */
        public function setLastActivity(int $time): void
        {
            $_SESSION['_last_activity_'] = $time;
        }

        /** @return int 
         */
        public function getLastRegeneration(): int
        {
            $last = $_SESSION['_last_regeneration_'] ?? 0;

            if (is_int($last)) {
                return $last;
            }

            if (is_numeric($last)) {
                return (int) $last;
            }

            return 0;
        }

        /**
         * @param int $time 
         * @return void 
         */
        public function setLastRegeneration(int $time): void
        {
            $_SESSION['_last_regeneration_'] = $time;
        }
    }
