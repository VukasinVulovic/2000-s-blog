<?php
    header('Content-Type: application/json; charset=utf-8');
    require_once('class.db.php');

    if(strlen($_GET['author']) < 0 || strlen($_GET['token']) < 0 || strlen($_GET['title']) < 0 || strlen($_GET['body']) < 0 || strlen($_GET['images']) < 0) {
        echo json_encode(array('success' => false, 'error' => 'Not all arguments have values.'));
        return;
    }

    $author = $_GET['author'];
    $token = $_GET['token'];
    $title = $_GET['title'];
    $body = $_GET['body'];
    $photos = explode(',', $_GET['images']);

    try {
        $db = new Db();
        $res = $db->createPost($author, $token, $title, $body, $photos);
    } catch(Error $err) {
        http_response_code(500);
        return;
    }
    
    echo json_encode($res['post']->toArray());
?>