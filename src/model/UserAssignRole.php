<?php
namespace upc\model;

class UserAssignRole extends Base
{
    public function __construct($data = [])
    {
        if (!empty($data)) {
            $mRole = new Role();
            $role = $mRole->where('id', 'eq', $data['role_id'])->field('name,direction,status')->findOrEmpty();
            $data['role_name'] = $role['name'];
            $data['status'] = $role['status'];
            $data['role_direction'] = $role['direction'];
        }
        parent::__construct($data);
    }
}