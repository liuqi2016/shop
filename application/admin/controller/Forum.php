<?php
namespace app\admin\controller;
use think\Controller;
use think\Db;
use app\admin\model\Forum as ForumModel;
class Forum extends Common
{
    protected $beforeActionList = ['delsoncat' => ['only' => 'dels']];
    public function index()
    {
        $ForumMd = new ForumModel();
        $ForumList = $ForumMd->cattree();
        $this->assign('ForumList', $ForumList);
        return view();
    }
    public function add()
    {
        $ForumMd = new ForumModel();
        if (request()->isPost()) {
            $data = input('post.');
            $data['time'] = time();
            if ($ForumMd->add($data)) {
                return json(array('code' => 200, 'msg' => '添加成功'));
            } else {
                return json(array('code' => 0, 'msg' => '添加失败'));
            }
        }
        $ForumList = $ForumMd->cattree();
        $this->assign('ForumList', $ForumList);
        return view();
    }
    public function edit()
    {
        $ForumMd = new ForumModel();
        if (request()->isPost()) {
            $data = input('post.');
            if ($ForumMd->edit($data)) {
                return json(array('code' => 200, 'msg' => '修改成功'));
            } else {
                return json(array('code' => 0, 'msg' => '修改失败'));
            }
        }
        $Forum = $ForumMd->find(input('id'));
        $ForumList = $ForumMd->cattree();
        $this->assign(array('ForumList' => $ForumList, 'Forum' => $Forum));
        return view();
    }
    public function dels()
    {
        $dels = Db::name('forum')->delete(input('id'));
        if ($dels) {
            return json(array('code' => 200, 'msg' => '删除成功'));
        } else {
            return json(array('code' => 0, 'msg' => '删除失败'));
        }
    }
    public function delsoncat()
    {
        $catid = input('id');
        $ForumMd = new ForumModel();
        $sonids = $ForumMd->getchilrenid($catid);
        if ($sonids) {
            Db::name('forum')->delete($sonids);
        }
    }
    public function changeshow()
    {
        if (request()->isAjax()) {
            $change = input('change');
            $show = Db::name('forum')->field('show')->where('id', $change)->find();
            $show = $show['show'];
            if ($show == 1) {
                Db::name('forum')->where('id', $change)->update(['show' => 0]);
                echo 1;
            } else {
                Db::name('forum')->where('id', $change)->update(['show' => 1]);
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
            $open = Db::name('forum')->field('open')->where('id', $change)->find();
            $open = $open['open'];
            if ($open == 1) {
                Db::name('forum')->where('id', $change)->update(['open' => 0]);
                echo 1;
            } else {
                Db::name('forum')->where('id', $change)->update(['open' => 1]);
                echo 2;
            }
        } else {
            $this->error('非法操作');
        }
    }
}