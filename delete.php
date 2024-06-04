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

if (!$_SESSION['form_enabled']) {
    header('Location: /');
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete'])) {
    try {

        $id = filter_input(INPUT_POST, 'delete', FILTER_VALIDATE_INT);

        if ($id === false || $id <= 0) {
            throw new Exception("Invalid item ID provided");
        }

        $db = new SQLite3('./db/watchlist.db');

        $stmt = $db->prepare("DELETE FROM watchlist WHERE id = :id");
        $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
        $stmt->execute();

        $db->close();

        if(isset($_SESSION['prev_page']) && !empty($_SESSION['prev_page'])) {
            header("Location: " . $_SESSION['prev_page']);
        } else {
            header("Location: /");
        }
        exit;
    } catch (Exception $e) {
        echo "Error deleting item: " . htmlspecialchars($e->getMessage());
    }
} else {
    header("Location: /");
    exit;
}

?>