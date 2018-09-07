<?php
namespace app\admin\model;
use think\Model;
class Forum extends Model
{
    function add($data)
    {
        $result = $this->isUpdate(false)->allowField(true)->save($data);
        if ($result) {
            return true;
        } else {
            return false;
        }
    }
    function edit($data)
    {
        $result = $this->isUpdate(true)->allowField(true)->save($data);
        if ($result) {
            return true;
        } else {
            return false;
        }
    }
    public function cattree()
    {
        $tptc = $this->order('id ASC')->select();
        return $this->sort($tptc);
    }
    public function sort($data, $pid = 0, $level = 0)
    {
        static $arr = array();
        foreach ($data as $k => $v) {
            if ($v['pid'] == $pid) {
                $v['level'] = $level;
                $arr[] = $v;
                $this->sort($data, $v['id'], $level + 1);
            }
        }
        return $arr;
    }
    public function getchilrenid($catid)
    {
        $cats = $this->select();
        return $this->_getchilrenid($cats, $catid);
    }
    public function _getchilrenid($cats, $catid)
    {
        static $arr = array();
        foreach ($cats as $k => $v) {
            if ($v['pid'] == $catid) {
                $arr[] = $v['id'];
                $this->_getchilrenid($cats, $v['id']);
            }
        }
        return $arr;
    }
}