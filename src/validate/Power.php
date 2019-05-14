<?php
namespace upc\validate;

use tp51\Validate;

class Power extends Validate
{
    protected $rule = [
        'id' => 'require|number',
        'limit' => 'require|max:1000',
        'power' => "require",
        'power_zh' => "require",
        'parent_id' => "require|number",
    ];
    protected $message = [
        'limit.require' => '{"code": -1,"msg":"必须限制分页条数"}',
        'limit.lt' => '{"code": -1,"msg":每页限制条数最大为1000"}',

        'power.require' => '{"code": -1,"msg":"名字不能为空"}',

        'power_zh.require' => '{"code": -1,"msg":"权限中文名称不能为空"}',

        'parent_id.require' => '{"code": -1,"msg":"parent_id不能为空"}',
        'parent_id.number' => '{"code": -1,"msg":"parent_id只能为数字"}',
    ];
    public $scene = [
        'create' => ['power', 'power_zh', 'parent_id'],
        'delete' => ['id'],
        'update' => ['id', 'power', 'power_zh', 'parent_id'],
        'read' => ['id']
    ];
}
