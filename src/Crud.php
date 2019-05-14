<?php
namespace upc;

use tp51\Db;
use upc\exception\ValidateException;

abstract class  Crud
{
    private  static $validate = null;
    protected static $model = null;
    private $className;
    public function __construct()
    {
        $this->className = basename(get_class($this));
        $modelName = "upc\\model\\{$this->className}";
        $reflection = new \ReflectionClass($modelName);
        self::$model = $reflection->newInstance();
    }

    protected function validate($input, $method)
    {
        if (!self::$validate) {
            $validate = "upc\\validate\\{$this->className}";
            $reflection = new \ReflectionClass($validate);
            self::$validate = $reflection->newInstance();
        }
        if (isset(self::$validate->scene[$method])) {
            $result = self::$validate->scene($method)->check($input);
            if (false === $result) {
                throw new ValidateException(self::$validate->getError());
            }
        }
    }
    public function create($input)
    {
        try {
            $this->validate($input, __FUNCTION__);
            if(isset($input['id'])) {
                # 解决validate unique 新增成功后，传入新增的id，还能新增。
                return ['code' => 0000, 'msg'=>"非法参数id"];
            }
            self::$model->data($input, true)->save();
            self::$model->commit();
            $backData = ['code' => 1, 'msg' => '新增成功'];
            return $backData;
        } catch (ValidateException $e) {
            return json_decode($e->getMessage(), true);
        }catch (\Exception $e) {
            $backData = ['code' => '-1', 'msg' => $e->getMessage()];
            return $backData;
        }
    }
    public function update($input)
    {
        try {
            $this->validate($input, __FUNCTION__);
            self::$model->save($input, $input['id']); # save 会触发修改器
            $backData = ['code' => 1, 'msg' => '更新成功'];
            return $backData;
        } catch (ValidateException $e) {
            return json_decode($e->getMessage(), true);
        }catch (\Exception $e) {
            $backData = ['code' => '-1', 'msg' => "更新失败"];
            return $backData;
        }
    }
    public function read($id)
    {
        try {
            $model = self::$model;
            $data = $model::get($id)->toArray();
            return ['code' => '1', 'msg' => "获取数据成功", 'data' => $data];
        }catch (\Exception $e) {
            return ['code' => '-1', 'msg' => "获取数据失败"];
        }
    }
    public function delete($id)
    {
        try{
            # 使用逗号分隔可实现批量删除
            self::$model->destroy((int)$id);
            Db::commit();
            $backData = ['code' => 1, 'msg' => '删除成功'];
            return $backData;
        } catch(\Exception $e) {
            $backData = ['code' => '-1', 'msg' => "删除失败"];
            return $backData;
        }
    }
    public function retrieve($input, $where='', $order='', $distinctBy = "")
    {
        try {
            $this->validate($input, __FUNCTION__);
            if (!isset($input['limit'])) {
                $limit = 20;
            } else {
                $limit = (int)$input['limit'];
            }
            if (!empty($distinctBy)) {
                $data = self::$model->where($where)->group($distinctBy)->order($order)->paginate($limit)->toArray();
            } else {
                $data = self::$model->where($where)->order($order)->paginate($limit)->toArray();
            }
            return ['code' => 1, 'msg' => '获取数据成功', 'data' => $data['data'], 'count' => $data['total']];
        } catch (ValidateException $e) {
            return json_decode($e->getMessage(), true);
        }catch (\Exception $e) {
            $backData = ['code' => '-1', 'msg' => "获取数据失败"];
            return $backData;
        }
    }
}