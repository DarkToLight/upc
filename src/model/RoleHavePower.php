<?php
namespace upc\model;

class RoleHavePower extends Base
{
    public function __construct($data = [])
    {
        if (!empty($data)) {
            $mPower = new Power();
            $power = $mPower->where('id', 'eq', $data['power_id'])->find();
            $data['power_zh'] = $power['power_zh'];
            $data['power'] = $power['power'];
        }
        parent::__construct($data);
    }
}