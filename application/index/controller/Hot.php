<?php
namespace app\index\controller;
use think\Controller;
use think\Db;
class Hot extends Controller
{
    public function _initialize()
    {
        $webtag = config('web.WEB_TAG');
        $TagList = explode(',', $webtag);
        $this->assign('TagList', $TagList);        
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
        $GoodsDb = Db::name('goods');
        $show['G.show'] = 1;
        $GoodsList = $GoodsDb->alias('G')->join('category C', 'C.id=G.cid')->join('member M', 'M.userid=G.uid')->field('G.*,C.id as cid,M.userid,M.userhead,M.username,C.name')->where($show)->where('volume','>',100)->order('G.volume desc')->paginate(40);
        $this->assign('GoodsList', $GoodsList);
        $this->assign('nav_curr', 'hot');
        return view();
    }
}