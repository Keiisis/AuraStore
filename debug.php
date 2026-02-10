<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>AuraStore Debug Mode</h1>";

echo "<h2>PHP Version</h2>";
echo PHP_VERSION;

echo "<h2>Loaded Extensions</h2>";
echo implode(", ", get_loaded_extensions());

echo "<h2>Database Connection Test</h2>";
try {
    require_once 'config/database.php';
    $db = getDB();
    echo "✅ Database connection successful!";

    echo "<h3>Tables Check</h3>";
    $tables = ['users', 'stores', 'products', 'landing_settings', 'pricing_plans'];
    foreach ($tables as $t) {
        try {
            $db->query("SELECT 1 FROM $t LIMIT 1");
            echo "✅ Table '$t' exists.<br>";
        } catch (Exception $e) {
            echo "❌ Table '$t' MISSING or Error: " . $e->getMessage() . "<br>";
        }
    }
} catch (Exception $e) {
    echo "❌ Database Error: " . $e->getMessage();
}

echo "<h2>Environment Variables</h2>";
echo "DATABASE_URL: " . (getenv('DATABASE_URL') ? 'SET' : 'NOT SET') . "<br>";
echo "DB_HOST: " . getenv('DB_HOST') . "<br>";
echo "DB_NAME: " . getenv('DB_NAME') . "<br>";
echo "DB_USER: " . getenv('DB_USER') . "<br>";
?>