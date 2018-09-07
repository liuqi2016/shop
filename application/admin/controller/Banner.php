<?php
namespace app\admin\controller;
use think\Controller;
use think\Db;
use app\admin\model\Banner as BannerModel;
class Banner extends Common
{
	public function index()
    {
        $BannerMd = new BannerModel();
        $BannerList = $BannerMd->order('id desc')->paginate(15);
        $this->assign('BannerList',$BannerList);
        return view();
    }

	public function add()
    {
        $BannerMd = new BannerModel();
		if(request()->isPost()){
			$data = input('post.');
			if (!$data['endTime']) {
				$data['endTime']=strtotime(date("Y-m-d",time()).'+365 day');//结束时间
			} else {
				$data['endTime']=strtotime($data['endTime']);//结束时间
			}
			if($BannerMd->add($data)){
				return json(array('code'=>200,'msg'=>'添加成功'));
			}else{
				return json(array('code'=>0,'msg'=>'添加失败'));
			}
		}
		$Banner = $BannerMd->select();
        $this->assign('Banner',$Banner);
		return view();
    }

	public function edit()
    {
        $BannerMd = new BannerModel();
        if(request()->isPost()){
            $data = input('post.');
			if (!$data['endTime']) {
				$data['endTime']=strtotime(date("Y-m-d",time()).'+365 day');//结束时间
			} else {
				$data['endTime']=strtotime($data['endTime']);//结束时间
			}            
            if($BannerMd->edit($data)){
                return json(array('code'=>200,'msg'=>'修改成功'));
            }else{
                return json(array('code'=>0,'msg'=>'修改失败'));
            }
        }
		$Banner = $BannerMd->find(input('id'));
        $this->assign('Banner',$Banner);
        return view();
    }

	public function dels()
	{
		$BannerMd = new BannerModel();
		if($BannerMd->destroy(input('post.id'))){
			return json(array('code'=>200,'msg'=>'删除成功'));
		}else{
			return json(array('code'=>0,'msg'=>'删除失败'));
		}
	}

	public function delss()
	{
		$BannerMd = new BannerModel();
		$params = input('post.');
		$ids = implode(',', $params['ids']);
		$result = $BannerMd->batches('delete',$ids);
		if($result){
			return json(array('code'=>200,'msg'=>'批量删除成功'));
		}else{
			return json(array('code'=>0,'msg'=>'批量删除失败'));
		}
	}

	public function changeshow()
	{
        if(request()->isAjax()){
			$change = input('change');
			$show = Db::name('banner')->field('show')->where('id',$change)->find();
			$show = $show['show'];
			if($show == 1){
				Db::name('banner')->where('id',$change)->update(['show'=>0]);
				echo 1;
			}else{
				Db::name('banner')->where('id',$change)->update(['show'=>1]);
				echo 2;
			}
		}else{
            $this->error('非法操作');
		}
    }
}
