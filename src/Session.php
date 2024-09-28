<?php declare(strict_types=1);

    namespace STDW\Session;

    use STDW\Session\Contract\SessionInterface;
    use STDW\Session\Contract\SessionHandlerInterface;
    use Throwable;
    use Error;

    class Session implements SessionInterface
    {
        public function __construct(SessionHandlerInterface $handler)
        {
            if (session_status() == PHP_SESSION_DISABLED) {
                throw new Error("Session not started because it's disabled.");
            }

            $name = 'PHPSESSID';
            $session_save_path = session_save_path();

            $cookie_lifetime = 0;
            $cookie_path = '/';
            $cookie_domain = '';
            $cookie_secure = 0;
            $cookie_samesite = 'Lax';

            $session_gc_maxlifetime = 1800;
            $session_gc_probability = 1;
            $session_gc_divisor = 100;


            try {
                $name = config('session.name');
            } catch (Throwable $e) { }

            try {
                $session_save_path = config('session.save_path');
            } catch (Throwable $e) { }

            try {
                $cookie_lifetime = config('session.cookie.lifetime');
            } catch (Throwable $e) { }

            try {
                $cookie_path = config('session.cookie.path');
            } catch (Throwable $e) { }

            try {
                $cookie_domain = config('session.cookie.domain');
            } catch (Throwable $e) { }

            try {
                $cookie_secure = config('session.cookie.secure');
            } catch (Throwable $e) { }

            try {
                $cookie_samesite = config('session.cookie.same_site');
            } catch (Throwable $e) { }

            try {
                $session_gc_maxlifetime = config('session.garbage_collector.maxlifetime');
            } catch (Throwable $e) { }

            try {
                $session_gc_probability = config('session.garbage_collector.probability');
            } catch (Throwable $e) { }

            try {
                $session_gc_divisor = config('session.garbage_collector.divisor');
            } catch (Throwable $e) { }


            ini_set('session.name', $name);

            session_save_path($session_save_path);

            ini_set('session.use_strict_mode', 1);
            ini_set('session.use_cookies', 1);
            ini_set('session.use_only_cookies', 1);
            ini_set('session.use_trans_sid', 0);

            session_set_cookie_params([
                'lifetime' => $cookie_lifetime,
                'path' => $cookie_path,
                'domain' => $cookie_domain,
                'secure' => $cookie_secure,
                'httponly' => true,
                'samesite' => $cookie_samesite,
            ]);

            ini_set('session.gc_maxlifetime', $session_gc_maxlifetime);
            ini_set('session.gc_probability', $session_gc_probability);
            ini_set('session.gc_divisor', $session_gc_divisor);

            session_cache_limiter('nocache');

            session_set_save_handler($handler, true);
        }


        public function start(): void
        {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }


            $regeneration = false;
            $regeneration_time = 1200;

            try {
                $regeneration = config('session.extra.regeneration');
            } catch (Throwable $e) { }

            try {
                $regeneration_time = config('session.extra.regeneration_time');
            } catch (Throwable $e) { }


            if ($regeneration && ! $this->exists('_last_regeneration_')) {
                $this->set('_last_regeneration_', time());
            }

            if ($regeneration && time() - $this->get('_last_regeneration_') > $regeneration_time) {
                session_regenerate_id(true);

                $this->set('_last_regeneration_', time());
            }
        }

        public function get(string $key, mixed $default = null): mixed
        {
            if ($this->exists($key)){
                return $_SESSION[$key];
            }

            return $default;
        }

        public function set(string $key, mixed $value): void
        {
            $_SESSION[$key] = $value;
        }

        public function exists(string $key): bool
        {
            return isset($_SESSION[$key]);
        }

        public function delete(string $key): void
        {
            if ($this->exists($key)) {
                unset($_SESSION[$key]);
            }
        }

        public function clear(): void
        {
            session_unset();
        }

        public function end(): void
        {
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_destroy();
            }
        }
    }