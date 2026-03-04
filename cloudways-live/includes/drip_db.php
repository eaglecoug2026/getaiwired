<?php
/**
 * GetAIWired - Drip Campaign Database
 * SQLite connection + table initialization
 */

function getDripDb() {
    static $db = null;

    if ($db !== null) {
        return $db;
    }

    $dbDir = __DIR__ . '/../data';
    $dbPath = $dbDir . '/getaiwired.db';

    // Create data directory if needed
    if (!is_dir($dbDir)) {
        mkdir($dbDir, 0755, true);
    }

    $db = new SQLite3($dbPath);
    $db->busyTimeout(5000);
    $db->exec('PRAGMA journal_mode = WAL');

    // Create tables
    $db->exec('CREATE TABLE IF NOT EXISTS drip_campaigns (
        id TEXT PRIMARY KEY,
        email TEXT NOT NULL,
        first_name TEXT,
        company TEXT,
        score INTEGER,
        level TEXT,
        top_opportunity TEXT,
        opportunities_json TEXT,
        industry TEXT,
        campaign TEXT DEFAULT \'assessment_followup\',
        current_stage INTEGER DEFAULT 0,
        status TEXT DEFAULT \'active\',
        unsubscribe_token TEXT UNIQUE,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        completed_at DATETIME
    )');

    $db->exec('CREATE TABLE IF NOT EXISTS drip_email_log (
        id TEXT PRIMARY KEY,
        campaign_id TEXT,
        email TEXT,
        email_number INTEGER,
        subject TEXT,
        send_result TEXT,
        skip_reason TEXT,
        error_message TEXT,
        sent_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )');

    // Index for efficient cron queries
    $db->exec('CREATE INDEX IF NOT EXISTS idx_drip_status ON drip_campaigns(status, current_stage)');
    $db->exec('CREATE INDEX IF NOT EXISTS idx_drip_email ON drip_campaigns(email)');

    return $db;
}
?>
