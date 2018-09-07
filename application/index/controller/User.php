<?php
namespace app\index\controller;
use think\Controller;
use think\Db;
use app\index\model\Member as MemberModel;
class User extends Controller
{
    public function _initialize()
    {
        $show['show'] = 1;
        $LinksList = Db::name('links')->where($show)->order('id desc')->select();
        $this->assign('LinksList', $LinksList);
    }

    public function index()
    {
        if (!session('userid') || !session('username')) {
            $this->error('亲！请登录',url('login/index'));
        } else {
            $ThreadDb = Db::name('thread');
            $uid = session('userid');
            $ThreadList = $ThreadDb->where("uid = {$uid}")->order('id desc')->paginate(10);
            $this->assign('ThreadList', $ThreadList);
            return view();
        }
    }
	public function comment()
    {
        if (!session('userid') || !session('username')) {
            $this->error('亲！请登录',url('login/index'));
        } else {
            $CommentDb = Db::name('comment');
            $uid = session('userid');
            $CommentList = $CommentDb->alias('C')->join('thread T', 'T.id=C.tid')->field('C.*,T.title')->where("C.uid = {$uid}")->order('C.id desc')->paginate(5);
            $this->assign('CommentList', $CommentList);
            return view();
        }
    }
    public function home()
    {
        $id = input('id');
        if (empty($id)) {
            return $this->error('亲！你迷路了');
        } else {
            $MemberMd = new MemberModel();
            $member = $MemberMd->where("userid = {$id}")->find($id);
            if ($member) {
                $this->assign('member', $member);
				$ThreadDb = Db::name('thread');
                $ThreadList = $ThreadDb->where("uid = {$id}")->order('id desc')->limit(10)->select();
                $this->assign('ThreadList', $ThreadList);
				$CommentDb = Db::name('comment');
                $CommentList = $CommentDb->alias('C')->join('thread T', 'T.id=C.tid')->field('C.*,T.title')->where("C.uid = {$id}")->order('C.id desc')->limit(5)->select();
                $this->assign('CommentList', $CommentList);
                return view();
            } else {
                return $this->error('亲！你迷路了');
            }
        }
    }
    public function set()
    {
        if (!session('userid') || !session('username')) {
            $this->error('亲！请登录',url('login/index'));
        } else {
            $MemberMd = new MemberModel();
            $uid = session('userid');
            if (request()->isPost()) {
                $data = input('post.');
                if ($MemberMd->edit($data)) {
                    return json(array('code' => 200, 'msg' => '修改成功'));
                } else {
                    return json(array('code' => 0, 'msg' => '修改失败'));
                }
            }
            $member = $MemberMd->find($uid);
            $this->assign('member', $member);
            return view();
        }
    }
    public function setedit()
    {
        if (!session('userid') || !session('username')) {
            $this->error('亲！请登录',url('login/index'));
        } else {
            $MemberMd = new MemberModel();
            $uid = session('userid');
            if (request()->isPost()) {
                $data = input('post.');
                $password = input('password');
                $passwords = input('passwords');
                if ($password != $passwords) {
                    return json(array('code' => 0, 'msg' => '密码错误'));
                }
                $data['password'] = substr(md5($password), 8, 16);
                if ($MemberMd->edit($data)) {
                    return json(array('code' => 200, 'msg' => '修改成功'));
                } else {
                    return json(array('code' => 0, 'msg' => '修改失败'));
                }
            }
            $member = $MemberMd->find($uid);
            $this->assign('member', $member);
            return view();
        }
    }
	public function headedit()
    {
        if (!session('userid') || !session('username')) {
            $this->error('亲！请登录',url('login/index'));
        } else {
            $MemberMd = new MemberModel();
            $uid = session('userid');
            if (request()->isPost()) {
                $data = input('post.');
                if ($MemberMd->edit($data)) {
                    return json(array('code' => 200, 'msg' => '修改成功'));
                } else {
                    return json(array('code' => 0, 'msg' => '修改失败'));
                }
            }
            $member = $MemberMd->find($uid);
            $this->assign('member', $member);
            return view();
        }
    }
}