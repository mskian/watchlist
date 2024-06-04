<?php

try {

    $db = new SQLite3('./db/watchlist.db');

    $sql = "CREATE TABLE IF NOT EXISTS watchlist (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                title TEXT NOT NULL,
                type TEXT NOT NULL,
                watched INTEGER DEFAULT 0,
                CONSTRAINT unique_title UNIQUE (title)
            )";

    $db->exec($sql);

    $db->close();

    echo "Table 'watchlist' created successfully with unique constraint on 'title' column.";
} catch (Exception $e) {
    echo "Error: " . htmlspecialchars($e->getMessage());
}

?>