<?php
namespace upc\validate;

use tp51\Validate;

class User extends Validate
{
    protected $rule = [
        'id' => 'require|number',
        'account' => "require",
        'password' => 'require',
        'avatar' => 'require',
    ];
    protected $message = [

        'id.require' => '{"code": -1,"msg":"id不能为空"}',
        'id.number' => '{"code": -1,"msg":"id只能为数字"}',

        'account.require' => '{"code": -1,"msg":"账号不能为空"}',
        'account.unique' => '{"code": -1,"msg":"账号已经存在"}',
        'password.require' => '{"code": -1,"msg":"密码不能为空"}',
        'avatar.require' => '{"code": -1,"msg":"请上传头像"}',
    ];
    public $scene = [
        'create' => ['account', 'password', 'avatar'],
        'delete' => ['id'],
        'update' => ['id', 'account', 'password', 'avatar'],
        'read' => ['id']
    ];
}
