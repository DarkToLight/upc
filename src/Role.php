<?php
namespace upc;

use tp51\Db;
use upc\model\RoleExcludeRole;
use upc\model\RoleHavePower;
use upc\model\UserAssignRole;
use upc\model\User;

final class Role extends Crud
{
    protected $mUserAssignRole;
    protected $mRoleExcludeRole;
    protected $mRoleHavePower;
    public function __construct()
    {
        $this->mUserAssignRole = new UserAssignRole();
        $this->mRoleExcludeRole = new RoleExcludeRole();
        $this->mRoleHavePower = new RoleHavePower();
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
     * @param array $roleId
     * @param $userId
     * @return array
     */
    public function assign(Array $roleId, $userId)
    {
        try{
            $data = array();
            $mUser = new User();
            $user = $mUser->field('account')->where('id', 'eq', $userId);
            if (empty($user)) {
                return ['code' => -1, 'msg' => '用户不存在'];
            }
            $mRole = new model\Role();
            $excludeAssigned = $this->mUserAssignRole->field('role_id')->where('user_id', 'eq', $userId)->select()->toArray();
            $excludeAssigned = array_column($excludeAssigned, 'role_id');
            foreach ($roleId as $item) {
                $role = $this->model->where('id', 'eq', $item)->find();
                if (empty($role)) {
                    return ['code' => -1, 'msg' => '角色不存在'];
                }
                $excludeRole = $this->mRoleExcludeRole->select()->toArray();

                foreach ($excludeRole as $value) {
                    $result = array_intersect($value,array_merge($excludeAssigned,$roleId));
                    if(count($result) > 1){
                        $excludeRoleInfo = $mRole
                            ->field('id,name')
                            ->where('id', 'eq', $value['role_id'])
                            ->whereOr('id', 'eq', $value['role_id2'])
                            ->select()
                            ->toArray();
                        $role0 = $excludeRoleInfo[0]['name'];
                        $role1 = $excludeRoleInfo[1]['name'];
                        return ['code' => -1, 'msg' => "【{$role0}】与【{$role1}】互斥"];
                    }
                }
                $exits = $this->mUserAssignRole->where(['role_id' => $item, 'user_id' => $userId])->find();
                if (empty($exits)) {
                    $data[] = ['role_id' => $item, 'user_id' => $userId];
                }
            }
            $this->mUserAssignRole->insertAll($data);
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
            $this->mUserAssignRole->startTrans();
            foreach ($roleId as $item) {
                $this->mUserAssignRole->where(['role_id' => $item, 'user_id' => $userId])->delete();
            }
            $this->mUserAssignRole->commit();
            return ['code' => 1, 'msg' => '回收成功'];
        }catch (\Exception $e) {
            $this->mUserAssignRole->rollback();
            return ['code' => -1, 'msg' => $e->getMessage()];
        }
    }
    public function revokeAll($userId)
    {
        $this->mUserAssignRole->where(['user_id' => $userId])->delete();
    }
    /**
     * 获取一个角色最终互斥的所有角色
     * @param $roleId 角色id
     * @return array
     */
    protected  function getFinalExcludeRole($roleId)
    {
        $allExclude = $this->mRoleExcludeRole
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
        try{
            if (count($this->mUserAssignRole->whereIn('role_id', $roleIds)->select()) > 1) {
                return ['code' => -1, 'msg' => "要互斥的角色被用户同时扮演。"];
            }
            $data = array();
            $role = $this->model->where('id', 'eq', $roleId)->find();
            if (empty($role)) {
                return ['code' => -1, 'msg' => "角色不存在"];
            }
            $delExclude = array_diff($this->getFinalExcludeRole($roleId), $roleIds);
            Db::startTrans();

            $this->mRoleExcludeRole
                ->where('role_id', 'eq', $roleId)
                ->whereIn('role_id2', $delExclude)
                ->delete();
            $this->mRoleExcludeRole
                ->where('role_id2', 'eq', $roleId)
                ->whereIn('role_id', $delExclude)
                ->delete();
            foreach ($roleIds as $item) {
                if ($roleId == $item) {
                    Db::rollback();
                    return ['code' => -1, 'msg' => "不能和自己互斥"];
                }
                $role = $this->model->where('id', 'eq', $item)->find();
                if(empty($role)) {
                    Db::rollback();
                    return ['code' => -1, 'msg' => "互斥的{$item}角色不存在"];
                }
                //$sql = "select * from wx_role_exclude_role where role_id={$roleId} and role_id2={$item} or role_id2={$roleId} and role_id={$roleId}";
                $excludeRole = $this->mRoleExcludeRole
                    ->where(['role_id' => $roleId, 'role_id2' => $item])
                    ->find();
                $excludeRole2 = $this->mRoleExcludeRole
                    ->where(['role_id2' => $roleId, 'role_id' => $item])
                    ->find();
                if (empty($excludeRole) && empty($excludeRole2)) {
                    $data[] = ['role_id' => $roleId, 'role_id2' => $item];
                }
            }
            $this->mRoleExcludeRole->insertAll($data);
            Db::commit();
            return ['code' => 1, 'msg' => '角色互斥组织成功'];
        } catch (\Exception $e) {
            Db::rollback();
            return ['code' => -1, 'msg' => $e->getMessage()];
        }
    }
    public function getExcludeRole($roleId)
    {
        try{
            $mRole = new model\Role();
            $role = $mRole->whereIn('id', $this->getFinalExcludeRole($roleId))->select();
            if (!empty($role)) {
                $role = $role->toArray();
            } else {
                $role = [];
            }
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
            $powerList = $this->mRoleHavePower->where('role_id', 'eq', $roleId)->select()->toArray();
            return ['code' => 1, 'data' => $powerList];
        }catch (\Exception $e) {
            return ['code' => -1, 'msg' => $e->getMessage()];
        }
    }
    public function delete($id)
    {
        Db::startTrans();
        $where = ['role_id' => $id];
        $this->mUserAssignRole->where($where )->delete(); // 解除用户分配到的角色
        $this->mRoleHavePower->where($where)->delete(); // 删除角色所有的权限
        $this->mRoleExcludeRole->where($where)->whereOr(['role_id2' => $id])->delete(); // 删除相关的互斥关系
        return parent::delete(intval($id));
    }
}