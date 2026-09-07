<?php declare(strict_types=1);

    namespace STDW\Session\Handler;

    use PDO;
    use SessionHandlerInterface;


    class SqliteSessionHandler implements SessionHandlerInterface
    {
        protected PDO $pdo;
        protected string $table = 'sessions';


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

            $this->createTable();
        }


        public function open(string $path, string $name): bool
        {
            return true;
        }

        public function read(string $id): string|false
        {
            $stmt = $this->pdo->prepare("
                SELECT data FROM {$this->table}
                WHERE id = :id
                LIMIT 1");

            $stmt->execute(['id' => $id]);

            $data = $stmt->fetchColumn();

            return $data ?: '';
        }

        public function write(string $id, string $data): bool
        {
            $stmt = $this->pdo->prepare("
                INSERT OR REPLACE INTO {$this->table} (id, data, timestamp)
                VALUES (:id, :data, :timestamp)
            ");

            return $stmt->execute([
                'id' => $id,
                'data' => $data,
                'timestamp' => time()
            ]);
        }

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

        public function close(): bool
        {
            return true;
        }

        public function destroy(string $id): bool
        {
            $stmt = $this->pdo->prepare("
                DELETE FROM {$this->table}
                WHERE id = :id
            ");

            return $stmt->execute(['id' => $id]);
        }


        protected function createTable(): void
        {
            $sql = "
                CREATE TABLE IF NOT EXISTS {$this->table} (
                    id TEXT PRIMARY KEY,
                    data BLOB,
                    timestamp INTEGER
                )
            ";

            $this->pdo->exec($sql);
        }
    }
