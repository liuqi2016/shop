<?php
namespace app\admin\controller;
use think\Controller;
use app\admin\model\Comment as CommentModel;
class Comment extends Common
{
    public function index()
    {
        $CommentMd = new CommentModel();
        $CommentList = $CommentMd->alias('C')->join('thread T', 'T.id=C.tid')->join('member M', 'M.userid=C.uid')->field('C.*,T.title,M.username')->order('C.id desc')->paginate(15);
        $this->assign('CommentList', $CommentList);
        return view();
    }
    public function edit()
    {
        $CommentMd = new CommentModel();
        if (request()->isPost()) {
            $data = input('post.');
            if ($CommentMd->edit($data)) {
                return json(array('code' => 200, 'msg' => '修改成功'));
            } else {
                return json(array('code' => 0, 'msg' => '修改失败'));
            }
        }
		$Comment = $CommentMd->find(input('id'));
        $this->assign('Comment', $Comment);
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
        $CommentMd = new CommentModel();
        if ($CommentMd->destroy(input('post.id'))) {
            return json(array('code' => 200, 'msg' => '删除成功'));
        } else {
            return json(array('code' => 0, 'msg' => '删除失败'));
        }
    }
    public function delss()
    {
        $CommentMd = new CommentModel();
        $params = input('post.');
        $ids = implode(',', $params['ids']);
        $result = $CommentMd->batches('delete', $ids);
        if ($result) {
            return json(array('code' => 200, 'msg' => '批量删除成功'));
        } else {
            return json(array('code' => 0, 'msg' => '批量删除失败'));
        }
    }
}