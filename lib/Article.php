<?php
/**
 * Created by PhpStorm.
 * User: DennyLee
 * Date: 2018/8/16
 * Time: 17:42
 */
require_once __DIR__.'/ErrorCode.php';
class Article{

    private $_db;

    /**
     * Article constructor.
     * @param PDO $_db
     */
    public function __construct($_db){
        $this->_db = $_db;
    }

    /**
     * C-create article
     * @param $title
     * @param $content
     * @param $user_id
     * @return array
     * @throws Exception
     * @internal param $userId
     */
    public function create($title, $content, $user_id){
        if(empty($title)){
            throw new Exception('Title is required!',ErrorCode::TITLE_CANNOT_EMPTY);
        }
        if(empty($content)){
            throw new Exception('Content is required!',ErrorCode::CONTENT_CANNOT_EMPTY);
        }
        $sql = 'INSERT INTO `article`(`title`,`content`,`user_id`,`created_at`)VALUES(:title,:content,:user_id,:created_at )';
        $created_at = time();
        $stmt = $this->_db->prepare($sql);
        $stmt->bindParam(':title',$title);
        $stmt->bindParam(':content',$content);
        $stmt->bindParam(':user_id',$user_id);
        $stmt->bindParam(':created_at',$created_at);
        if (!$stmt->execute()){
            throw new Exception('Report failed',ErrorCode::REPORT_FAIL);
        }
        return [
            'article_id'=> $this->_db->lastInsertId(),
            'title'=>$title,
            'content'=>$content,
            'created_at'=>$created_at
        ];
    }

    /**
     * view article
     * @param $article_id
     * @return mixed
     * @throws Exception
     */
    public function view($article_id){
        if(empty($article_id)){
            throw new Exception("Article ID is required!",ErrorCode::CASE_ID_CANNOT_EMPTY);
        }
        $sql = 'SELECT * FROM `article` WHERE `article_id` = :article_id';
        $stmt = $this->_db->prepare($sql);
        $stmt->bindParam('article_id',$article_id);
        $stmt->execute();
        $article = $stmt->fetch(PDO::FETCH_ASSOC);
        if(empty($article)){
            throw new Exception('Article Not Found!',ErrorCode::CASE_NOT_FOUND);
        }
        return $article;
    }

    /**
     * U-edit article
     * @param $article_id
     * @param $title
     * @param $content
     * @param $user_id
     * @return array
     * @throws Exception
     */
    public function edit($article_id,$title,$content,$user_id){
        $article = $this->view($article_id);
        if ($article['user_id'] !== $user_id){
            throw new Exception('No permission to edit this article',ErrorCode::PERMISSION_DENIED);
        }
        $title = empty($title)?$article['title']:$title;
        $content = empty($content)?$article['content']:$content;
        if ($title===$article['title'] && $content===$article['content']){
            require $article;
        }
        $sql='UPDATE `article` SET `title`=:title,`content` =:content WHERE `article_id`=:article_id';
        $stmt = $this->_db->prepare($sql);
        $stmt->bindParam(':title',$title);
        $stmt->bindParam(':content',$content);
        $stmt->bindParam(':article_id',$article_id);
        if (!$stmt->execute()){
            throw new Exception('Edit fauled!',ErrorCode::CASE_EDIT_FAIL);
        }
        return[
            'article_id'=>$article_id,
            'title'=>$title,
            'content'=>$content,
            'created_at'=>$article['created_at']
        ];
    }

    /** D-delete Article
     * @param $article_id
     * @param $user_id
     * @return bool
     * @throws Exception
     */
    public function delete($article_id,$user_id){

        $article = $this->view($article_id);
        if ($article['user_id'] !== $user_id){
            throw new Exception('No permission to edit this article',ErrorCode::PERMISSION_DENIED);
        }
        if(empty($article_id)) {
            throw new Exception('Article ID is required', ErrorCode::CASE_ID_CANNOT_EMPTY);
        }
        $sql = 'DELETE FROM `article` WHERE `article_id`=:article_id AND `user_id`=:user_id';
        $stmt = $this->_db->prepare($sql);
        $stmt->bindParam(':article_id',$article_id);
        $stmt->bindParam(':user_id',$user_id);
        if (false === $stmt->execute()){
            throw new Exception('Delete failed!',ErrorCode::CASE_DELETE_FAIL);
        }
        return true;
    }

    /**
     * @param $user_id
     * @param int $page
     * @param int $size
     * @return array
     * @throws Exception
     */
    public function getList($user_id,$page = 1,$size = 10){
        if($size>100){
            throw new Exception("The maximum page size is 100",ErrorCode::PAGE_SIZE_TO_BIG);
        }
        $sql = 'SELECT * FROM `article` WHERE `user_id`=:user_id LIMIT :limit,:offset';
        $limit = ($page - 1) * $size;
        $limit = $limit < 0 ? 0: $limit;
        $stmt = $this->_db->prepare($sql);
        $stmt->bindParam(':user_id',$user_id);
        $stmt->bindParam(':limit',$limit);
        $stmt->bindParam(':offset',$size);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $data;
    }

}