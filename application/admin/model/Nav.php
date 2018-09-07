<?php
namespace app\admin\model;
use think\Model;
class Nav extends Model
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
    public function navtree()
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
    public function getchilrenid($navid)
    {
        $navs = $this->select();
        return $this->_getchilrenid($navs, $navid);
    }
    public function _getchilrenid($navs, $navid)
    {
        static $arr = array();
        foreach ($navs as $k => $v) {
            if ($v['pid'] == $navid) {
                $arr[] = $v['id'];
                $this->_getchilrenid($navs, $v['id']);
            }
        }
        return $arr;
    }
}