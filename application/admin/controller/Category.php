<?php
namespace app\admin\controller;
use think\Controller;
use think\Db;
use app\admin\model\Category as CategoryModel;
class Category extends Common
{
    protected $beforeActionList = ['delsoncat' => ['only' => 'dels']];
    public function index()
    {
        $CategoryMd = new CategoryModel();
        $CategoryList = $CategoryMd->cattree();
        $this->assign('CategoryList', $CategoryList);
        return view();
    }
    public function add()
    {
        $CategoryMd = new CategoryModel();
        if (request()->isPost()) {
            $data = input('post.');
            $data['time'] = time();
            if ($CategoryMd->add($data)) {
                return json(array('code' => 200, 'msg' => '添加成功'));
            } else {
                return json(array('code' => 0, 'msg' => '添加失败'));
            }
        }
        $CategoryList = $CategoryMd->cattree();
        $this->assign('CategoryList', $CategoryList);
        return view();
    }
    public function edit()
    {
        $CategoryMd = new CategoryModel();
        if (request()->isPost()) {
            $data = input('post.');
            if ($CategoryMd->edit($data)) {
                return json(array('code' => 200, 'msg' => '修改成功'));
            } else {
                return json(array('code' => 0, 'msg' => '修改失败'));
            }
        }
        $Category = $CategoryMd->find(input('id'));
        $CategoryList = $CategoryMd->cattree();
        $this->assign(array('CategoryList' => $CategoryList, 'Category' => $Category));
        return view();
    }
    public function dels()
    {
        $dels = Db::name('category')->delete(input('id'));
        if ($dels) {
            return json(array('code' => 200, 'msg' => '删除成功'));
        } else {
            return json(array('code' => 0, 'msg' => '删除失败'));
        }
    }
    public function delsoncat()
    {
        $catid = input('id');
        $CategoryMd = new CategoryModel();
        $sonids = $CategoryMd->getchilrenid($catid);
        if ($sonids) {
            Db::name('category')->delete($sonids);
        }
    }
    public function changeshow()
    {
        if (request()->isAjax()) {
            $change = input('change');
            $show = Db::name('category')->field('show')->where('id', $change)->find();
            $show = $show['show'];
            if ($show == 1) {
                Db::name('category')->where('id', $change)->update(['show' => 0]);
                echo 1;
            } else {
                Db::name('category')->where('id', $change)->update(['show' => 1]);
                echo 2;
            }
        } else {
            $this->error('非法操作');
        }
    }
    public function changeopen()
    {
        if (request()->isAjax()) {
            $change = input('change');
            $open = Db::name('category')->field('open')->where('id', $change)->find();
            $open = $open['open'];
            if ($open == 1) {
                Db::name('category')->where('id', $change)->update(['open' => 0]);
                echo 1;
            } else {
                Db::name('category')->where('id', $change)->update(['open' => 1]);
                echo 2;
            }
        } else {
            $this->error('非法操作');
        }
    }
}