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
        if (!isset($input['name'])) {
            return ['code' => -1, 'msg' => '角色名称(name)不能为空'];
        }
        if ($this->model->where('name', 'eq', $input['name'])->find()) {
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
                $role = $this->model->where('id', 'eq', $item)->find();
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
     * 获取一个角色最终互斥的所有角色
     * @param $roleId 角色id
     * @return array
     */
    protected  function getFinalExcludeRole($roleId)
    {
        $mRoleExcludeRole = new RoleExcludeRole();
        $allExclude = $mRoleExcludeRole
            ->where('role_id2', 'eq', $roleId)
            ->whereOr('role_id', 'eq', $roleId)
            ->select()->toArray();
        $finalExclude = [];
        foreach ($allExclude as $item) {
            if ($item['role_id'] == $roleId) {
                array_push($finalExclude, $item['role_id2']);
            } else {
                array_push($finalExclude, $item['role_id']);
            }
        }
        return $finalExclude;
    }
    /**
     * 配置互斥角色
     * @param array $roleIds
     * @param $roleId
     * @return array
     */
    public function exclude(Array $roleIds, $roleId)
    {
        $mRoleExcludeRole = new RoleExcludeRole();
        try{
            $data = array();
            $role = $this->model->where('id', 'eq', $roleId)->find();
            if (empty($role)) {
                return ['code' => -1, 'msg' => "角色不存在"];
            }
            $delExclude = array_diff($this->getFinalExcludeRole($roleId), $roleIds);
            $mRoleExcludeRole->startTrans();

            $mRoleExcludeRole
                ->where('role_id', 'eq', $roleId)
                ->whereIn('role_id2', $delExclude)
                ->delete();
            $mRoleExcludeRole
                ->where('role_id2', 'eq', $roleId)
                ->whereIn('role_id', $delExclude)
                ->delete();
            foreach ($roleIds as $item) {
                if ($roleId == $item) {
                    return ['code' => -1, 'msg' => "不能和自己互斥"];
                }
                $role = $this->model->where('id', 'eq', $item)->find();
                if(empty($role)) {
                    return ['code' => -1, 'msg' => "互斥的{$item}角色不存在"];
                }
                //$sql = "select * from wx_role_exclude_role where role_id={$roleId} and role_id2={$item} or role_id2={$roleId} and role_id={$roleId}";
                $excludeRole = $mRoleExcludeRole
                    ->where(['role_id' => $roleId, 'role_id2' => $item])
                    ->find();
                $excludeRole2 = $mRoleExcludeRole
                    ->where(['role_id2' => $roleId, 'role_id' => $item])
                    ->find();
                if (empty($excludeRole) && empty($excludeRole2)) {
                    $data[] = ['role_id' => $roleId, 'role_id2' => $item];
                }
            }
            $mRoleExcludeRole->insertAll($data);
            $mRoleExcludeRole->commit();
            return ['code' => 1, 'msg' => '角色互斥组织成功'];
        } catch (\Exception $e) {
            $mRoleExcludeRole->rollback();
            return ['code' => -1, 'msg' => $e->getMessage()];
        }
    }
    public function getExcludeRole(int $roleId)
    {
        try{
            $mRole = new model\Role();
            $role = $mRole->whereIn('id', $this->getFinalExcludeRole($roleId))->select()->toArray();
            return ['code' => 1, 'data' => $role];
        }catch (\Exception $exception) {
            return ['code' => -1, 'msg' => $exception->getMessage()];
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