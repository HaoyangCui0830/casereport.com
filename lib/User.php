<?php
/**
 * 连接数据库
 * Created by PhpStorm.
 * User: DennyLee
 * Date: 2018/8/16
 * Time: 17:42
 */
require_once __DIR__.'/ErrorCode.php';

class User
{
    private $_db;

    /**
     * User constructor.
     * @param PDO $_db connection
     */
    public function __construct($_db)
    {
        $this->_db =$_db;
    }

    /**
     * User Login
     * @param $username
     * @param $password
     * @return mixed
     * @throws Exception
     */
    public function login($username,$password)
    {
        if(empty($username)){
            throw new Exception('Username is required!',ErrorCode::USERNAME_CANNOT_EMPTY);
        }
        if(empty($password)){
            throw new Exception('Password is required!',ErrorCode::PASSWORD_CANNOT_EMPTY);
        }
        $sql = 'SELECT * FROM `user` WHERE `username`=:username AND `password`=:password';
        $password = $this->_md5($password);
        $stmt = $this ->_db->prepare($sql);
        $stmt->bindParam(':username',$username);
        $stmt->bindParam(':password',$password);
        if(!$stmt->execute()){
            throw new Exception('Sever Internal Error',ErrorCode::SEVER_INTERNAL_ERROR);
        }
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if(empty($user)){
            throw new Exception('Invalid username or password!',ErrorCode::USERNAME_OR_PASSWORD_INVALID);
        }
        unset($user['password']);
        return $user;
    }

    /**
     * User register
     * @param $username
     * @param $password
     * @return array
     * @throws Exception
     */
    public function register($username,$password){
        if(empty($username)){
            throw new Exception('Username is required!',ErrorCode::USERNAME_CANNOT_EMPTY);
        }
        if(empty($password)){
            throw new Exception('Password is required!',ErrorCode::PASSWORD_CANNOT_EMPTY);
        }
       if($this->_isUsernameExists($username)){
            throw new Exception('This username has been used!',ErrorCode::USERNAME_EXSISTS);
        }
        //write into database
        $sql = 'INSERT INTO `user`(`username`,`password`,`created_at`) VALUES (:username,:password,:created_at)';
        $created_at = time();
        $password = $this->_md5($password);
        $stmt = $this ->_db->prepare($sql);
        $stmt->bindParam(':username',$username);
        $stmt->bindParam(':password',$password);
        $stmt->bindParam('created_at',$created_at);
        if(!$stmt->execute()){
            var_dump($this->_db->lastInsertId());
            throw new Exception('Register Fail',ErrorCode::REGISTER_FAIL);
        }
        return[
            'userId'=>$this->_db->lastInsertId(),
            'username' => $username,
            'created_at' => $created_at
        ];
    }

    private function _md5($string,$salt = 'mine'){
        return md5($string.$salt);
    }

    /**
     *
     * @param $username
     * @return bool
     */
    private function _isUsernameExists($username){
        $exist = false;
        $sql = 'SELECT * FROM `user` WHERE `username`=:username';
        $stmt = $this->_db->prepare($sql);
        $stmt->bindParam(':username',$username);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return !empty($result);
    }

    private function _userView($user_id){
        $sql = 'SELECT * FROM `user` WHERE `user_id` =:user_id';
        $stmt = $this->_db->prepare($sql);
        $stmt->bindParam(':user_id',$user_id);
        $stmt->execute();
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        if(empty($data)){
            throw new Exception('User not found',ErrorCode::USER_NOT_FOUND);
        }
        return $data;
    }
    public function _userUpdate($user_id,$password){
        $user = $this->_userView($user_id);
        if (empty($password)){
            throw new Exception('Password is required',ErrorCode::PASSWORD_CANNOT_EMPTY);
        }
        $sql = 'UPDATE `user` SET `password`=:password WHERE `user_id`=:user_id';
        $stmt = $this->_db->prepare($sql);
        $stmt->bindParam(':user_id',$user_id);
        $password = $this->_md5($password);
        $stmt->bindParam(':password',$password);
        if(!$stmt->execute()){
            throw new Exception('Update failed',ErrorCode::USER_UPDATE_FAIL);
        }
        else {
            unset($user['password']);
            return $user;
        }
    }
}
