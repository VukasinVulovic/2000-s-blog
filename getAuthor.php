<?php
    header('Content-Type: application/json; charset=utf-8');
    require_once('class.db.php');

    $id = $_GET['id'];

    if(strlen($id) <= 0) {
        echo json_encode(array('success' => false, 'error' => 'Not all values provided.'));
        return;
    }
    
    try {
        $db = new Db();
        $author = $db->getAuthor($id);
    } catch(Exception $err) {
        http_response_code(500);
        return;
    }

    echo json_encode($author->toArray());
?>