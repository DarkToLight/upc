<?php
namespace upc\validate;

use tp51\Validate;

class Role extends Validate
{
    protected $rule = [
        'id' => 'require|number',
        'name' => "require",
        'direction' => 'require|in:1,-1',
        'description' => 'require',
        'role_id' => 'require'
    ];
    protected $message = [

        'id.require' => '{"code": -1,"msg":"id不能为空"}',
        'id.number' => '{"code": -1,"msg":"id只能为数字"}',

        'name.require' => '{"code": -1,"msg":"名称不能为空"}',
        'direction.require' => '{"code": -1,"msg":"授权方向不能为空"}',
        'direction.in' => '{"code": -1,"msg":"授权方向设置错误"}',
        'role_id.require' => '{"code": -1,"msg":"role_id参数不能为空"}',
    ];
    public $scene = [
        'create' => ['name', 'direction'],
        'delete' => ['id'],
        'update' => ['id','name', 'direction'],
        'read' => ['id'],
        'exclude' => ['role_id']
    ];
}
