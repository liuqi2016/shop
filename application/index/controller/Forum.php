<?php
namespace app\index\controller;
use think\Controller;
use think\Db;
class Forum extends Controller
{
    public function _initialize()
    {
        $show['show'] = 1;
        $type['type'] = 1;
        $choice['choice'] = 1;
        $NewMemberList = Db::name('member')->order('userid desc')->limit(12)->select();
        $this->assign('NewMemberList', $NewMemberList);
        $LinksList = Db::name('links')->where($show)->order('id desc')->select();
        $this->assign('LinksList', $LinksList);
        $BannerList = Db::name('banner')->where($show)->where('endTime','>=',time())->order('id asc')->select();
        $this->assign('BannerList', $BannerList); 
        $NavList = Db::name('nav')->where($show)->order('sort asc')->select();
        $this->assign('NavList', $NavList); 
        $ChoiceThreadList = Db::name('thread')->where($show)->where($choice)->order('id desc')->limit(9)->select();
        $this->assign('ChoiceThreadList', $ChoiceThreadList);
        $HotThreadList = Db::name('thread')->where($show)->order('view desc')->limit(9)->select();
        $this->assign('HotThreadList', $HotThreadList);
        $CategoryList = Db::name('category')->where($show)->order('sort desc')->limit(20)->select();
        $this->assign('CategoryList', $CategoryList);
        $ForumList = Db::name('forum')->where($show)->order('sort desc')->limit(12)->select();
        $this->assign('ForumList', $ForumList);
    }

    public function index()
    {
        $ThreadDb = Db::name('thread');
        $show['T.show'] = 1;
        $settop['settop'] = 1;
        $SettopThreadList = $ThreadDb->alias('T')->join('forum F', 'F.id=T.fid')->join('member M', 'M.userid=T.uid')->field('T.*,F.id as cid,M.userid,M.userhead,M.username,F.name')->where($show)->where($settop)->order('T.id desc')->limit(5)->select();
        $this->assign('SettopThreadList', $SettopThreadList);
        $ThreadList = $ThreadDb->alias('T')->join('forum F', 'F.id=T.fid')->join('member M', 'M.userid=T.uid')->field('T.*,F.id as cid,M.userid,M.userhead,M.username,F.name')->where($show)->order('T.id desc')->paginate(30);
        $this->assign('ThreadList', $ThreadList);
        return view();
    }

    public function search()
    {
        $ks=input('ks');
        if (empty($ks)) {
            return $this->error('亲！你迷路了');
        } else {
            $ThreadDb = Db::name('thread');
            $show['T.show'] = 1;
            $ThreadList = $ThreadDb->alias('T')->join('forum F', 'F.id=T.fid')->join('member M', 'M.userid=T.uid')->field('T.*,F.id as cid,M.userid,M.userhead,M.username,F.name')->order('T.id desc')->where($show)->where('title','like','%'.$ks.'%')->paginate(15,false,$config = ['query'=>array('ks'=>$ks)]);
            $this->assign('ThreadList', $ThreadList);
            return view();
        }
    }

    public function choice()
    {
        $ThreadDb = Db::name('thread');
        $show['T.show'] = 1;
        $choice['choice'] = 1;
        $ThreadList = $ThreadDb->alias('T')->join('forum F', 'F.id=T.fid')->join('member M', 'M.userid=T.uid')->field('T.*,F.id as cid,M.userid,M.userhead,M.username,F.name')->where($show)->where($choice)->order('T.id desc')->paginate(30);
        $this->assign('ThreadList', $ThreadList);
        return view();
    }

    public function frm()
    {
        $id = input('id');
        if (empty($id)) {
            return $this->error('亲！你迷路了');
        } else {
            $ForumDb = Db::name('forum');
            $Forum = $ForumDb->where("id = {$id}")->find();
            $this->assign('Forum', $Forum);
            if ($Forum) {
                $ThreadDb = Db::name('thread');
                $show['T.show'] = 1;
                $ThreadList = $ThreadDb->alias('T')->join('forum F', 'F.id=T.fid')->join('member M', 'M.userid=T.uid')->field('T.*,F.id as cid,M.userid,M.userhead,M.username,F.name')->where("T.fid={$id}")->where($show)->order('T.id desc')->paginate(30);
                $this->assign('ThreadList', $ThreadList);
                return view();
            } else {
                $this->error("亲！你迷路了！");
            }
        }
    }

    public function trd()
    {
        $id = input('id');
        if (empty($id)) {
            return $this->error('亲！你迷路了');
        } else {
            $ThreadDb = Db::name('thread');
            $query = $ThreadDb->where("id = {$id}")->find();
            if ($query) {
                $ThreadDb->where("id = {$id}")->setInc('view', 1);
                $Thread = $ThreadDb->alias('T')->join('forum F', 'F.id=T.fid')->join('member M', 'M.userid=T.uid')->field('T.*,F.id as cid,F.name,M.userid,M.grades,M.point,M.userhead,M.username')->find($id);
                $this->assign('Thread', $Thread);
                $content = $Thread['content'];
                $content = htmlspecialchars_decode($content);
                $this->assign('content', $content);
                $CommentList = Db::name('comment')->alias('C')->join('member M', 'M.userid=C.uid')->where("tid = {$id}")->order('C.id asc')->paginate(15);
                $this->assign('CommentList', $CommentList);
                return view();
            } else {
                return $this->error('亲！你迷路了');
            }
        }
    }    
}