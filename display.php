<?php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

try {

    $db = new SQLite3('./db/watchlist.db');

    $perPage = 3;
    $currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $offset = ($currentPage - 1) * $perPage;

    $stmt = $db->prepare("SELECT * FROM watchlist ORDER BY id DESC LIMIT :limit OFFSET :offset");
    $stmt->bindValue(':limit', $perPage, SQLITE3_INTEGER);
    $stmt->bindValue(':offset', $offset, SQLITE3_INTEGER);
    $results = $stmt->execute();

    ?>
<?php if (!$_SESSION['form_enabled']): ?>
            <p>Authorization is Required</p>
<?php else: ?>
    <ul>
        <?php while ($row = $results->fetchArray()): ?>
            <?php
            $id = htmlspecialchars($row['id']);
            $title = htmlspecialchars($row['title']);
            $type = htmlspecialchars($row['type']);
            $watched = $row['watched'];
            ?>
            <li>
                <form id="watchlistForm<?= $id ?>" action='status.php' method='POST'>
                    <input type="hidden" name="watched[<?= $id ?>]" value="0">
                    <label class="checkbox">
                        <input type='checkbox' name='watched[<?= $id ?>]' value='1' <?= $watched ? 'checked' : '' ?>>
                        <?= $title ?> (<?= $type ?>)
                    </label>
                </form>
                <br>
                <form action="edit.php" method="GET" style="display: inline;">
                    <input type="hidden" name="id" value="<?= $id ?>">
                    <input type="hidden" name="page" value="<?= $currentPage ?>">
                    <button type="submit" class="button is-success delete-button">Edit</button>
                </form>
                <form action="delete.php" method="POST" style="display: inline;">
                    <input type="hidden" name="delete" value="<?= $id ?>">
                    <input type="hidden" name="page" value="<?= $currentPage ?>">
                    <button type="submit" class="button is-danger delete-button">Delete</button>
                </form>
            </li><br>
        <?php endwhile; ?>
    </ul>

    <script>
        const checkboxes = document.querySelectorAll('input[type="checkbox"]');
        checkboxes.forEach(checkbox => {
            checkbox.addEventListener('change', () => {
                checkbox.closest('form').submit();
            });
        });
    </script>
<?php endif; ?>
    <?php

    $stmt = $db->prepare("SELECT COUNT(*) as count FROM watchlist");
    $totalItems = $stmt->execute()->fetchArray()['count'];
    $totalPages = ceil($totalItems / $perPage);

    if (!$_SESSION['form_enabled']) {
       echo '...';
    } else {

       echo "<hr><nav class='pagination' role='navigation' aria-label='pagination'>";
       echo "<ul class='pagination-list'>";


       if ($currentPage > 1) {
          echo "<li><a class='pagination-link' href='?page=" . ($currentPage - 1) . "'>Previous</a></li>";
       }

       $maxPagesToShow = 5;
       $startPage = max(1, min($totalPages - $maxPagesToShow + 1, $currentPage - floor($maxPagesToShow / 2)));
       $endPage = min($totalPages, $startPage + $maxPagesToShow - 1);

        for ($i = $startPage; $i <= $endPage; $i++) {
          echo "<li><a class='pagination-link" . ($currentPage == $i ? " is-current" : "") . "' href='?page=$i'>$i</a></li>";
        }

        if ($currentPage < $totalPages) {
          echo "<li><a class='pagination-link' href='?page=" . ($currentPage + 1) . "'>Next</a></li>";
        }

        echo "</ul>";
        echo "</nav>";
    }

    $db->close();

} catch (Exception $e) {

    echo "Error: " . htmlspecialchars($e->getMessage());
}

?>