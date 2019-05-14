<?php
namespace upc;

use upc\model\RoleHavePower;
use upc\model\Role;
use upc\model\Power as mPower;

class Power extends Crud
{
    protected $userHavePower;
    public function __construct()
    {
        $this->userHavePower =  new RoleHavePower();
        parent::__construct();
    }
    public function create($input)
    {
        if (!isset($input['power'])) {
            return ['code' => -1, 'msg' => '权限名称不能为空'];
        }
        if (self::$model->where('power', 'eq', $input['power'])->find()) {
            return ['code' => -1, 'msg' => '权限节点已经存在'];
        }
        $input['create_time'] = date("Y-m-d H:i:s");
        return parent::create($input);
    }
    /**
     * 批量分配权限到角色
     * @param array $powerId 权限数组
     * @param $roleId 角色id
     * @return array
     */
    public function assign(Array $powerId, $roleId)
    {
        try{
            $mRole = new Role();
            $role = $mRole->where('id', 'eq', $roleId)->find();
            $data = array();
            if (!empty($role)) {
                $mPower = new mPower();
                foreach ($powerId as $item) {
                    $power = $mPower->where('id', 'eq', $item)->find();
                    $exits = $this->userHavePower->where(['power_id' => $item, 'role_id' => $roleId])->find();
                    if (empty($exits)) {
                        $data[] = ['power_id' => $item, 'role_id' => $roleId];
                    }
                    if (empty($power)) {
                        return ['code' => -1, 'msg' => '权限不存在'];
                    }
                }
            } else {
                return ['code' => -1, 'msg' => '角色不存在'];
            }
            $this->userHavePower->insertAll($data);
            return ['code' => 1, 'msg' => '权限分配成功'];
        } catch (\Exception $e) {
            return ['code' => -1, 'msg' => $e->getMessage()];
        }
    }
    /**
     * 回收权限
     * @param array $powerId 权限id数组
     * @param $roleId
     * @return array
     * @throws \think\db\exception\PDOException
     */
    public function revoke(Array $powerId, $roleId)
    {
        try{
            $this->userHavePower->startTrans();
            foreach ($powerId as $item) {
                $this->userHavePower->where(['power_id' => $item, 'role_id' => $roleId])->delete();
            }
            $this->userHavePower->commit();
            return ['code' => 1, 'msg' => '回收成功'];
        }catch (\Exception $e) {
            $this->userHavePower->rollback();
            return ['code' => -1, 'msg' => $e->getMessage()];
        }
    }
}