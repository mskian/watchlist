<?php

ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);

$_SESSION['prev_page'] = "http" . (($_SERVER['SERVER_PORT'] == 443) ? "s://" : "://") . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('X-Content-Type-Options: nosniff');
header('Strict-Transport-Security: max-age=63072000');
header('X-Robots-Tag: noindex, nofollow', true);

$error = "";
$apiKey = "";
if (!isset($_SESSION['form_enabled'])) {
    $_SESSION['form_enabled'] = false;
}
$isFormSubmitted = isset($_POST['submit_button']); 
$PostSubmitted = isset($_POST['submit_post']); 

function sanitize_input($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES | ENT_HTML5);
}

function generate_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

if ($isFormSubmitted) {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $error = "CSRF token verification failed. Action aborted.";
    } else {
        $apiKey = sanitize_input($_POST['api_key']);

        if (empty($apiKey)) {
            $error = "API Key is required";
        } else {
            // Update your API KEY
            $validApiKey = "<Your API KEY>";
            if ($apiKey === $validApiKey) {
                $_SESSION['form_enabled'] = true;
            } else {
                $error = "Invalid API Key";
            }
        }
    }
}

$_SESSION['csrf_token'] = generate_csrf_token();

$errors = [];
$title = '';

function validateInput($input) {
    $input = trim($input);
    $input = htmlspecialchars($input);
    if (strlen($input) > 100) {
        $input = substr($input, 0, 100);
    }
    return $input;
}

if ($PostSubmitted) {

    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $errors[] = "Action aborted.";
    } else {
        $title = validateInput($_POST["title"]);
        $type = validateInput($_POST["type"]);

        if (empty($title)) {
            $errors[] = "Movie or Series Name is required";
        } elseif (strlen($title) > 255) {
            $errors[] = "Text should not exceed 255 characters";
        }

        $db = new SQLite3('./db/watchlist.db');
        $stmt = $db->prepare("SELECT COUNT(*) FROM watchlist WHERE title = :title");
        $stmt->bindValue(':title', $title);
        $result = $stmt->execute()->fetchArray();
        if ($result[0] > 0) {
            $errors[] = "Movie or Series already exists in the watchlist";
        }

        if (empty($errors)) {
            try {
                $db = new SQLite3('./db/watchlist.db');
            
                $stmt = $db->prepare("INSERT INTO watchlist (title, type) VALUES (:title, :type)");
                
                $stmt->bindValue(':title', $title);
                $stmt->bindValue(':type', $type);
                $stmt->execute();

                $db->close();
                
                header("Location: /");
                exit;
            } catch (Exception $e) {
                $errors[] = "Error adding item to the database";
            }
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="HandheldFriendly" content="True" />
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="theme-color" content="#c7ecee">
<link rel="shortcut icon" href="data:image/x-icon;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAACXBIWXMAAA7EAAAOxAGVKw4bAAABqklEQVQ4jZ2Tv0scURDHP7P7SGWh14mkuXJZEH8cgqUWcklAsLBbCEEJSprkD7hD/4BUISHEkMBBiivs5LhCwRQBuWgQji2vT7NeYeF7GxwLd7nl4knMwMDMfL8z876P94TMLt+8D0U0EggQSsAjwMvga8ChJAqxqjTG3m53AQTg4tXHDRH9ABj+zf6oytbEu5d78nvzcyiivx7QXBwy46XOi5z1jbM+Be+nqVfP8yzuD3FM6rzIs9YE1hqGvDf15cVunmdx7w5eYJw1pcGptC9CD4gBUuef5Ujq/BhAlTLIeFYuyfmTZgeYv+2nPt1a371P+Hm1WUPYydKf0lnePwVmh3hnlcO1uc7yvgJUDtdG8oy98kduK2KjeHI0fzCQINSXOk/vlXBUOaihAwnGWd8V5r1uhe1VIK52V6JW2D4FqHZX5lphuwEE7ooyaN7gjLMmKSwYL+pMnV+MA/6+g8RYa2Lg2RBQbj4+rll7uymLy3coiuXb5PdQVf7rKYvojAB8Lf3YUJUHfSYR3XqeLO5JXvk0dhKqSqQQoCO+s5AIxCLa2Lxc6ALcAPwS26XFskWbAAAAAElFTkSuQmCC" />

<title>Watchlist</title>
<meta name="description" content="Movie and Web Series Watchlist."/>
<?php $current_page = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"; echo '<link rel="canonical" href="'.$current_page.'" />'; ?>
 

<link rel="preconnect" href="https://cdnjs.cloudflare.com">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bulma/0.9.3/css/bulma.min.css" integrity="sha512-IgmDkwzs96t4SrChW29No3NXBIBv8baW490zk5aXvhCD8vuZM3yUSkbyTBcXohkySecyzIrUwiF/qV0cuPcL3Q==" crossorigin="anonymous" referrerpolicy="no-referrer" />
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Roboto+Mono:ital,wght@0,100..700;1,100..700&display=swap" rel="stylesheet">

<style>
     html, body {
        min-height: 100vh;
    }
    body {
        font-family: "Roboto Mono", monospace;
        background-color: #FDA7DF;
        padding-bottom: 20px;
    }
    #quote-container {
        margin: 10px auto;
        border-radius: 10px;
        padding: 20px;
        background-color: #fff;
        font-family: "Roboto Mono", monospace;
    }
    #quote {
        font-family: "Roboto Mono", monospace;
        font-size: 20px;
        margin-bottom: 20px;
        color: #333;
    }
    #author {
        font-family: "Roboto Mono", monospace;
        font-style: italic;
        color: #777;
    }
    #image-container {
        margin-top: 20px;
    }
    #quote-card {
        max-width: 800px;
        margin: 10px auto;
        font-family: "Roboto Mono", monospace;
    }
    .generate-button {
        font-family: "Roboto Mono", monospace;
        background-color: #4CAF50;
        border: none;
        color: white;
        padding: 10px 20px;
        text-align: center;
        text-decoration: none;
        display: inline-block;
        font-size: 16px;
        margin: 4px 2px;
        transition-duration: 0.4s;
        cursor: pointer;
        border-radius: 4px;
    }

    .generate-button:hover {
        background-color: #45a049;
    }
    button {
       display: flex;
       flex-grow: 0.3;
       font-family: "Roboto Mono", monospace;
       font-weight: 600;
       font-size: 14px;
       text-transform: uppercase;
       box-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
       border-radius: 32px;
       padding: 12px;
       -moz-osx-font-smoothing: grayscale;
       -webkit-font-smoothing: antialiased !important;
       -moz-font-smoothing: antialiased !important;
       text-rendering: optimizelegibility !important;
    }
    .pagination {
            font-family: "Roboto Mono", monospace;
            justify-content: center;
            margin-top: 20px;
        }
        .pagination-link {
            color: #007bff;
            font-size: 0.8rem;
            padding: 0.2rem 0.5rem;
        }
        .pagination-link.is-current {
            color: #000;
            font-weight: bold;
    }
    input {
        font-family: "Roboto Mono", monospace;
    }
    select {
        font-family: "Roboto Mono", monospace;
    }
    input[type="checkbox"] {
        appearance: none;
        -webkit-appearance: none;
        -moz-appearance: none;
        width: 15px;
        height: 14px;
        border: 1px solid #ccc;
        border-radius: 3px;
        cursor: pointer;
        outline: none;
        position: relative;
    }

    input[type="checkbox"]:checked {
        background-color: #007bff;
        border-color: #007bff;
    }
    input[type="checkbox"]:checked::before {
        content: '\2714';
        font-size: 13px;
        color: #fff;
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
    }
    button.delete-button {
        font-size: 12px;
        padding: 4px 4px;
    }
</style>
</head>
<body>
    <section class="section">
        <div class="container">
        <div id="quote-card" class="card">
                <div class="card-content">
                <div id="quote-container">
        <?php if (!$_SESSION['form_enabled']): ?>
                 <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                  <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                  <div class="field">
                  <label for="api_key" class="label">Auth Key:</label>
                  <div class="control">
                  <input type="password" class="input" id="api_key" name="api_key" value="<?= htmlspecialchars($apiKey); ?>" autocomplete="current-password">
                  </div>
                  </div>
                  <?php if (!empty($error)): ?>
                  <div class="notification is-danger"><button class="delete" onclick="this.parentNode.remove();"></button><P><?= $error; ?></P></span></div>
                  <?php endif; ?>
                  <div class="field">
                  <div class="control">
                  <input type="submit" class="button is-warning" name="submit_button" value="Submit">
                  </div>
                  </div>
                  </form>
        <?php else: ?>
                    <?php if (!empty($errors)): ?>
                        <div class="notification is-danger">
                            <button class="delete" onclick="this.parentNode.remove();"></button>
                            <?php foreach ($errors as $error): ?>
                                <p><?php echo $error; ?></p>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <div class="field">
                            <label class="label" for="title">Title:</label>
                            <div class="control">
                                <input class="input" type="text" id="title" name="title" value="<?php echo htmlspecialchars($title); ?>">
                            </div>
                        </div><br>
                        <div class="field">
                            <label class="label" for="type">Type:</label>
                            <div class="control">
                                <div class="select">
                                    <select id="type" name="type">
                                        <option value="movie">Movie</option>
                                        <option value="series">Web Series</option>
                                    </select>
                                </div>
                            </div>
                        </div><br>
                        <div class="field">
                            <div class="control">
                                <button class="button is-warning" name="submit_post" type="submit">Add to Watchlist</button>
                            </div>
                        </div>
                    </form>
        <?php endif; ?>
                    <hr>
                    <h2 class="title is-4">Watchlist</h2>
            <ul id="watchlist">
                <?php include 'display.php'; ?>
            </ul>
                </div>
                <br>
            </div>
          </div>
        </div>
    </section>

</body>
</html>