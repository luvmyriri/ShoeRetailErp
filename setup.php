<?php
/**
 * Database Setup Script
 * Creates database and tables if they don't exist
 */

// Direct MySQL connection (without selecting database)
try {
    $pdo = new PDO(
        'mysql:host=localhost;charset=utf8mb4',
        'root',
        '0428',
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]
    );
    
    // Create database if not exists
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `shoeretailerp` CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci");
    
    // Select database
    $pdo->exec("USE `shoeretailerp`");
    
    // Read and execute schema
    $schemaFile = __DIR__ . '/ERP_DEFAULT_SCHEMA_FINAL.sql';
    
    if (!file_exists($schemaFile)) {
        die("Schema file not found: " . $schemaFile);
    }
    
    $sql = file_get_contents($schemaFile);
    
    // Split by statements and execute
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            try {
                $pdo->exec($statement);
            } catch (Exception $e) {
                // Log error but continue
                echo "Warning: " . $e->getMessage() . " (Statement: " . substr($statement, 0, 50) . "...)<br>";
            }
        }
    }
    
    echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; font-family: Arial;'>";
    echo "<strong>✓ Database initialized successfully!</strong><br>";
    echo "Database: shoeretailerp<br>";
    echo "Tables created and ready for use.<br>";
    echo "<a href='/ShoeRetailErp/public/index.php' style='color: #0056b3; text-decoration: underline;'>Go to Dashboard</a>";
    echo "</div>";
    
} catch (PDOException $e) {
    die("<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; font-family: Arial;'>" .
         "<strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . 
         "</div>");
}
?>
