<?php
namespace app\admin\controller;
use think\Controller;
use think\Db;
use app\admin\model\Links as LinksModel;
class Links extends Common
{
    
	
	public function index()
    {
        $linksMd = new LinksModel();
        $LinksList = $linksMd->order('id desc')->paginate(15);
        $this->assign('LinksList',$LinksList);
        return view();
    }

	public function add()
    {
        $linksMd = new LinksModel();
		if(request()->isPost()){
			$data = input('post.');
			$data['time']=time();
			if($linksMd->add($data)){
				return json(array('code'=>200,'msg'=>'添加成功'));
			}else{
				return json(array('code'=>0,'msg'=>'添加失败'));
			}
		}
		$Links = $linksMd->select();
        $this->assign('Links ',$Links);
		return view();
    }

	public function edit()
    {
        $linksMd = new LinksModel();
        if(request()->isPost()){
            $data = input('post.');
            if($linksMd->edit($data)){
                return json(array('code'=>200,'msg'=>'修改成功'));
            }else{
                return json(array('code'=>0,'msg'=>'修改失败'));
            }
        }
		$Links = $linksMd->find(input('id'));
        $this->assign('Links',$Links);
        return view();
    }

	public function dels()
	{
		$linksMd = new LinksModel();
		if($linksMd->destroy(input('post.id'))){
			return json(array('code'=>200,'msg'=>'删除成功'));
		}else{
			return json(array('code'=>0,'msg'=>'删除失败'));
		}
	}

	public function delss()
	{
		$linksMd = new LinksModel();
		$params = input('post.');
		$ids = implode(',', $params['ids']);
		$result = $linksMd->batches('delete',$ids);
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
			$show = Db::name('links')->field('show')->where('id',$change)->find();
			$show = $show['show'];
			if($show == 1){
				Db::name('links')->where('id',$change)->update(['show'=>0]);
				echo 1;
			}else{
				Db::name('links')->where('id',$change)->update(['show'=>1]);
				echo 2;
			}
		}else{
            $this->error('非法操作');
		}
    }

}
