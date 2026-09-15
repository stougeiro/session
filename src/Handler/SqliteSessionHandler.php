<?php declare(strict_types=1);

    namespace STDW\Session\Handler;

    use PDO;
    use PDOStatement;
    use RuntimeException;
    use SessionHandlerInterface;
    use SessionUpdateTimestampHandlerInterface;


    class SqliteSessionHandler implements SessionHandlerInterface, SessionUpdateTimestampHandlerInterface
    {
        /** @var PDO
         */
        protected PDO $pdo;

        /** @var PDOStatement
         */
        protected PDOStatement $stmtRead;

        /** @var PDOStatement
         */
        protected PDOStatement $stmtInsert;

        /** @var PDOStatement
         */
        protected PDOStatement $stmtDelete;

        /** @var PDOStatement
         */
        protected PDOStatement $stmtDeleteExpired;

        /** @var PDOStatement
         */
        protected PDOStatement $stmtUpdate;


        /** @var string
         */
        protected string $table = 'sessions';

        /** @var array<string, string>
         */
        protected array $cache = [];

        /** @var array<string, string>
         */
        protected array $pending = [];


        /** @param string $storage 
         */
        public function __construct(string $storage)
        {
            $dir = rtrim($storage, '/');

            if ( ! is_dir($dir) && ! mkdir($dir, 0700, true)) {
                throw new RuntimeException("Failed to create session storage directory: {$dir}");
            }

            $database = $dir . '/session.sqlite';

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
            $this->prepareStatements();
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

            $this->stmtRead->closeCursor();
            $this->stmtRead->execute(['id' => $id]);

            $data = $this->stmtRead->fetchColumn();

            if ($data === false) {
                return '';
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

            $this->stmtDeleteExpired->closeCursor();
            $this->stmtDeleteExpired->execute(['limit' => $limit]);

            return $this->stmtDeleteExpired->rowCount();
        }

        /** @return bool 
         */
        public function close(): bool
        {
            if (empty($this->pending)) {
                return true;
            }

            $this->stmtInsert->closeCursor();
            $timestamp = time();

            foreach ($this->pending as $id => $data) {
                $this->stmtInsert->execute([
                    'id'        => $id,
                    'data'      => $data,
                    'timestamp' => $timestamp,
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
            $this->stmtDelete->closeCursor();

            return $this->stmtDelete->execute(['id' => $id]);
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
            $this->stmtUpdate->closeCursor();

            return $this->stmtUpdate->execute([
                'id'        => $id,
                'timestamp' => time(),
            ]);
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

        /** @return void 
         */
        protected function prepareStatements(): void
        {
            $this->stmtRead = $this->pdo->prepare(
                "SELECT data FROM {$this->table} WHERE id = :id LIMIT 1"
            );

            $this->stmtInsert = $this->pdo->prepare(
                "INSERT OR REPLACE INTO {$this->table} (id, data, timestamp) VALUES (:id, :data, :timestamp)"
            );

            $this->stmtDelete = $this->pdo->prepare(
                "DELETE FROM {$this->table} WHERE id = :id"
            );

            $this->stmtDeleteExpired = $this->pdo->prepare(
                "DELETE FROM {$this->table} WHERE timestamp < :limit"
            );

            $this->stmtUpdate = $this->pdo->prepare(
                "UPDATE {$this->table} SET timestamp = :timestamp WHERE id = :id"
            );
        }
    }
