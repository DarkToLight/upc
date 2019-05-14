<?php
namespace upc\model;

use tp51\Model;

abstract class Base extends Model
{
    /**
     * 获取空模型
     */
    public  function getEmptyObj()
    {
        $field = $this->getTableFields();
        $array = [];
        foreach ($field as $item) {
            $array[$item] = null;
        }
        return $array;
    }
}