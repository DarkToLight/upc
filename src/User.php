<?php
namespace upc;

use upc\model\RoleHavePower;
use upc\model\UserAssignRole;

class User
{
    protected $power;
    protected $userId;
    public function __construct($userId)
    {
        $this->userId = $userId;
    }
    /**
     * 获取用户所分配的角色
     * @return array 返回分配角色的数组
     */
    public function getAssignRole()
    {
        try{
            $mUserAssignRole = new UserAssignRole();
            $roleList = $mUserAssignRole->where(['user_id' => $this->userId])->select()->toArray();
            return ['code'=>-1, 'msg' => '获取成功', 'data' => $roleList];
        }catch (\Exception $e) {
            return ['code'=>-1, 'msg' => $e->getMessage()];
        }
    }

    /**
     * 获取用户所有权限
     * @return array 权限数组  正向授权数组和反向授权数组
     */
    public function getPower()
    {
        try{
            $mRoleHavePower = new RoleHavePower();
            $powerList = ['allow' => array(), 'deny' => array()];

            $roleList = $this->getAssignRole();
            foreach ($roleList['data'] as $key => $value) {
                if ($value['status'] != 1) {
                    continue;
                }
                $powers = $mRoleHavePower->where('role_id', 'eq', $value['role_id'])->select()->toArray();
                switch ($value['role_direction']){
                    case -1:
                        $powerList['deny'] = array_merge($powerList['deny'], $powers);
                        break;
                    case 1:
                        $powerList['allow'] = array_merge($powerList['allow'], $powers);
                        break;
                }
            }
            return $powerList;
        } catch (\Exception $e) {
            return ['code'=>-1, 'msg' => $e->getMessage()];
        }
    }

    /**
     *  判断用户是否拥有权限,此处角色分为正向和反向角色。此处要分为两个数组，正向授权数组，和反向授权数组
     *  1. 两个以上反向授权的角色。反向权限叠加。
     *  2. 两个以上的正向授权角色。正向权限叠加。
     *  3. 既有正向角色，又又反向角色，以反向角色为主，若正向包含反向的授权，则从反向授权中移除该权限
     * @param $power 权限名称
     * @return array|bool
     */
    public function isHavePower($power)
    {
        try{
            $powerList = $this->getPower();
            $allow = array_column($powerList['allow'], 'power');
            $deny = array_column($powerList['deny'], 'power');
            if (!empty($deny) && !empty($allow)) {
                $finalPower = array_diff($deny, array_intersect($allow, $deny));
                if(!in_array($power, $finalPower)){
                    return true;
                } else {
                    return false;
                }
            }else if (empty($deny)) {
                // 全是正向授权
                if(in_array($power, $allow)){
                    return true;
                } else {
                    return false;
                }
            }else if (empty($allow)) {
                // 全是反向授权
                if(!in_array($power, $deny)){
                    return true;
                } else {
                    return false;
                }
            } else {
                return false;
            }
        } catch (\Exception $e) {
            return ['code'=>-1, 'msg' => $e->getMessage()];
        }
    }
}