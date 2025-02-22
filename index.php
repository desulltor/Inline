<?php
try {
    $pdo = new PDO('mysql:host=MySQL-8.2;dbname=hh', 'root', '');
    $pdo->exec("SET NAMES 'utf8'");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Невозможно установить соединение с БД: " . $e->getMessage();
    die();
}

$posts_url = "https://jsonplaceholder.typicode.com/posts";
$comments_url = "https://jsonplaceholder.typicode.com/comments";

function loadJsonData($url) {
    $json = file_get_contents($url);
    return json_decode($json, true);
}

$posts_data = loadJsonData($posts_url);
$comments_data = loadJsonData($comments_url);

if ($posts_data === null || $comments_data === null) {
    die("Ошибка загрузки данных. Проверьте подключение к интернету.");
}

$loaded_posts = 0;
$loaded_comments = 0;

$sql_insert_user = $pdo->prepare("INSERT INTO users (id) VALUES (:id)
    ON DUPLICATE KEY UPDATE");
$sql_insert_post = $pdo->prepare("
    INSERT INTO post (id, user_id, title, body) VALUES (:id, :user_id, :title, :body)
    ON DUPLICATE KEY UPDATE
    user_id = VALUES(user_id), title = VALUES(title), body = VALUES(body)
");
$sql_insert_comment = $pdo->prepare("
    INSERT INTO comments (id, post_id, name, email, body) VALUES (:id, :post_id, :name, :email, :body)
    ON DUPLICATE KEY UPDATE
    post_id = VALUES(post_id), name = VALUES(name), email = VALUES(email), body = VALUES(body)
");

foreach ($posts_data as $post) {
    $id = $post['id'];
    $user_id = $post['userId'];
    $title = $post['title'];
    $body = $post['body'];

    $sql_insert_post->bindParam(':id', $id, PDO::PARAM_INT);
    $sql_insert_post->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $sql_insert_post->bindParam(':title', $title, PDO::PARAM_STR);
    $sql_insert_post->bindParam(':body', $body, PDO::PARAM_STR);

    $sql_insert_user->bindParam(':id', $user_id, PDO::PARAM_INT);

    try {
        $sql_insert_post->execute();
        $loaded_posts++;
    } catch (PDOException $e) {
        echo "Error adding/updating post with ID " . $id . ": " . $e->getMessage() . "\n";
    }
}
foreach ($comments_data as $comment) {
    $id = $comment['id'];
    $post_id = $comment['postId'];
    $name = $comment['name'];
    $body = $comment['body'];
    $email = $comment['email'];

    $sql_insert_comment->bindParam(':id', $id, PDO::PARAM_INT);
    $sql_insert_comment->bindParam(':post_id', $post_id, PDO::PARAM_INT);
    $sql_insert_comment->bindParam(':name', $name, PDO::PARAM_STR);
    $sql_insert_comment->bindParam(':body', $body, PDO::PARAM_STR);
    $sql_insert_comment->bindParam(':email', $email, PDO::PARAM_STR);

    try {
        $sql_insert_comment->execute();
        $loaded_comments++;
    } catch (PDOException $e) {
        echo "Error adding/updating comment with ID " . $id . ": " . $e->getMessage() . "\n";
    }
}

echo "<script>console.log(`Загружено постов: ` + $loaded_posts + `и ` + $loaded_comments + `комментариев`)</script>";
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Поиск по БД</title>
</head>
<body>
<h1>Поисковой запрос</h1>
    <form method="post">
        <input type="text" name="search">
        <input type="submit" value="Найти">
    </form>
<?php
    if(isset($_POST['search'])){
        $str = $_POST['search'];
        $count = 0;
    if (strlen($str) >= 3){
        foreach ($comments_data as $comment) {
            $pos = strpos($comment['body'], $str);
            if ($pos) {
                $id_post = $comment['postId'];
                foreach ($posts_data as $post) {
                    if ($post['id'] == $id_post) {
                        echo '<h1> Статья: ' . $post['title'] . '</h1>';
                    }
                }
                echo '<p><b>Комментарий: </b>' . $comment['body'] . '</p>';
                $count++;
            }
        }
        if ($count == 0) {
            echo '<h2>По данному запросу ничего не найдено</h2>';
        }
    }else{
        echo '<h2>Введите минимум 3 символа</h2>';
    }
    }
?>
</body>
</html>
