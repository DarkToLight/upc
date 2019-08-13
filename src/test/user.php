<?php
require "..\\..\\vendor\\autoload.php";

use upc\Admin;

$result = system('git diff');
var_dump($result);
exit;
$user = new Admin(0);
$result = $user->create(['account' => 'guoliang', 'password' => '123456', 'avatar' => 'this is a image']);
var_dump($result);
$result = $user->update(['id' => 6, 'account' => 'xiaobao']);
$result = $user->update(['id' => 6, 'avatar' => 'x']);
$result = $user->update(['id' => 6,  'account' => '', 'status' => '1']);
var_dump($result);
$u = new Admin(6);
$result = $u->isHavePower("hello/world");
var_dump($result);