<?php
require __DIR__.'/lib/User.php';
require __DIR__.'/lib/Article.php';
$pdo = require __DIR__.'/lib/db.php';
$user = new User($pdo);
//print_r($user->register('test1','test1'));
//print_r($user->register('test2','test2'));
//var_dump($user->register('test3','test3'));
//print_r($user->login('test1','test1'));
$article = new Article($pdo);
//print_r($article->create('Hi!This is the title.','This is content.',1));
//print_r($article->view(1));
//print_r($article->edit(2,'This is a edited title1!','This is edited content1',0));
//var_dump($article->delete(1,1));
var_dump($article->getList(1));
