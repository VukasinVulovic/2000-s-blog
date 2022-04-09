<?php
    header('Content-Type: application/json; charset=utf-8');
    require_once('./class.db.php');

    $fname = 'John';
    $lname = 'Doe';
    $photo = 'https://upload.wikimedia.org/wikipedia/commons/7/73/Sample_Picture.png';

    if(strlen($fname) <= 0 || strlen($lname) <= 0 || strlen($photo) <= 0) {
        echo json_encode(array('success' => false, 'error' => 'Not all values provided.'));
        return;
    }
    
    try {
        $db = new Db();
        $res = $db->createAuthor($fname, $lname, $photo);
    } catch(Error $err) {
        http_response_code(500);
        return;
    }

    $authorInfo = $res['author']->toArray();

    echo json_encode(array('token' => $res['token'], 'id' => $authorInfo['id']));
?>