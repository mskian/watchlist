<?php

ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('X-Content-Type-Options: nosniff');
header('Strict-Transport-Security: max-age=63072000');
header('X-Robots-Tag: noindex, nofollow', true);

try {

    $db = new SQLite3('./db/watchlist.db');

    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['watched'])) {
        foreach ($_POST['watched'] as $id => $watched) {
            $id = filter_var($id, FILTER_VALIDATE_INT);
            $watched = filter_var($watched, FILTER_VALIDATE_BOOLEAN);
            
            if ($id !== false && $id > 0) {
                $stmt = $db->prepare("UPDATE watchlist SET watched = :watched WHERE id = :id");
                $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
                $stmt->bindValue(':watched', $watched, SQLITE3_INTEGER);
                $stmt->execute();
            }
        }
    }

    $db->close();

    if(isset($_SESSION['prev_page']) && !empty($_SESSION['prev_page'])) {
        header("Location: " . $_SESSION['prev_page']);
    } else {
        header("Location: /");
    }
    exit;
} catch (Exception $e) {

    echo "Error: " . htmlspecialchars($e->getMessage());
}

?>
