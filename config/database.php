<?php
/**
 * AuraStore - Database Connection (PDO Singleton)
 */

function getDB()
{
    static $pdo = null;

    if ($pdo === null) {
        $dbUrl = getenv('DATABASE_URL');

        if ($dbUrl) {
            // Parse URL from Supabase/Vercel (e.g. postgres://user:pass@host:port/db)
            $db = parse_url($dbUrl);

            $host = $db['host'];
            $port = $db['port'] ?? 5432;
            $dbname = ltrim($db['path'], '/');
            $user = $db['user'];
            $pass = $db['pass'];

            $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
        } else {
            // Local Fallback or Individual Vars
            $host = getenv('DB_HOST') ?: 'sql105.infinityfree.com';
            $dbname = getenv('DB_NAME') ?: 'if0_41123536_aron';
            $user = getenv('DB_USER') ?: 'if0_41123536';
            $pass = getenv('DB_PASS') ?: 'gapihnRkGREv7I';
            $port = getenv('DB_PORT') ?: '3306';

            // Check if we are running locally (likely MySQL) or Prod (Postgres) based on port/driver
            // Forced to mysql for InfinityFree
            $driver = 'mysql';

            if ($driver === 'mysql') {
                $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
            } else {
                $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
            }
        }

        try {
            $pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            // Log error safely in production
            error_log("DB Connection Error: " . $e->getMessage());
            throw new RuntimeException("Erreur de connexion à la base de données (Supabase/SQL).");
        }
    }

    return $pdo;
}
