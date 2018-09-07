<?php
namespace app\admin\controller;
use think\Controller;
use think\Db;
use app\admin\model\Thread as ThreadModel;
class Thread extends Common
{
    public function index()
    {
        $ThreadMd = new ThreadModel();
		$ks=input('ks');
        $ThreadList = $ThreadMd->alias('T')->join('forum F', 'F.id=T.fid')->field('T.*,F.id as cid,F.name')->order('T.id desc')->where('title','like','%'.$ks.'%')->paginate(15,false,$config = ['query'=>array('ks'=>$ks)]);
        $this->assign('ThreadList', $ThreadList);
        return view();
    }
    public function edit()
    {
        $ThreadMd = new ThreadModel();
        if (request()->isPost()) {
            $data = input('post.');
            if ($ThreadMd->edit($data)) {
                return json(array('code' => 200, 'msg' => '修改成功'));
            } else {
                return json(array('code' => 0, 'msg' => '修改失败'));
            }
        }
        $ForumDb = Db::name('forum');
        $Thread = $ThreadMd->find(input('id'));
        $ForumList = $ForumDb->select();
        $this->assign(array('ForumList' => $ForumList, 'Thread' => $Thread));
        return view();
    }
    public function doUploadPic()
    {
        $file = request()->file('FileName');
        $info = $file->move(ROOT_PATH . DS . 'upload');
		if($info){
			$path = WEB_URL . DS . 'upload' . DS .$info->getSaveName();
			echo str_replace("\\","/",$path);
        }
    }
    public function dels()
    {
        $ThreadMd = new ThreadModel();
        if ($ThreadMd->destroy(input('post.id'))) {
            return json(array('code' => 200, 'msg' => '删除成功'));
        } else {
            return json(array('code' => 0, 'msg' => '删除失败'));
        }
    }
    public function delss()
    {
        $ThreadMd = new ThreadModel();
        $params = input('post.');
        $ids = implode(',', $params['ids']);
        $result = $ThreadMd->batches('delete', $ids);
        if ($result) {
            return json(array('code' => 200, 'msg' => '批量删除成功'));
        } else {
            return json(array('code' => 0, 'msg' => '批量删除失败'));
        }
    }
    public function changechoice()
    {
        if (request()->isAjax()) {
            $change = input('change');
            $choice = Db::name('thread')->field('choice')->where('id', $change)->find();
            $choice = $choice['choice'];
            if ($choice == 1) {
                Db::name('thread')->where('id', $change)->update(['choice' => 0]);
                echo 1;
            } else {
                Db::name('thread')->where('id', $change)->update(['choice' => 1]);
                echo 2;
            }
        } else {
            $this->error('非法操作');
        }
    }
    public function changesettop()
    {
        if (request()->isAjax()) {
            $change = input('change');
            $settop = Db::name('thread')->field('settop')->where('id', $change)->find();
            $settop = $settop['settop'];
            if ($settop == 1) {
                Db::name('thread')->where('id', $change)->update(['settop' => 0]);
                echo 1;
            } else {
                Db::name('thread')->where('id', $change)->update(['settop' => 1]);
                echo 2;
            }
        } else {
            $this->error('非法操作');
        }
    }
    public function changeshow()
    {
        if (request()->isAjax()) {
            $change = input('change');
            $show = Db::name('thread')->field('show')->where('id', $change)->find();
            $show = $show['show'];
            if ($show == 1) {
                Db::name('thread')->where('id', $change)->update(['show' => 0]);
                echo 1;
            } else {
                Db::name('thread')->where('id', $change)->update(['show' => 1]);
                echo 2;
            }
        } else {
            $this->error('非法操作');
        }
    }
}