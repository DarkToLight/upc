<?php
namespace upc;
use upc\model\RoleExcludeRole;
use upc\model\RoleHavePower;
use upc\model\UserAssignRole;

class Role extends Crud
{
    protected $userAssignRole;
    protected $roleExcludeRole;
    public function __construct()
    {
        $this->userAssignRole = new UserAssignRole();
        parent::__construct();
    }
    public function create($input)
    {
        if (self::$model->where('name', 'eq', $input['name'])->find()) {
            return ['code' => -1, 'msg' => '角色名称已经存在'];
        }
        return parent::create($input);
    }

    /**
     * 将角色分配给用户,用户是否存在在此不做判断。
     * @param array $roleId
     * @param $userId
     * @return array
     */
    public function assign(Array $roleId, $userId)
    {
        try{
            $data = array();
            $mRoleExcludeRole = new RoleExcludeRole();
            foreach ($roleId as $item) {
                $role = self::$model->where('id', 'eq', $item)->find();
                if (empty($role)) {
                    return ['code' => -1, 'msg' => '角色不存在'];
                }
                $excludeRole = $mRoleExcludeRole->select()->toArray();
                foreach ($excludeRole as $value) {
                    $result = array_intersect($value,$roleId);
                    if(count($result) > 1){
                        return ['code' => -1, 'msg' => '存在互斥的角色'.json_encode($value)];
                    }
                }
                $exits = $this->userAssignRole->where(['role_id' => $item, 'user_id' => $userId])->find();
                if (empty($exits)) {
                    $data[] = ['role_id' => $item, 'user_id' => $userId];
                }
            }
            $this->userAssignRole->insertAll($data);
            return ['code' => 1, 'msg' => '分配角色成功'];
        } catch (\Exception $e) {
            return ['code' => -1, 'msg' => $e->getMessage()];
        }
    }
    /**
     * 从用户处回收角色
     * @param array $roleId
     * @param $userId
     * @return array
     * @throws \think\db\exception\PDOException
     */
    public function revoke(Array $roleId, $userId)
    {
        try{
            $this->userAssignRole->startTrans();
            foreach ($roleId as $item) {
                $this->userAssignRole->where(['role_id' => $item, 'user_id' => $userId])->delete();
            }
            $this->userAssignRole->commit();
            return ['code' => 1, 'msg' => '回收成功'];
        }catch (\Exception $e) {
            $this->userAssignRole->rollback();
            return ['code' => -1, 'msg' => $e->getMessage()];
        }
    }

    /**
     * 配置互斥角色
     * @param array $roleIds
     * @param $roleId
     * @return array
     */
    public function exclude(Array $roleIds, $roleId)
    {
        try{
            $data = array();
            $mRoleExcludeRole = new RoleExcludeRole();
            $role = self::$model->where('id', 'eq', $roleId)->find();
            if (empty($role)) {
                return ['code' => -1, 'msg' => "角色不存在"];
            }
            foreach ($roleIds as $item) {
                if ($roleId == $item) {
                    return ['code' => -1, 'msg' => "不能和自己互斥"];
                }
                $role = self::$model->where('id', 'eq', $item)->find();
                if(empty($role)) {
                    return ['code' => -1, 'msg' => "互斥的{$item}角色不存在"];
                }
                $excludeRole = $mRoleExcludeRole->where(['role_id' => $roleId, 'role_id2' => $item])->find();
                if (empty($excludeRole)) {
                    $data[] = ['role_id' => $roleId, 'role_id2' => $item];
                }
            }
            $mRoleExcludeRole->insertAll($data);
            return ['code' => 1, 'msg' => '角色互斥组织成功'];
        } catch (\Exception $e) {
            return ['code' => -1, 'msg' => $e->getMessage()];
        }
    }

    /**
     * 获取角色权限列表
     * @param $roleId
     */
    public function getPower($roleId)
    {
        try{
            $mRoleHavePower = new RoleHavePower();
            $powerList = $mRoleHavePower->where('role_id', 'eq', $roleId)->select()->toArray();
            return ['code' => 1, 'data' => $powerList];
        }catch (\Exception $e) {
            return ['code' => -1, 'msg' => $e->getMessage()];
        }
    }
}