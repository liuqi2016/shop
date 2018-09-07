<?php
namespace app\admin\controller;
use think\Controller;
use think\Db;
use app\admin\model\Nav as NavModel;
class Nav extends Common
{
    protected $beforeActionList = ['delsoncat' => ['only' => 'dels']];
    public function index()
    {
        $NavMd = new NavModel();
        $NavList = $NavMd->navtree();
        $this->assign('NavList', $NavList);
        return view();
    }
    public function add()
    {
        $NavMd = new NavModel();
        if (request()->isPost()) {
            $data = input('post.');
            $data['time'] = time();
            if ($NavMd->add($data)) {
                return json(array('code' => 200, 'msg' => '添加成功'));
            } else {
                return json(array('code' => 0, 'msg' => '添加失败'));
            }
        }
        $NavList = $NavMd->navtree();
        $this->assign('NavList', $NavList);
        return view();
    }
    public function edit()
    {
        $NavMd = new NavModel();
        if (request()->isPost()) {
            $data = input('post.');
            if ($NavMd->edit($data)) {
                return json(array('code' => 200, 'msg' => '修改成功'));
            } else {
                return json(array('code' => 0, 'msg' => '修改失败'));
            }
        }
        $Nav = $NavMd->find(input('id'));
        $NavList = $NavMd->navtree();
        $this->assign(array('NavList' => $NavList, 'Nav' => $Nav));
        return view();
    }
    public function dels()
    {
        $dels = Db::name('nav')->delete(input('id')); 
        if ($dels) {
            return json(array('code' => 200, 'msg' => '删除成功'));
        } else {
            return json(array('code' => 0, 'msg' => '删除失败'));
        }
    }
    public function delsoncat()
    {
        $catid = input('id');
        $NavMd = new NavModel();
        $sonids = $NavMd->getchilrenid($catid);
        if ($sonids) {
            Db::name('nav')->delete($sonids);
        }
    }
    public function changeshow()
    {
        if (request()->isAjax()) {
            $change = input('change');
            $show = Db::name('nav')->field('show')->where('id', $change)->find();
            $show = $show['show'];
            if ($show == 1) {
                Db::name('nav')->where('id', $change)->update(['show' => 0]);
                echo 1;
            } else {
                Db::name('nav')->where('id', $change)->update(['show' => 1]);
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
            $open = Db::name('nav')->field('open')->where('id', $change)->find();
            $open = $open['open'];
            if ($open == 1) {
                Db::name('nav')->where('id', $change)->update(['open' => 0]);
                echo 1;
            } else {
                Db::name('nav')->where('id', $change)->update(['open' => 1]);
                echo 2;
            }
        } else {
            $this->error('非法操作');
        }
    }
}