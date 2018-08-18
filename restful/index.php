<?php
/**
 * Created by PhpStorm.
 * User: DennyLee
 * Date: 2018/8/17
 * Time: 11:25
 */
require __DIR__ . '/../lib/User.php';
require __DIR__ . '/../lib/Article.php';
$pdo = require __DIR__ . '/../lib/db.php';

class Restful{
    private $_user;
    private $_article;
    private $_requestMethod;
    private $_resourceName;
    private $_id;
    private $_allowResources =['users','articles'];
    private $_allowRequestMethods = ['GET','POST','PUT','DELETE'];
    private $_statusCodes = [
        200 =>'OK',
        204 =>'No Content',
        400 =>'Bad Request',
        403 =>'Forbidden',
        404 =>'Not Found',
        405 =>'Method Not Allowed',
        500 =>'Server Internal Error'
    ];

    /**
     * Restful constructor 构造函数.
     * @param $_user
     * @param $_article
     * @internal param $user
     * @internal param $article
     */
    public function __construct(User $_user,Article $_article)
    {
        $this->_user = $_user;
        $this->_article = $_article;
    }


    /**
     *
     */
    public function run(){
        try{
            $this->_setupRequestMethod();
            $this->_setupResource();
            if ($this->_resourceName == 'users'){
                $this->_json($this->_handleUser());
            }
            if ($this->_resourceName =='articles'){
                $this->_json($this->_handleCase());
            }
        }catch (Exception $e){
            $this->_json(['error'=>$e->getMessage()],$e->getCode());
        }
    }

    /**
     * Limit request method(Get\Post\Put\Delete)
     * 限制请求方法只能是GET POST PUT DELETE
     * @throws Exception
     */
    private function _setupRequestMethod()
    {
        $this->_requestMethod = $_SERVER['REQUEST_METHOD'];
        if(!in_array($this->_requestMethod,$this->_allowRequestMethods)){
            throw new Exception('Invalid Request Method',405);
        }
    }

    /**
     * Handle request URL, return a array. Array[1]=> Resource Path, Array[2]=>id
     * 处理请求路径 返回数组 数组第二参数为请求路径 第三参数为请求的id
     */
    private function _setupResource()
    {
        $path = $_SERVER['PATH_INFO'];
        if (empty($path)){
            throw new Exception('Invalid Request',400);
        }
        $params = explode('/',$path);
        $this->_resourceName = $params[1];
        if(!in_array($this->_resourceName,$this->_allowResources)){
            throw new Exception('Invalid Request',400);
        }
        if(!empty($params[2])){
            $this->_id = $params[2];
        }
    }

    /**
     * get request body parameters 获得请求体参数
     * @return mixed
     * @throws Exception
     */
    private function _getBodyParams()
    {
        $raw = file_get_contents('php://input');
        if(empty($raw)){
            throw new Exception('Wrong Request Parameter',400);
        }
        return json_decode($raw,true);
    }

    /**
     * json util json包装
     * @param $array
     * @param int $code
     */
    private function _json($array,$code=0)
    {
        if ($array === null && $code ===0 ){
            $code = 204;
        }
        if ($array !== null && $code == 0){
            $code = 200;
        }
        header("HTTP/1.1 ".$code." ".$this->_statusCodes[$code]);
        header('Content-Type:application/json:charset=utf-8');
        if($array !==null) {
            echo json_encode($array, JSON_UNESCAPED_UNICODE);
        }
        exit();
    }

    //******************************************************************************************************************
    //USER RESTFUL


    private function _handleUser()
    {
        switch ($this->_requestMethod){
            case 'POST':
                return $this->_handleUserRegister();
            case 'PUT':
                return $this->_handleUserUpdate();
            case 'GET':
                throw new Exception('Request forbidden',403);
            default:
                throw new Exception('Invalid request method!',405);
        }
    }
    /**
     * handle user data request 处理关于自用户的请求
     * @return mixed
     * @throws Exception
     */
    private function _handleUserRegister()
    {
        try{
            $body = $this->_getBodyParams();
            if(empty($body['username'])){
                throw new Exception('Username is required',400);
            }
            if(empty($body['password'])){
                throw new Exception('Password is required',400);
            }
            return $this->_user->register($body['username'],$body['password']);
        }catch (Exception $e){
            if (in_array($e->getCode(),[
                ErrorCode::USERNAME_CANNOT_EMPTY,
                ErrorCode::PASSWORD_CANNOT_EMPTY,
                ErrorCode::USERNAME_OR_PASSWORD_INVALID
            ])){
                throw new Exception($e->getMessage(),400);
            }else{
                throw new Exception($e->getMessage(),500);
            }
        }
    }

    /**
     * @param $PHP_AUTH_USER
     * @param $PHP_AUTH_PW
     * @return mixed
     * @throws Exception
     */
    private function _userLogin($PHP_AUTH_USER, $PHP_AUTH_PW)
    {
        try {
            return $this->_user->login($PHP_AUTH_USER, $PHP_AUTH_PW);
        }catch (Exception $e){
            if (in_array($e->getCode(),[
                ErrorCode::USERNAME_CANNOT_EMPTY,
                ErrorCode::PASSWORD_CANNOT_EMPTY,
                ErrorCode::USERNAME_OR_PASSWORD_INVALID
            ])){
                throw new Exception($e->getMessage(),400);
            }else{
                throw new Exception($e->getMessage(),500);
            }
        }
    }

    /**
     * PUT
     * @return mixed
     * @throws Exception
     */
    private function _handleUserUpdate()
    {
        try{
            if (empty($this->_id)){
                throw new Exception('Invalid Request',400);
            }
            $params = $this->_getBodyParams();
            if (empty($params)||empty($params['password'])) {
                throw new Exception('Password is required',400);
            }
            $user = $this->_userLogin($_SERVER['PHP_AUTH_USER'],$_SERVER['PHP_AUTH_PW']);
            return $this->_user->_userUpdate($user['user_id'],$params['password']);
        }catch (Exception $e){
            if ($e->getCode() == ErrorCode::USER_NOT_FOUND){
                throw new Exception($e->getMessage(),404);
            }
            else{
                throw new Exception($e->getMessage(),400);
            }
        }
    }


    //The end of User Restful Method
    //******************************************************************************************************************
    //Article RESTFUL

    /**
     * handle article data request 处理关于文章的请求
     */
    private function _handleCase()
    {
        switch ($this->_requestMethod){
            case 'POST':
                return $this->_handleCaseCreate();
            case 'PUT':
                return $this->_handleCaseEdit();
            case 'DELETE':
                return $this->_handleCaseDelete();
            case 'GET':
                if (empty($this->_id)){
                    return $this->_handleCaseList();
                }else{
                    return $this->_handleCaseView();
                }
            default:
                throw new Exception('Invalid request method!',405);
        }
    }

    /**
     * POST method,create new article
     * @return array
     * @throws Exception
     */
    private function _handleCaseCreate()
    {
       $body = $this->_getBodyParams();
       if(empty($body['title'])){
           throw new Exception('Title is required!',400);
       }
       if (empty($body['content'])){
           throw new Exception('Content is required',400);
       }
       $user = $this->_userLogin($_SERVER['PHP_AUTH_USER'],$_SERVER['PHP_AUTH_PW']);
       try{
           $article = $this->_article->create($body['title'],$body['content'],$user['user_id']);
           return $article;
       }catch (Exception $e){
           if (in_array($e->getCode(),[
               ErrorCode::TITLE_CANNOT_EMPTY,
               ErrorCode::CONTENT_CANNOT_EMPTY
           ])){
               throw new  Exception($e->getMessage(),401);
           }
           throw new Exception($e->getMessage(),500);
       }
    }

    /**
     * PUT method, edit article
     *
     */
    private function _handleCaseEdit()
    {
        $user = $this->_userLogin($_SERVER['PHP_AUTH_USER'],$_SERVER['PHP_AUTH_PW']);
        try{
            $article = $this->_article->view($this->_id);
            if ($article['user_id'] !== $user['user_id']){
                throw new Exception('No permission to edit article',403);
            }
            $body = $this->_getBodyParams();
            $title = empty($body['title'])?$article['title']:$body['title'];
            $content = empty($body['content'])?$article['content']:$body['content'];
            if($title == $article['title'] && $content ==$article['content']){
                return $article;
            }
            return  $this->_article->edit($article['article_id'],$title,$content,$user['user_id']);
        }catch (Exception $e){
            if($e->getCode()<100) {
                if ($e->getCode() == ErrorCode::CASE_NOT_FOUND) {
                    throw new Exception($e->getMessage(), 404);
                } else {
                    throw new Exception($e->getMessage(), 400);
                }
                }else{
                throw $e;
            }
        }
    }

    /**
     * @return null
     * @throws Exception
     */
    private function _handleCaseDelete()
    {
        $user = $this->_userLogin($_SERVER['PHP_AUTH_USER'],$_SERVER['PHP_AUTH_PW']);
        try{
            $article = $this->_article->view($this->_id);
            if($article['user_id'] !== $user['user_id']){
                throw new Exception('No permission to delete this report',403);
            }
            $this->_article->delete($article['article_id'],$user['user_id']);
            return null;
        }catch (Exception $e){
            if($e->getCode()<100) {
                if ($e->getCode() == ErrorCode::CASE_NOT_FOUND) {
                    throw new Exception($e->getMessage(), 404);
                } else {
                    throw new Exception($e->getMessage(), 400);
                }
            }else{
                throw $e;
            }
        }
    }

    private function _handleCaseList()
    {
        $user = $this->_userLogin($_SERVER['PHP_AUTH_USER'],$_SERVER['PHP_AUTH_PW']);
        $page = isset($_GET['page'])?$_GET['page']:1;
        $size = isset($_GET['size'])?$_GET['size']:10;
        if ($size>100){
            throw new Exception('The maximum list size is 100',400);
        }
        return $this->_article->getList($user['user_id'],$page,$size);
    }

    private function _handleCaseView()
    {
        try{
            return $this->_article->view($this->_id);
        }catch (Exception $exception){
            if($exception->getCode() == ErrorCode::CASE_NOT_FOUND){
                throw new Exception($exception->getMessage(),404);
            }else{
                throw new Exception($exception->getMessage(),500);
            }
        }
    }



    //The end of Article RESTFUL Method
    //******************************************************************************************************************
}


$article = new Article($pdo);
$user = new User($pdo);

$restful = new Restful($user,$article);
$restful->run();