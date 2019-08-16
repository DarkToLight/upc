<?php
namespace upc;

use tp51\Db;
use upc\exception\ValidateException;

abstract class  Crud
{
    private   $validate = null;
    protected $model = null;
    private $className;
    public function __construct()
    {
        $this->className = str_replace("upc\\", "",get_class($this));
        $modelName = "upc\\model\\{$this->className}";
        $reflection = new \ReflectionClass($modelName);
        $this->model = $reflection->newInstance();
    }

    protected function validate($input, $method)
    {
        if (!$this->validate) {
            $validate = "upc\\validate\\{$this->className}";
            $reflection = new \ReflectionClass($validate);
            $this->validate = $reflection->newInstance();
        }
        if (isset($this->validate->scene[$method])) {
            $result = $this->validate->scene($method)->check($input);
            if (false === $result) {
                throw new ValidateException($this->validate->getError());
            }
        }
    }
    public function create($input)
    {
        try {
            $this->validate($input, __FUNCTION__);
            if(isset($input['id'])) {
              unset($input['id']);
            }
            $this->model->save($input);
            Db::commit();
            $backData = ['code' => 1, 'msg' => '新增成功', 'id' => $this->model->id];
            return $backData;
        } catch (ValidateException $e) {
            Db::rollback();
            return json_decode($e->getMessage(), true);
        }catch (\Exception $e) {
            Db::rollback();
            $backData = ['code' => '-1', 'msg' => $e->getMessage()];
            return $backData;
        }
    }
    public function update($input)
    {
        try {
            $this->validate($input, __FUNCTION__);
            $this->model->save($input, $input['id']); # save 会触发修改器
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
            $data = $this->model->where("id", 'eq', $id)->find();
            return ['code' => '1', 'msg' => "获取数据成功", 'data' => $data];
        }catch (\Exception $e) {
            return ['code' => '-1', 'msg' => "获取数据失败"];
        }
    }
    public function delete($id)
    {
        try{
            # 使用逗号分隔可实现批量删除
            $this->model->destroy($id);
            Db::commit();
            $backData = ['code' => 1, 'msg' => '删除成功'];
            return $backData;
        } catch(\Exception $e) {
            Db::rollback();
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
                $data = $this->model->where($where)->group($distinctBy)->order($order)->paginate($limit)->toArray();
            } else {
                $data = $this->model->where($where)->order($order)->paginate($limit)->toArray();
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