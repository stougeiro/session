<?php declare(strict_types=1);

    namespace STDW\Session;

    use STDW\Contract\Session\SessionInterface;
    use STDW\Session\Handler\FileSessionHandler;
    use STDW\Session\Handler\SqliteSessionHandler;

    use RuntimeException;


    class Session implements SessionInterface
    {
        /** @var string The reserved session key for storing the last activity timestamp. 
         */
        protected const KEY_LAST_ACTIVITY = '_last_activity_';

        /** @var string The reserved session key for storing the last regeneration timestamp. 
         */
        protected const KEY_LAST_REGENERATION = '_last_regeneration_';

        /** @var array<string> The reserved session keys that cannot be accessed or modified directly.
         */
        protected array $reservedKeys = [
            self::KEY_LAST_ACTIVITY,
            self::KEY_LAST_REGENERATION,
        ];


        public function __construct(
            protected SessionConfig $config
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

        public function id(): string
        {
            return $this->getSessionId();
        }

        public function has(string $key): bool
        {
            return array_key_exists($key, $_SESSION);
        }

        public function get(string $key, mixed $default = null): mixed
        {
            if (in_array($key, $this->reservedKeys, true)) {
                return $default;
            }

            return $_SESSION[$key] ?? $default;
        }

        public function set(string $key, mixed $value): void
        {
            if (in_array($key, $this->reservedKeys, true)) {
                throw new RuntimeException("Cannot write to reserved session key '{$key}'");
            }

            $_SESSION[$key] = $value;
        }

        public function remove(string $key): void
        {
            if (in_array($key, $this->reservedKeys, true)) {
                throw new RuntimeException("Cannot remove reserved session key '{$key}'");
            }

            unset($_SESSION[$key]);
        }

        public function clear(): void
        {
            $keys = array_keys($_SESSION);

            foreach ($keys as $key) {
                if ( ! in_array($key, $this->reservedKeys, true)) {
                    unset($_SESSION[$key]);
                }
            }

            $_SESSION[self::KEY_LAST_ACTIVITY] = time();
        }

        public function destroy(): void
        {
            if ($this->isSessionStatusActive()) {
                $this->doSessionDestroy();
            }
        }


        protected function isSessionStatusDisabled(): bool
        {
            return session_status() === PHP_SESSION_DISABLED;
        }

        protected function isSessionStatusNone(): bool
        {
            return session_status() === PHP_SESSION_NONE;
        }

        protected function isSessionStatusActive(): bool
        {
            return session_status() === PHP_SESSION_ACTIVE;
        }

        protected function getSessionId(): string
        {
            return session_id();
        }

        protected function applyHandlerSettings(): void
        {
            $type = $this->config->handler();
            $path = $this->config->storage();

            switch ($type) {
                case 'sqlite':
                    $handler = new SqliteSessionHandler($path); break;
                default:
                    $handler = new FileSessionHandler($path);
            }

            session_set_save_handler($handler, true);
        }

        protected function applyIniSettings(): void
        {
            $name = $this->config->name();
            $storage = $this->config->storage();
            $gc = $this->config->gc();

            ini_set('session.name', $name);

            ini_set('session.save_path', $storage);

            ini_set('session.gc_maxlifetime', (string) $gc['maxlifetime']);
            ini_set('session.gc_probability', (string) $gc['probability']);
            ini_set('session.gc_divisor', (string) $gc['divisor']);

            ini_set('session.use_strict_mode', '1');
            ini_set('session.use_cookies', '1');
            ini_set('session.use_only_cookies', '1');
            ini_set('session.use_trans_sid', '0');

            session_cache_limiter('nocache');
        }

        protected function applyCookieSettings(): void
        {
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

        protected function doSessionStart(): void
        {
            $this->applyHandlerSettings();
            $this->applyIniSettings();
            $this->applyCookieSettings();

            session_start();
        }

        protected function handleActivity(): void
        {
            $now = time();
            $timeout = $this->config->gc()['maxlifetime'];

            $last = $_SESSION[self::KEY_LAST_ACTIVITY] ?? null;

            if ($last !== null && ($now - $last) > $timeout) {
                $this->doSessionDestroy();
                $this->doSessionStart();
            }

            $_SESSION[self::KEY_LAST_ACTIVITY] = $now;
        }

        protected function handleRegeneration(): void
        {
            $extra = $this->config->extra();

            if ( ! $extra['regeneration']) {
                return;
            }

            $now = time();
            $last = $_SESSION[self::KEY_LAST_REGENERATION] ?? null;

            if ($last === null) {
                $_SESSION[self::KEY_LAST_REGENERATION] = $now;

                return;
            }

            if (($now - $last) > $extra['regeneration_time']) {
                $this->doSessionRegenerateId();

                $_SESSION[self::KEY_LAST_REGENERATION] = $now;
            }
        }

        protected function doSessionRegenerateId(): void
        {
            session_regenerate_id(true);
        }

        protected function doSessionDestroy(): void
        {
            session_destroy();
        }

        protected function isHttps(): bool
        {
            $https = $_SERVER['HTTPS'] ?? null;
            $port = $_SERVER['SERVER_PORT'] ?? null;

            return ($port === 443) || ( ! empty($https) && $https !== 'off');
        }

        protected function resolveSameSite(string $sameSite): string
        {
            if ($sameSite === 'None' && ! $this->isHttps()) {
                return 'Lax';
            }

            return $sameSite;
        }
    }
