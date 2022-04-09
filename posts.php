<?php
    header('Content-Type: application/json; charset=utf-8');
    require_once('class.db.php');

    $pageSize = 4;
    $index = 0;

    if($_GET['offset'])
        $index = (int)$_GET['offset'];

    if($_GET['count'])
        $pageSize = (int)$_GET['count'];

    if($pageSize <= 0) {
        echo json_encode(array('success' => false, 'error' => 'Not all values provided.'));
        return;
    }
    
    try {
        $db = new Db();
        $posts = $db->getPosts($pageSize, $index);
    } catch(Exception $err) {
        http_response_code(500);
        return;
    }

    echo json_encode($posts);
?>