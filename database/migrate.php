<?php
/**
 * Database Migration & Seed Runner CLI
 * Smart Tax Planning Assistance
 */

require_once __DIR__ . '/../config/database.php';

echo "========================================================\n";
echo " Smart Tax Planning Assistance - Database Setup Runner\n";
echo "========================================================\n\n";

try {
    $pdo = getDb();
    echo "[OK] Connected to MySQL database successfully.\n";

    // 1. Run schema.sql
    $schemaFile = __DIR__ . '/schema.sql';
    if (!file_exists($schemaFile)) {
        throw new Exception("schema.sql not found at {$schemaFile}");
    }
    echo "[*] Importing schema.sql...\n";
    $schemaSql = file_get_contents($schemaFile);
    $pdo->exec($schemaSql);
    echo "[OK] Tables created successfully.\n";

    // 2. Run seed.sql
    $seedFile = __DIR__ . '/seed.sql';
    if (!file_exists($seedFile)) {
        throw new Exception("seed.sql not found at {$seedFile}");
    }
    echo "[*] Importing seed.sql (Tax rules, provisions, statutory calendar, demo users)...\n";
    $seedSql = file_get_contents($seedFile);
    $pdo->exec($seedSql);
    echo "[OK] Seed data inserted successfully.\n\n";

    // Verify row counts
    $tables = ['users', 'tax_rule_sets', 'tax_slabs', 'tax_provisions', 'tax_reminders'];
    echo "--- Database Status ---\n";
    foreach ($tables as $tbl) {
        $stmt = $pdo->query("SELECT COUNT(*) AS cnt FROM `{$tbl}`");
        $cnt = $stmt->fetchColumn();
        echo "Table `{$tbl}`: {$cnt} records\n";
    }
    echo "-----------------------\n";
    echo "\nDatabase migration completed successfully!\n";

} catch (Exception $e) {
    echo "[ERROR] Database setup failed: " . $e->getMessage() . "\n";
    exit(1);
}
