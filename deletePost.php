<?php
    header('Content-Type: application/json; charset=utf-8');
    require_once('class.db.php');

    if(strlen($_GET['author']) < 0 || strlen($_GET['token']) < 0 || strlen($_GET['post_id']) < 0) {
        echo json_encode(array('success' => false, 'error' => 'Not all arguments have values.'));
        return;
    }

    $author = $_GET['author'];
    $token = $_GET['token'];
    $post_id = $_GET['post_id'];

    try {
        $db = new Db();
        $res = $db->deletePost($author, $token, $post_id);
    } catch(Error $err) {
        http_response_code(500);
        return;
    }
    
    echo array('success' => true);
?>