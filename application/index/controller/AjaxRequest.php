<?php
namespace app\index\controller;
use think\Controller;
use think\Db;
class AjaxRequest extends Controller
{
	public function suggest()
	{
		$data = input('ks', '');
        $source = post_web_page('http://suggest.taobao.com/sug?code=utf-8&q='.$data);
		$result_data = json_decode($source, true);
		$result_data = $result_data['result'];
		$tags = '';
		$tags .= '<ul class="dropdown-menu">';
		if ($result_data) {
			foreach ($result_data as $k) {
				$ks = str_replace($data, "<b style=\"color:#ff464e;\">{$data}</b>", $k[0]);
				$tags .= '<li><a data-ks="' . $k[0] . '">' . $ks . '</a></li>';
			}
		}
		$tags .= '</ul>';
		if ($tags) {
			return json(array('code' => 1, 'msg' => '', 'data' => $tags));
		}
	}
	public function taoToken()
	{
		$num_iid = input('id', '');
		$GoodsDb = Db::name('goods');
		$Goods = $GoodsDb->alias('G')->join('category C', 'C.id=G.cid')->join('member M', 'M.userid=G.uid')->field('G.*,C.id as cid,C.name,M.userid,M.grades,M.point,M.userhead,M.username')->where(['numIid'=>$num_iid])->find();
		$tao_token = $Goods['taoToken'];
		$url = urldecode($Goods['clickUrl']);
		$pic = $Goods['pic'];
		$title = $Goods['title'];
		if (!$tao_token) {
			if ($url) {
		        vendor('taobao.TopClient');
		        vendor('taobao.ResultSet');
		        vendor('taobao.RequestCheckUtil');
		        vendor('taobao.TopLogger');
		        vendor('taobao.domain.GenPwdIsvParamDto');
		        vendor('taobao.request.TbkTpwdCreateRequest');
	        	$c = new \TopClient();
				$c->appkey = config('web.WEB_TBKEY');
				$c->secretKey = config('web.WEB_TBSECREC');
				$req = new \TbkTpwdCreateRequest;
				/*$req->setUserId("123");*/
				$req->setText($title);
				$req->setUrl($url);
				$req->setLogo($pic);
				$req->setExt("{}");
				$resp = $c->execute($req);
				$tao_token = object_to_array($resp)['data']['model'];
				$GoodsDb->where(['numIid'=>$num_iid])->update(['taoToken'=>$tao_token]);
			} else {
				$tao_token = '没有淘口令';
			}
		}	
		return json($tao_token);
	}




	public function taobaoToken()
	{
		$num_iid = input('id', '');
		$url = urldecode(input('clickUrl', ''));
		$title = urldecode(input('title', ''));		
		$pic = urldecode(input('pic', ''));
		if ($url) {
	        vendor('taobao.TopClient');
	        vendor('taobao.ResultSet');
	        vendor('taobao.RequestCheckUtil');
	        vendor('taobao.TopLogger');
	        vendor('taobao.domain.GenPwdIsvParamDto');
	        vendor('taobao.request.TbkTpwdCreateRequest');
        	$c = new \TopClient();
			$c->appkey = config('web.WEB_TBKEY');
			$c->secretKey = config('web.WEB_TBSECREC');
			$req = new \TbkTpwdCreateRequest;
			/*$req->setUserId("123");*/
			$req->setText($title);
			$req->setUrl($url);
			$req->setLogo($pic);
			$req->setExt("{}");
			$resp = $c->execute($req);
			$tao_token = object_to_array($resp)['data']['model'];
		} else {
			$tao_token = '没有淘口令';
		}
		return json($tao_token);
	}

	public function goodsDesc()
	{
		$num_iid = input('goodsId', '');
		$goods_desc = '';
		$GoodsDb = Db::name('goods');
		$count = $GoodsDb->{'where'}(array('numIid' => $num_iid))->{'value'}('content');
		if ($count) {
			$goods_desc = $count;
		} else {
            $source = get_web_page('http://hws.m.taobao.com/cache/mtop.wdetail.getItemDescx/4.1/?data=%7B%22item_num_id%22%3A%22'.$num_iid.'%22%7D');
            $result_data = json_decode($source, true);
			$result_data = is_array($result_data) ? $result_data : '';
			$item = '';
			$num = $result_data['data']['images'];
			for ($i = 0; $i < count($num); $i++) {
				$item .= '<img  class="lazy" src="'.$num[$i].'" style="width: 100%;">';
			}
			$goods_desc = $item;
		}
		if ($goods_desc) {
			$content['status'] = 200;
			$content['content'] = $goods_desc;
		}
		return json($content);
	}

	public function indexList()
	{
		$data = input();
		$GoodsDb = Db::name('goods');
		$show['G.show'] = isset($data['G.show'])?$data['G.show']:1;
		$count = $GoodsDb->alias('G')->join('category C', 'C.id=G.cid')->join('member M', 'M.userid=G.uid')->field('G.*,C.id as cid,M.userid,M.userhead,M.username,C.name')->where($show)->count();
		$glist = [];
		if($count){
			$GoodsList = $GoodsDb->alias('G')->join('category C', 'C.id=G.cid')->join('member M', 'M.userid=G.uid')->field('G.*,C.id as cid,M.userid,M.userhead,M.username,C.name')->where($show)->order('G.id desc')->paginate(40);
			foreach($GoodsList as $key=>$val){
				$tmp = $val;
				$tmp['url'] = url('dtl/'.$tmp['id']);
				$tmp['jumpurl'] = url('jump/index').'?id='.$tmp['id'];
				$tmp['newicon'] = newicon($tmp['startTime']);
				$tmp['icon'] =!($tmp['userType'])?'t-icon':'m-icon';
				$tmp['couponAmount'] =!($tmp['couponAmount'])?'':'<div class="quan"><a href="'.$tmp['jumpurl'].'" rel="nofollow"><span class="quan-info">优惠券<br><em>'.$val['couponAmount'].'元</em></span></a></div>';
				$glist[] = $tmp;
			}
			$pages = ceil($count/40);
		}else{
			$pages = 0;
		}
		return json(['data'=>$glist,'pages'=>$pages]);
	}

	public function catList()
	{
		$data = input();
        $id = $data['id'];
        $sort = $data['sort'];
        $order = '';
        switch ($sort) {
            case 'new':
                $order = 'G.startTime desc';
                break;

            case 'price':
                $order = 'G.couponPrice desc';
                break;

            case 'hot':
                $order = 'G.volume desc';
                break;

            case 'quan':
                $order = 'G.couponAmount desc';
                break;

            case 'rate':
                $order = 'G.couponRate asc';
        }
        $CategoryDb = Db::name('category'); 
        $Category = $CategoryDb->where("id = {$id}")->find();
        $glist = [];
        if ($Category) {
            $GoodsDb = Db::name('goods');
            $show['G.show'] = 1;          
			$count = $GoodsDb->alias('G')->join('category C', 'C.id=G.cid')->join('member M', 'M.userid=G.uid')->field('G.*,C.id as cid,M.userid,M.userhead,M.username,C.name')->where("G.cid={$id}")->where($show)->count();
            $GoodsList = $GoodsDb->alias('G')->join('category C', 'C.id=G.cid')->join('member M', 'M.userid=G.uid')->field('G.*,C.id as cid,M.userid,M.userhead,M.username,C.name')->where("G.cid={$id}")->where($show)->order($order)->paginate(40);            
			foreach($GoodsList as $key=>$val){
				$tmp = $val;
				$tmp['url'] = url('dtl/'.$tmp['id']);
				$tmp['jumpurl'] = url('jump/index').'?id='.$tmp['id'];				
				$tmp['newicon'] = newicon($tmp['startTime']);
				$tmp['icon'] =!($tmp['userType'])?'t-icon':'m-icon';
				$tmp['couponAmount'] =!($tmp['couponAmount'])?'':'<div class="quan"><a href="'.$tmp['jumpurl'].'" rel="nofollow"><span class="quan-info">优惠券<br><em>'.$val['couponAmount'].'元</em></span></a></div>';
				$glist[] = $tmp;
			}
			$pages = ceil($count/40);
        } else {
            $pages = 0;
        }
        return json(['data'=>$glist,'pages'=>$pages]);    
	}	

	public function searchList()
    {
        $ks = input('ks');
        $glist = [];
        if (empty($ks)) {
            $pages = 0;
        } else {
			$GoodsDb = Db::name('goods');
			$show['G.show'] = 1;
			$count = $GoodsDb->alias('G')->join('category C', 'C.id=G.cid')->join('member M', 'M.userid=G.uid')->field('G.*,C.id as cid,M.userid,M.userhead,M.username,C.name')->order('G.id desc')->where($show)->where('title','like','%'.$ks.'%')->count();	
			if ($count) {
				$GoodsList = $GoodsDb->alias('G')->join('category C', 'C.id=G.cid')->join('member M', 'M.userid=G.uid')->field('G.*,C.id as cid,M.userid,M.userhead,M.username,C.name')->order('G.id desc')->where($show)->where('title','like','%'.$ks.'%')->paginate(16,false,$config = ['query'=>array('ks'=>$ks)]);
	            foreach($GoodsList as $key=>$val){
					$tmp = $val;
					$tmp['url'] = url('dtl/'.$tmp['id']);
					$tmp['jumpurl'] = url('jump/index').'?id='.$tmp['id'];					
					$tmp['newicon'] = newicon($tmp['startTime']);
					$tmp['icon'] =!($tmp['userType'])?'t-icon':'m-icon';
					$tmp['couponAmount'] =!($tmp['couponAmount'])?'':'<div class="quan"><a href="'.$tmp['jumpurl'].'" rel="nofollow"><span class="quan-info">优惠券<br><em>'.$val['couponAmount'].'元</em></span></a></div>';					
					$glist[] = $tmp;
				}
				$pages = ceil($count/40);
			} else {
	            $pages = 0;
	        }
		}
		return json(['data'=>$glist,'pages'=>$pages]);    
    }

    public function historyList()
    {
        $history = cookie('person_dtl_history');
        if($history){
            $order = 'field(G.id,'.implode(',',array_reverse($history)).')';
            $GoodsDb = Db::name('goods');
            $show['G.show'] = 1;
            $count = $GoodsDb->alias('G')->join('category C', 'C.id=G.cid')->join('member M', 'M.userid=G.uid')->field('G.*,C.id as cid,M.userid,M.userhead,M.username,C.name')->where('G.id','in',$history)->where($show)->order($order)->count();	
			if ($count) {
            	$GoodsList = $GoodsDb->alias('G')->join('category C', 'C.id=G.cid')->join('member M', 'M.userid=G.uid')->field('G.*,C.id as cid,M.userid,M.userhead,M.username,C.name')->where('G.id','in',$history)->where($show)->order($order)->paginate(40);
	            foreach($GoodsList as $key=>$val){
					$tmp = $val;
					$tmp['url'] = url('dtl/'.$tmp['id']);
					$tmp['jumpurl'] = url('jump/index').'?id='.$tmp['id'];					
					$tmp['newicon'] = newicon($tmp['startTime']);
					$tmp['icon'] =!($tmp['userType'])?'t-icon':'m-icon';
					$tmp['couponAmount'] =!($tmp['couponAmount'])?'':'<div class="quan"><a href="'.$tmp['jumpurl'].'" rel="nofollow"><span class="quan-info">优惠券<br><em>'.$val['couponAmount'].'元</em></span></a></div>';					
					$glist[] = $tmp;
				}
				$pages = ceil($count/40);
			} else {
	            $pages = 0;
	        }
        }else{
            $pages = 0;
        }
        return json(['data'=>$glist,'pages'=>$pages]);    
    }  

	public function jiuList()
	{
		$GoodsDb = Db::name('goods');
		$show['G.show'] = 1;
		$count = $GoodsDb->alias('G')->join('category C', 'C.id=G.cid')->join('member M', 'M.userid=G.uid')->field('G.*,C.id as cid,M.userid,M.userhead,M.username,C.name')->where($show)->where('couponPrice','<',10)->count();
		$glist = [];
		if($count){
			$GoodsList = $GoodsDb->alias('G')->join('category C', 'C.id=G.cid')->join('member M', 'M.userid=G.uid')->field('G.*,C.id as cid,M.userid,M.userhead,M.username,C.name')->where($show)->where('couponPrice','<',10)->order('G.id desc')->paginate(40);
			foreach($GoodsList as $key=>$val){
				$tmp = $val;
				$tmp['url'] = url('dtl/'.$tmp['id']);
				$tmp['jumpurl'] = url('jump/index').'?id='.$tmp['id'];				
				$tmp['newicon'] = newicon($tmp['startTime']);
				$tmp['icon'] =!($tmp['userType'])?'t-icon':'m-icon';
				$tmp['couponAmount'] =!($tmp['couponAmount'])?'':'<div class="quan"><a href="'.$tmp['jumpurl'].'" rel="nofollow"><span class="quan-info">优惠券<br><em>'.$val['couponAmount'].'元</em></span></a></div>';				
				$glist[] = $tmp;
			}
			$pages = ceil($count/40);
		}else{
			$pages = 0;
		}
		return json(['data'=>$glist,'pages'=>$pages]);
	}

	public function shijiuList()
	{
		$GoodsDb = Db::name('goods');
		$show['G.show'] = 1;
		$count = $GoodsDb->alias('G')->join('category C', 'C.id=G.cid')->join('member M', 'M.userid=G.uid')->field('G.*,C.id as cid,M.userid,M.userhead,M.username,C.name')->where($show)->where('couponPrice','<',10)->count();
		$glist = [];
		if($count){
			$GoodsList = $GoodsDb->alias('G')->join('category C', 'C.id=G.cid')->join('member M', 'M.userid=G.uid')->field('G.*,C.id as cid,M.userid,M.userhead,M.username,C.name')->where($show)->where('couponPrice','<',10)->order('G.id desc')->paginate(40);
			foreach($GoodsList as $key=>$val){
				$tmp = $val;
				$tmp['url'] = url('dtl/'.$tmp['id']);
				$tmp['jumpurl'] = url('jump/index').'?id='.$tmp['id'];				
				$tmp['newicon'] = newicon($tmp['startTime']);
				$tmp['icon'] =!($tmp['userType'])?'t-icon':'m-icon';
				$tmp['couponAmount'] =!($tmp['couponAmount'])?'':'<div class="quan"><a href="'.$tmp['jumpurl'].'" rel="nofollow"><span class="quan-info">优惠券<br><em>'.$val['couponAmount'].'元</em></span></a></div>';				
				$glist[] = $tmp;
			}
			$pages = ceil($count/40);
		}else{
			$pages = 0;
		}
		return json(['data'=>$glist,'pages'=>$pages]);
	}

	public function hotList()
	{
		$GoodsDb = Db::name('goods');
		$show['G.show'] = 1;
		$count = $GoodsDb->alias('G')->join('category C', 'C.id=G.cid')->join('member M', 'M.userid=G.uid')->field('G.*,C.id as cid,M.userid,M.userhead,M.username,C.name')->where($show)->where('volume','>',100)->count();
		$glist = [];
		if($count){
			$GoodsList = $GoodsDb->alias('G')->join('category C', 'C.id=G.cid')->join('member M', 'M.userid=G.uid')->field('G.*,C.id as cid,M.userid,M.userhead,M.username,C.name')->where($show)->where('volume','>',100)->order('G.volume desc')->paginate(40);
			foreach($GoodsList as $key=>$val){
				$tmp = $val;
				$tmp['url'] = url('dtl/'.$tmp['id']);
				$tmp['jumpurl'] = url('jump/index').'?id='.$tmp['id'];				
				$tmp['newicon'] = newicon($tmp['startTime']);
				$tmp['icon'] =!($tmp['userType'])?'t-icon':'m-icon';
				$tmp['couponAmount'] =!($tmp['couponAmount'])?'':'<div class="quan"><a href="'.$tmp['jumpurl'].'" rel="nofollow"><span class="quan-info">优惠券<br><em>'.$val['couponAmount'].'元</em></span></a></div>';				
				$glist[] = $tmp;
			}
			$pages = ceil($count/40);
		}else{
			$pages = 0;
		}
		return json(['data'=>$glist,'pages'=>$pages]);
	}	 	      	
}