<?php declare(strict_types=1);

    namespace STDW\Session\Handler;

    use SessionHandlerInterface;


    class FileSessionHandler implements SessionHandlerInterface
    {
        protected string $path;


        public function __construct(string $path)
        {
            $this->path = rtrim($path, '/');

            if ( ! is_dir($this->path)) {
                mkdir($this->path, 0777, true);
            }
        }


        public function open(string $path, string $name): bool
        {
            return true;
        }

        public function read(string $id): string|false
        {
            $file = $this->filePath($id);

            if ( ! is_file($file)) {
                return '';
            }

            $fp = fopen($file, 'rb');

            if ( ! $fp) {
                return '';
            }

            $data = stream_get_contents($fp);
            fclose($fp);

            return $data ?: '';
        }

        public function write(string $id, string $data): bool
        {
            $file = $this->filePath($id);
            $fp = fopen($file, 'c+b');

            if ( ! $fp) {
                return false;
            }

            if ( ! flock($fp, LOCK_EX)) {
                fclose($fp);

                return false;
            }

            ftruncate($fp, 0);

            $bytes = fwrite($fp, $data);

            fflush($fp);
            flock($fp, LOCK_UN);
            fclose($fp);

            return $bytes !== false;
        }

        public function gc(int $max_lifetime): int|false
        {
            $count = 0;
            $now = time();
            $files = glob($this->path . '/sess_*') ?: [];

            foreach ($files as $file) {
                if (filemtime($file) + $max_lifetime < $now) {
                    @unlink($file);
                    $count++;
                }
            }

            return $count;
        }

        public function close(): bool
        {
            return true;
        }

        public function destroy(string $id): bool
        {
            $file = $this->filePath($id);

            if (is_file($file)) {
                unlink($file);
            }

            return true;
        }


        protected function filePath(string $id): string
        {
            return $this->path .'/sess_'. $id;
        }
    }
