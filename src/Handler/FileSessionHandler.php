<?php declare(strict_types=1);

    namespace STDW\Session\Handler;

    use SessionHandlerInterface;
    use SessionUpdateTimestampHandlerInterface;
    use RuntimeException;
    use DirectoryIterator;


    class FileSessionHandler implements SessionHandlerInterface, SessionUpdateTimestampHandlerInterface
    {
        /** @var string
         */
        protected string $path;

        /** @var array<string, string>
         */
        protected array $cache = [];

        /** @var array<string, string>
         */
        protected array $pending = [];


        /** @param string $path 
         */
        public function __construct(string $path)
        {
            $this->path = rtrim($path, '/');

            if ( ! is_dir($this->path) && ! mkdir($this->path, 0700, true)) {
                throw new RuntimeException("Failed to create session storage directory: {$this->path}");
            }
        }


        /**
         * @param string $path 
         * @param string $name 
         * @return bool 
         */
        public function open(string $path, string $name): bool
        {
            return true;
        }

        /**
         * @param string $id 
         * @return string|false 
         */
        public function read(string $id): string|false
        {
            if (isset($this->cache[$id])) {
                return $this->cache[$id];
            }

            $file = $this->filePath($id);

            if ( ! is_file($file)) {
                return '';
            }

            $data = (string) file_get_contents($file);
            $this->cache[$id] = $data;

            return $data;
        }

        /**
         * @param string $id 
         * @param string $data 
         * @return bool 
         */
        public function write(string $id, string $data): bool
        {
            $this->cache[$id] = $data;
            $this->pending[$id] = $data;

            return true;
        }

        /**
         * @param int $max_lifetime 
         * @return int|false 
         */
        public function gc(int $max_lifetime): int|false
        {
            $count = 0;
            $now = time();

            foreach (new DirectoryIterator($this->path) as $file) {
                if ($file->isDot() || !$file->isFile())
                    continue;

                if ($file->getMTime() + $max_lifetime > $now)
                    continue;

                if (unlink($file->getPathname()))
                    $count++;
            }

            return $count;
        }

        /** @return bool 
         */
        public function close(): bool
        {
            if (empty($this->pending)) {
                return true;
            }
           
            foreach ($this->pending as $id => $data) {
                file_put_contents($this->filePath($id), $data, LOCK_EX);
            }

            $this->pending = [];

            return true;
        }

        /**
         * @param string $id 
         * @return bool 
         */
        public function destroy(string $id): bool
        {
            $file = $this->filePath($id);

            if ( ! is_file($file)) {
                return true;
            }

            return unlink($file);
        }

        /**
         * @param string $id
         * @return bool
         */
        public function validateId(string $id): bool
        {
            return preg_match('/^[a-zA-Z0-9,-]{1,128}$/', $id) === 1;
        }

        /**
         * @param string $id
         * @param string $data
         * @return bool
         */
        public function updateTimestamp(string $id, string $data): bool
        {
            $file = $this->filePath($id);

            if (is_file($file)) {
                return touch($file);
            }

            return true;
        }


        /**
         * @param string $id 
         * @return string 
         */
        protected function filePath(string $id): string
        {
            return $this->path .'/sess_'. $id;
        }
    }
