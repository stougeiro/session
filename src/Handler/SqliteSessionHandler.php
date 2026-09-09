<?php declare(strict_types=1);

    namespace STDW\Session\Handler;

    use PDO;
    use SessionHandlerInterface;


    class SqliteSessionHandler implements SessionHandlerInterface
    {
        /** @var PDO
         */
        protected PDO $pdo;

        /** @var string
         */
        protected string $table = 'sessions';

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
            $database = rtrim($path, '/') . '/session.sqlite';

            if ( ! file_exists($database)) {
                touch($database);
            }

            $this->pdo = new PDO('sqlite:' . $database);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

            $this->pdo->exec("PRAGMA journal_mode = WAL");
            $this->pdo->exec("PRAGMA synchronous = NORMAL");
            $this->pdo->exec("PRAGMA temp_store = MEMORY");
            $this->pdo->exec("PRAGMA mmap_size = 268435456");
            $this->pdo->exec("PRAGMA cache_size = -20000");
            $this->pdo->exec("PRAGMA foreign_keys = OFF");

            $this->createTable();
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

            $stmt = $this->pdo->prepare("
                SELECT data FROM {$this->table}
                WHERE id = :id
                LIMIT 1");

            $stmt->execute(['id' => $id]);

            $data = $stmt->fetchColumn();

            if ($data === false) {
                return false;
            }

            $this->cache[$id] = (string) $data;

            return $this->cache[$id];
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
            $limit = time() - $max_lifetime;

            $stmt = $this->pdo->prepare("
                DELETE FROM {$this->table}
                WHERE timestamp < :limit
            ");

            $stmt->execute(['limit' => $limit]);

            return $stmt->rowCount();
        }

        /** @return bool 
         */
        public function close(): bool
        {
            if (empty($this->pending)) {
                return true;
            }

            $stmt = $this->pdo->prepare("
                INSERT OR REPLACE INTO {$this->table} (id, data, timestamp)
                VALUES (:id, :data, :timestamp)
            ");

            $timestamp = time();

            foreach ($this->pending as $id => $data) {
                $stmt->execute([
                    'id'        => $id,
                    'data'      => $data,
                    'timestamp' => $timestamp
                ]);
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
            unset($this->cache[$id], $this->pending[$id]);

            $stmt = $this->pdo->prepare("
                DELETE FROM {$this->table}
                WHERE id = :id
            ");

            return $stmt->execute(['id' => $id]);
        }


        /** @return void 
         */
        protected function createTable(): void
        {
            $sql = "
                CREATE TABLE IF NOT EXISTS {$this->table} (
                    id TEXT PRIMARY KEY,
                    data BLOB,
                    timestamp INTEGER
                ) WITHOUT ROWID
            ";

            $this->pdo->exec($sql);

            $this->pdo->exec("
                CREATE INDEX IF NOT EXISTS {$this->table}_timestamp_idx
                ON {$this->table} (timestamp)
            ");
        }
    }
