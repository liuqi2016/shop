<?php
namespace app\admin\controller;
use think\Controller;
use think\Db;
use app\admin\model\Member as MemberModel;
class Member extends Common
{
    
	
	public function index()
    {
		$MemberMd = new MemberModel();
		$ks = input('ks');
		$MemberList = $MemberMd ->order('userid desc')->where('username','like','%'.$ks.'%')->paginate(15,false,$config = ['query'=>array('ks'=>$ks)]);
        $this->assign('MemberList',$MemberList);
		return view();
    }

	public function add()
    {
        $MemberMd = new MemberModel();
		if(request()->isPost()){
			$data = input('post.');
			$data['regtime']=time();
			$data['password']=substr(md5(input('password')),8,16);
			$data['grades']=0;
			$data['userhead'] = '/public/img/user-default.png';
			$data['userip']=$_SERVER['REMOTE_ADDR'];
			if($MemberMd ->add($data)){
				return json(array('code'=>200,'msg'=>'添加成功'));
			}else{
				return json(array('code'=>0,'msg'=>'添加失败'));
			}
		}
		$Member = $MemberMd ->select();
        $this->assign('Member',$Member);
		return view();
    }

	public function edit()
    {
        $MemberMd = new MemberModel();
        if(request()->isPost()){
            $data=input('post.');
            if($MemberMd ->edit($data)){
                return json(array('code'=>200,'msg'=>'修改成功'));
            }else{
                return json(array('code'=>0,'msg'=>'修改失败'));
            }
        }
		$Member = $MemberMd ->find(input('id'));
        $this->assign('Member',$Member);
        return view();
    }

	public function edits()
    {
        $MemberMd = new MemberModel();
        if(request()->isPost()){
            $data=input('post.');
			$data['password']=substr(md5(input('password')),8,16);
            if($MemberMd ->edit($data)){
                return json(array('code'=>200,'msg'=>'修改成功'));
            }else{
                return json(array('code'=>0,'msg'=>'修改失败'));
            }
        }
		$Member = $MemberMd ->find(input('id'));
        $this->assign('Member',$Member);
        return view();
    }

	public function dels()
	{
		$MemberMd = new MemberModel();
		if($MemberMd ->destroy(input('post.id'))){
			return json(array('code'=>200,'msg'=>'删除成功'));
		}else{
			return json(array('code'=>0,'msg'=>'删除失败'));
		}
	}

	public function delss()
	{
		$MemberMd = new MemberModel();
		$params = input('post.');
		$ids = implode(',', $params['ids']);
		$result = $MemberMd ->batches('delete',$ids);
		if($result){
			return json(array('code'=>200,'msg'=>'批量删除成功'));
		}else{
			return json(array('code'=>0,'msg'=>'批量删除失败'));
		}
	}

	public function changestatus()
	{
        if(request()->isAjax()){
			$change = input('change');
			$status = Db::name('member')->field('status')->where('userid',$change)->find();
			$status = $status['status'];
			if($status==1){
				Db::name('member')->where('userid',$change)->update(['status'=>0]);
				echo 1;
			}else{
				Db::name('member')->where('userid',$change)->update(['status'=>1]);
				echo 2;
			}
		}else{
            $this->error('非法操作');
		}
    }

}
