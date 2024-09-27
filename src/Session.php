<?php declare(strict_types=1);

    namespace STDW\Session;

    use STDW\Session\Contract\SessionInterface;
    use STDW\Session\Contract\SessionHandlerInterface;
    use Error;


    class Session implements SessionInterface
    {
        public function __construct(SessionHandlerInterface $handler)
        {
            if (session_status() == PHP_SESSION_DISABLED) {
                throw new Error("Session is disabled.");
            }

            /**
             * SOURCERER
             */

            // $name = config('session.cookie.name');
            // $save_path = config('session.handling.options.save_path');

            // $lifetime = config('session.lifetime.minutes');

            // $cookie_path = config('session.cookie.path');
            // $cookie_domain = config('session.cookie.domain');
            // $cookie_secure = config('session.cookie.secure');
            // $cookie_http_only = config('session.cookie.http_only');
            // $cookie_same_site = config('session.cookie.same_site');

            // session_name($name);
            // session_save_path($save_path);
            // session_cache_expire($lifetime);

            // $cookie_lifetime = config('session.lifetime.expires_on_close') ? 0 : TTL::minutes($lifetime);

            // session_set_cookie_params([
            //     'lifetime' => $cookie_lifetime,
            //     'path' => $cookie_path,
            //     'domain' => $cookie_domain,
            //     'secure' => $cookie_secure,
            //     'httponly' => $cookie_http_only,
            //     'samesite' => $cookie_same_site
            // ]);

            /**
             * MAJU
             */

            // $strict_mode = (bool) config('session.strict_mode') ? 1 : 0;

            // $secure = (bool) config('session.cookie.secure') ? 1 : 0;
            // $http_only = (bool) config('session.cookie.http_only') ? 1 : 0;
            // $same_site = config('session.cookie.same_site');

            // ini_set('session.use_strict_mode', $strict_mode);

            // ini_set('session.cookie_secure', $secure);
            // ini_set('session.cookie_httponly', $http_only);
            // ini_set('session.cookie_samesite', $same_site);


            // $name = config('session.name');
            // $save_path = config('session.save_path');
            // $lifetime = config('session.lifetime.minutes');
            // $cache_limiter = config('session.cache_limiter');
 
            // session_name($name);
            // session_save_path($save_path);
            // session_cache_expire($lifetime);
            // session_cache_limiter($cache_limiter);

            session_set_save_handler($handler, true);
        }


        public function start(): void
        {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
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