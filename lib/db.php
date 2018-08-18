<?php
/**
 * 连接数据库
 * Created by PhpStorm.
 * User: DennyLee
 * Date: 2018/8/16
 * Time: 17:42
 */

$pdo = new PDO('mysql:host=localhost;dbname=restful','root','DRsXT5ZJ6Oi55LPQ');
$pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES,false);
return $pdo;