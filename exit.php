<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (session_id() !== '') {
    session_destroy();
    $_SESSION = [];

    echo "Session destroyed successfully.";
} else {
    echo "No active session to destroy.";
}

?>