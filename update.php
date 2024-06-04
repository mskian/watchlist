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

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['id'])) {

    try {

        $db = new SQLite3('./db/watchlist.db');

        $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
        $title = trim(filter_input(INPUT_POST, 'title', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $type = trim(filter_input(INPUT_POST, 'type', FILTER_SANITIZE_FULL_SPECIAL_CHARS));

        if (empty($title)) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(array("error" => "Title is required"));
            exit;
        }
        if (strlen($title) > 255) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(array("error" => "Title should not exceed 255 characters"));
            exit;
        }

        $existingTitleStmt = $db->prepare("SELECT id FROM watchlist WHERE title = :title AND id != :id");
        $existingTitleStmt->bindValue(':title', $title, SQLITE3_TEXT);
        $existingTitleStmt->bindValue(':id', $id, SQLITE3_INTEGER);
        $existingTitleResult = $existingTitleStmt->execute();
        $existingTitleRow = $existingTitleResult->fetchArray();
        
        if ($existingTitleRow) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(array("error" => "Title already exists"));
            exit;
        }

        $stmt = $db->prepare("UPDATE watchlist SET title = :title, type = :type WHERE id = :id");
        $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
        $stmt->bindValue(':title', $title, SQLITE3_TEXT);
        $stmt->bindValue(':type', $type, SQLITE3_TEXT);
        $stmt->execute();

        $db->close();
        
        // echo json_encode(array("success" => true));
        if(isset($_SESSION['prev_page']) && !empty($_SESSION['prev_page'])) {
            header("Location: " . $_SESSION['prev_page']);
        } else {
            header("Location: /");
        }
        exit;
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(array("error" => "Internal Server Error"));
    }
} else {
    header("Location: /");
    exit;
}

?>