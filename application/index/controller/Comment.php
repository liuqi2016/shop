<?php
namespace app\index\controller;
use think\Controller;
use think\Db;
use app\index\model\Comment as CommentModel;
class Comment extends Controller
{
    public function _initialize()
    {
        $show['show'] = 1;
        $LinksList = Db::name('links')->where($show)->order('id desc')->select();
        $this->assign('LinksList', $LinksList);
    }

    public function add()
    {
        if (!session('userid') || !session('username')) {
            $this->error('亲！请登录');
        } else {
            $CommentMd = new CommentModel();
            $id = input('id');
            $uid = session('userid');
            if (request()->isPost()) {
                $data = input('post.');
                $data['time'] = time();
                $data['tid'] = $id;
                $data['uid'] = session('userid');
				$MemberDb = Db::name('member');
				$MemberDb->where('userid', session('userid'))->setInc('point', config('point.EDIT_POINT'));
				$ThreadDb = Db::name('thread');
				$ThreadDb->where('id', $id)->setInc('reply', 1);
                if ($CommentMd->add($data)) {
                    return json(array('code' => 200, 'msg' => '回复成功'));
                } else {
                    return json(array('code' => 0, 'msg' => '回复失败'));
                }
            }
        }
    }
	public function edit()
    {
        if (!session('userid') || !session('username')) {
            $this->error('亲！请登录');
        } else {
            $id = input('id');
            $uid = session('userid');
            $CommentMd = new CommentModel();
            $query = $CommentMd->find($id);
            if (empty($id) || $query == null || $query['uid'] != $uid) {
                $this->error('亲！您迷路了');
            } else {
                if (request()->isPost()) {
                    $data = input('post.');
                    if ($CommentMd->edit($data)) {
                        return json(array('code' => 200, 'msg' => '修改成功'));
                    } else {
                        return json(array('code' => 0, 'msg' => '修改失败'));
                    }
                }
                $Comment = $CommentMd->alias('C')->join('thread T', 'T.id=C.tid')->field('C.*,T.title')->find($id);
		        $this->assign('Comment', $Comment);
                return view();
            }
        }
    }
    public function doUploadPic()
    {
        if (!session('userid') || !session('username')) {
            $this->error('亲！请登录');
        } else {
			$file = request()->file('FileName');
			$info = $file->move(ROOT_PATH . DS . 'upload');
			if ($info) {
				$path = WEB_URL . DS . 'upload' . DS . $info->getSaveName();
				echo str_replace("\\", "/", $path);
			}
		}
    }
    public function dels()
    {
        if (session('userid')!=1) {
            $this->error('亲！你迷路了');
        } else {
			$id = input('id');
			if (db('comment')->delete(input('id'))) {
				return json(array('code' => 200, 'msg' => '删除成功'));
			} else {
				return json(array('code' => 0, 'msg' => '删除失败'));
			}
		}
    }
}