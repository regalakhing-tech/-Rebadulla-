<?php
declare(strict_types=1);

function db(): PDO
{
    static $connection;
    if ($connection instanceof PDO) {
        return $connection;
    }
    if (!configured()) {
        throw new RuntimeException(
            "Database setup is required. Follow SETUP.md to configure Supabase.",
        );
    }
    $dsn = sprintf(
        "pgsql:host=%s;port=%d;dbname=%s;sslmode=%s;connect_timeout=8",
        env("DB_HOST"),
        (int) env("DB_PORT", "6543"),
        env("DB_DATABASE", "postgres"),
        env("DB_SSLMODE", "require"),
    );
    $connection = new PDO($dsn, env("DB_USERNAME"), env("DB_PASSWORD"), [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        // Supabase transaction pooling does not support session-scoped prepared statements.
        PDO::ATTR_EMULATE_PREPARES => true,
        PDO::ATTR_PERSISTENT => false,
    ]);
    return $connection;
}

function transaction(callable $work): mixed
{
    $connection = db();
    if ($connection->inTransaction()) {
        return $work($connection);
    }
    $connection->beginTransaction();
    try {
        // SET LOCAL is confined to this transaction, including when a pooler reuses the connection.
        $connection->exec("SET LOCAL ROLE clinic_app");
        $connection->exec("SET LOCAL search_path TO clinic, pg_catalog");
        $result = $work($connection);
        $connection->commit();
        return $result;
    } catch (Throwable $error) {
        if ($connection->inTransaction()) {
            $connection->rollBack();
        }
        throw $error;
    }
}

function query(string $sql, array $parameters = []): PDOStatement
{
    return transaction(function (PDO $connection) use (
        $sql,
        $parameters,
    ): PDOStatement {
        $statement = $connection->prepare($sql);
        foreach ($parameters as $key => $value) {
            $type = match (true) {
                is_int($value) => PDO::PARAM_INT,
                is_bool($value) => PDO::PARAM_BOOL,
                $value === null => PDO::PARAM_NULL,
                default => PDO::PARAM_STR,
            };
            $statement->bindValue(
                is_int($key) ? $key + 1 : $key,
                $value,
                $type,
            );
        }
        $statement->execute();
        return $statement;
    });
}

function rows(string $sql, array $parameters = []): array
{
    return query($sql, $parameters)->fetchAll();
}

function row(string $sql, array $parameters = []): ?array
{
    return query($sql, $parameters)->fetch() ?: null;
}
