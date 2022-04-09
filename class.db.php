<?php
    require_once('class.config.php');

    function randomString(int $length) {
        $str = '';

        for(; $length > 0; $length--)
            $str .= 'qwertyuiopasdfghjklzxcvbnm1234567890'[rand(0, 35)];

        return $str;
    }
    
    class Author {
        public $id;
        public $fname;
        public $lname;
        public $photo;

        function __construct(string $id, string $fname, string $lname, string $photo) {
            $this->id = $id;
            $this->fname = $fname;
            $this->lname = $lname;
            $this->photo = $photo;
        }

        function toArray() {
            return array(
                'id' => $this->id,
                'fname' => $this->fname,
                'lname' => $this->lname,
                'photo' => $this->photo
            );
        }
    }

    class Post {
        public $id;
        public $author;
        public $title;
        public $body;
        public $photos;
        public $date;

        function __construct(string $id, Author $author, string $title, string $body, array $photos, DateTime $date) {
            $this->id = $id;
            $this->author = $author;
            $this->title = $title;
            $this->body = $body;
            $this->photos = $photos;
            $this->date = $date;
        }

        function toArray() {
            return array(
                'id' => $this->id,
                'author' => $this->author->toArray(),
                'title' => $this->title,
                'body' => $this->body,
                'photos' => $this->photos,
                'date' => $this->date
            );
        }
    }

    class DB {
        public $conn;

        function __construct() {
            $conn = mysqli_connect(Config::$db['hostname'], Config::$db['username'], Config::$db['password'], Config::$db['db']);
        
            if($conn === false)
                throw new Exception('Could not connect do database.');

            $this->conn = $conn;
        }

        function __destruct() {
            mysqli_close($this->conn);
        }

        public function createAuthor(string $fname, string $lname, string $photo) {
            $id = randomString(18);
            $token = randomString(18);
            $salt = randomString(5);
            $hashedToken = hash('sha256', $token . $salt); //sha256 hash token with salt
    
            $author = new Author($id, mysqli_real_escape_string($this->conn, $fname), mysqli_real_escape_string($this->conn, $lname), mysqli_real_escape_string($this->conn, $photo));
        
            //create sql
            $queryAuthors = sprintf('INSERT INTO authors VALUES (\'%s\', \'%s\', \'%s\', \'%s\')', $author->id, $author->fname, $author->lname, $author->photo);    
            $queryAuth = sprintf('INSERT INTO auth VALUES (\'%s\', \'%s\', \'%s\')', $author->id, $hashedToken, $salt);
    
            //exec sql
            $rAuthors = mysqli_query($this->conn, $queryAuthors);
            $rAuth = mysqli_query($this->conn, $queryAuth);
    
            if($rAuthors === false || $rAuth === false)
                throw new Exception('Query Exception, could not execute.');
            
            return array('success' => true, 'author' => $author, 'token' => $token);
        }

        public function isAuthorValid(string $id, string $token) {
            //create sql
            $queryAuth = sprintf('SELECT hashed_token, salt FROM auth WHERE author=\'%s\'', mysqli_real_escape_string($this->conn, $id));    
    
            //exec sql
            $res = mysqli_query($this->conn, $queryAuth);

            if($res->num_rows <= 0)
                throw new Exception('Author with id not found.');

            $row = mysqli_fetch_row($res);    

            return hash('sha256', $token . $row[1]) === $row[0]; //sha256 hash token with salt mathes with hashed token
        }

        public function getAuthor(string $id) {
            //create sql
            $queryAuthor = sprintf('SELECT fname, lname, photo_url FROM authors WHERE id=\'%s\'', mysqli_real_escape_string($this->conn, $id));    

            //exec sql
            $res = mysqli_query($this->conn, $queryAuthor);

            if($res->num_rows <= 0)
                throw new Exception('Author with id not found.');

            $row = mysqli_fetch_row($res);  

            return new Author($id, $row[0], $row[1], $row[2]);
        }

        public function createPost($author_id, $token, $title, $body, $photos) {
            $id = randomString(18);
    
            $author = $this->getAuthor($author_id);

            if($this->isAuthorValid($author_id, $token) === false) 
                throw new Exception('Authentication error, invalid token');

            $date = new DateTime();
            $post = new Post($id, $author, mysqli_real_escape_string($this->conn, $title), mysqli_real_escape_string($this->conn, $body),  $photos, $date);

            $photosSafe = mysqli_real_escape_string($this->conn, join(',', $post->photos));

            //create sql
            $queryPost = sprintf('INSERT INTO posts VALUES (\'%s\', \'%s\', \'%s\', \'%s\', \'%s\', \'%s\')', $post->id, $post->author->id, $post->title, $post->body, $photosSafe, $post->date->format('y-m-d H:i:s'));    
    
            //exec sql
            $rPost = mysqli_query($this->conn, $queryPost);
    
            if($rPost === false)
                throw new Exception('Query Exception, could not execute.');
            
            return array('success' => true, 'post' => $post);
        }

        public function deletePost($author_id, $token, $post_id) {    
            if($this->isAuthorValid($author_id, $token) === false) 
                throw new Exception('Authentication error, invalid token');

            //create sql
            $queryPost = sprintf('DELETE FROM posts WHERE id = \'%s\'', mysqli_real_escape_string($this->conn, $post_id));    
    
            //exec sql
            $rPost = mysqli_query($this->conn, $queryPost);
    
            if($rPost === false)
                throw new Exception('Query Exception, could not execute.');
            
            return array('success' => true);
        }

        public function getPosts(int $pageSize, int $index) {
            //create sql
            $queryPosts = sprintf('SELECT posts.id, title, body, photos, date, authors.id, fname, lname, photo_url, (SELECT count(*) FROM  posts) FROM posts JOIN authors ON posts.author=authors.id GROUP BY posts.id, title, body, photos, date, authors.id, fname, lname, photo_url ORDER BY date DESC LIMIT %d OFFSET %d', $pageSize, $index);    

            //exec sql
            $res = mysqli_query($this->conn, $queryPosts);

            if($res->num_rows <= 0)
                return array('count' => 0, 'total_count' => 0, 'index' => $index, 'posts' => array());

            $rows = mysqli_fetch_all($res);
            $posts = array_map(fn(array $row):Post => new Post($row[0], new Author($row[5], $row[6], $row[7], $row[8]), $row[1], $row[2], explode(',', $row[3]), date_create_from_format('Y-m-d H:i:s', $row[4])), $rows);

            return array('count' => count($posts), 'total_count' => (int)$rows[0][9], 'index' => $index, 'posts' => $posts);
        }
    }    
?>