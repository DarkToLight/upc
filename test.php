<?php
require "vendor\\autoload.php";
use upc\Power;
use upc\Role;
use upc\User;

# ========================================================================
$role = new Role();
//$result = $role->create(['name' => '操作', 'description' => 'what', 'direction' => -1]);
//var_dump($result);
//$result = $role->update(['id' => 3, 'name' => '曹操', 'description' => '我激动', 'direction' => 1]);
// $result = $role->delete(3);
//$result = $role->retrieve(['limit' => 30], '', ['id' => 'desc']);
//$result = $role->read(2);
//$result = $role->assign([1,2],14);
//var_dump($result);
//$result = $role->revoke([2,3], 14);
//var_dump($result);
//$result = $role->exclude([2], 1);
//var_dump($result);
$result = $role->getPower(1);
var_dump($result);
# ========================================================================

# ========================================================================
$power = new Power();
//$result = $power->create(['power' => 'user/update', 'power_zh' => '新增用户', 'parent_id' => 0]);
//$result = $power->update(['id' => 3, 'power' => 'user/update', 'power_zh' => '更新用户信息','parent_id' => 0]);
////$result = $power->delete(3);
//$result = $power->read(2);
//$result = $role->retrieve(['limit' => 30], '', ['id' => 'desc']);

//$result = $power->assign([1,2,4],1);
//var_dump($result);
//$result = $power->revoke([2], 1);
//var_dump($result);
# =========================================================================
//
$user = new User(14);
$result = $user->getAssignRole();
var_dump($result);

$result = $user->getPower();
var_dump($result);
$result = $user->isHavePower("admin/user/update");
var_dump($result);
//# =======================================================================
