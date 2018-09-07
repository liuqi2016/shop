<?php
namespace app\admin\controller;
use think\Controller;
use think\Db;
use app\admin\model\Goods as GoodsModel;
class Goods extends Common
{
    public function index()
    {
        $GoodsMd = new GoodsModel();
		$ks=input('ks');
        $GoodsList = $GoodsMd->alias('G')->join('category C', 'C.id=G.cid')->field('G.*,C.id as cid,C.name')->order('G.id desc')->where('title','like','%'.$ks.'%')->where('endTime','>=',time())->paginate(15,false,$config = ['query'=>array('ks'=>$ks)]);
        $this->assign('GoodsList', $GoodsList);
        $CategoryDb = Db::name('category');
        $CategoryList = $CategoryDb->select();
        $this->assign('CategoryList', $CategoryList);
        return view();
    }

    public function stale()
    {
        $GoodsMd = new GoodsModel();
        $ks=input('ks');
        $GoodsList = $GoodsMd->alias('G')->join('category C', 'C.id=G.cid')->field('G.*,C.id as cid,C.name')->order('G.id desc')->where('title','like','%'.$ks.'%')->where('endTime','<',time())->paginate(15,false,$config = ['query'=>array('ks'=>$ks)]);
        $this->assign('GoodsList', $GoodsList);
        return view();
    }

    public function collect()
    {
        /*http://hws.m.taobao.com/cache/wdetail/5.0/?id=557157320426*/
        $link = input('link');
        $source = get_web_page('http://pub.alimama.com/items/search.json?q='.$link.'&_t=1507278343878&auctionTag=&perPageSize=40&shopTag=yxjh&t=1507278343888&_tb_token_=ee756e7e74634&pvid=10_122.242.122.0_1231_1507278338487');   
        /*$source = get_web_page('http://hws.m.taobao.com/cache/wdetail/5.0/?id=557157320426');  */ 
        if(!$source){
            return ['code'=>202,'msg'=>'采集程序错误，无法继续'];
        }
        $result_data = json_decode($source, true);//图片
        $result_data = $result_data['data']['pageList'];
        if(!$result_data){
            return ['code'=>202,'msg'=>'没有采集数据'];
        }
        $item['title'] = $result_data[0]['title'];//标题
        $item['pic'] = str_replace('https', 'http', $result_data[0]['pictUrl']);//图片
        $item['view'] = 1;//浏览量
        $item['numIid'] = $result_data[0]['auctionId'];//商品ID
        $item['price'] = $result_data[0]['reservePrice'];//原价
        $item['couponPrice'] = $result_data[0]['zkPrice'];//现价
        $item['couponRate'] = round(($item['couponPrice'] / $item['price']) * 10, 1);//折扣
        $item['commissionRate'] = $result_data[0]['tkRate'];//佣金率                     
        $item['commission'] = $result_data[0]['tkCommFee'];//佣金  
        $item['volume'] = $result_data[0]['biz30day'];//30天销量
        $item['nick'] = $result_data[0]['nick'];//掌柜旺旺名
        $item['sellerId'] = $result_data[0]['sellerId'];//卖家id
        $item['couponAmount'] = $result_data[0]['couponAmount'];//优惠卷
        if($result_data[0]['userType'] == 0) {
            $item['userType'] = "0";
        } else {
            $item['userType'] = "1";
        }
        if ($result_data[0]['includeDxjh'] == 1) {//1代表定向计划
            $item['dxjhType'] = "1";
        } else {
            $item['dxjhType'] = "0";
        }
        $item['startTime'] = time();//开始时间
        $endTime = config('web.WEB_EXPTIME');
        if ($endTime) {
            $item['endTime'] = (int)(time() + $endTime * 3600);//结束时间
        } else {
            $item['endTime'] = (int)(time() + 72 * 86400);
        }
        $item['keywords'] = implode(',', get_tags($item['title']));//关键词
        $item['description'] = $item['title'].'，现价只需要'.$item['price'].'元，领券后下单还可优惠'.$item['couponAmount'].'元，赶紧抢购吧！';//描述
        return ['code'=>200,'msg'=>$item];
    } 

    public function add()
    {
        $GoodsMd = new GoodsModel();
        if (request()->isPost()) {
            $data = input('post.');
            if (!$data['startTime']) {
                $data['startTime'] = time();
            } else {
                $data['startTime'] = strtotime($data['startTime']);
            }
            if (!$data['endTime']) {
                $data['endTime'] = time();
            } else {
                $data['endTime'] = strtotime($data['endTime']);
            }
            $data['open'] = config('web.WEB_OPE');
            $data['view'] = 1;
            $data['uid'] = session('userid');

            if (!$data['description']) {
                $description = htmlspecialchars_decode(strip_tags($data['content']));
                $description = preg_replace( "@<script(.*?)</script>@is", "", $description );
                $description = preg_replace( "@<iframe(.*?)</iframe>@is", "", $description );
                $description = preg_replace( "@<style(.*?)</style>@is", "", $description );
                $description = preg_replace( "@<(.*?)>@is", "", $description );
                $description = str_replace("&nbsp;", '', $description);
                $description = str_replace(" ", '', mb_substr($description, 0, 200, 'utf-8'));
                $data['description'] = $description;
            }

            $MemberDb = Db::name('member');
            $MemberDb->where('userid', session('userid'))->setInc('point', config('point.ADD_POINT'));
            if ($GoodsMd->add($data)) {
                return json(array('code' => 200, 'msg' => '添加成功'));
            } else {
                return json(array('code' => 0, 'msg' => '添加失败'));
            }
        }
        $CategoryDb = Db::name('category');
        $CategoryList = $CategoryDb->select();
        $this->assign('CategoryList', $CategoryList);
        $webtag = config('web.WEB_TAG');
        $TagList = explode(',', $webtag);
        $this->assign('TagList', $TagList);
        return view();
    }  
    public function edit()
    {
        $GoodsMd = new GoodsModel();
        if (request()->isPost()) {
            $data = input('post.');
            if (!$data['startTime']) {
                $data['startTime'] = time();
            } else {
                $data['startTime'] = strtotime($data['startTime']);
            }
            if (!$data['endTime']) {
                $data['endTime'] = time();
            } else {
                $data['endTime'] = strtotime($data['endTime']);
            }            
            if ($GoodsMd->edit($data)) {
                return json(array('code' => 200, 'msg' => '修改成功'));
            } else {
                return json(array('code' => 0, 'msg' => '修改失败'));
            }
        }
        $CategoryDb = Db::name('category');
        $Goods = $GoodsMd->find(input('id'));
        $CategoryList = $CategoryDb->select();
        $this->assign(array('CategoryList' => $CategoryList, 'Goods' => $Goods));
        $Keywords = explode(',', $Goods['keywords']);
        $this->assign('Keywords', $Keywords);  
        $webtag = config('web.WEB_TAG');
        $TagList = explode(',', $webtag);
        $this->assign('TagList', $TagList);        
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
        $GoodsMd = new GoodsModel();
        if ($GoodsMd->destroy(input('post.id'))) {
            return json(array('code' => 200, 'msg' => '删除成功'));
        } else {
            return json(array('code' => 0, 'msg' => '删除失败'));
        }
    }

    public function delss()
    {
        $GoodsMd = new GoodsModel();
        $params = input('post.');
        $ids = implode(',', $params['ids']);
        $result = $GoodsMd->batches('delete', $ids);
        if ($result) {
            return json(array('code' => 200, 'msg' => '批量删除成功'));
        } else {
            return json(array('code' => 0, 'msg' => '批量删除失败'));
        }
    }

    public function dels_all()
    {
        if (Db::name('goods')->delete(true)) {
            return json(array('code' => 200, 'msg' => '删除成功'));
        } else {
            return json(array('code' => 0, 'msg' => '删除失败'));
        }
    }

    public function dels_category()
    {
    	$data = input('post.');
        if (Db::name('goods')->where('cid',$data['cid'])->delete()) {
            return json(array('code' => 200, 'msg' => '删除成功'));
        } else {
            return json(array('code' => 0, 'msg' => '删除失败'));
        }
    }

    public function dels_time()
    {
    	$data = input('post.');
    	if (!$data['startTime']) {
       		$endTime = strtotime($data['endTime']);
	        if (Db::name('goods')->where('startTime','<',$endTime)->delete()) {
	            return json(array('code' => 200, 'msg' => '删除成功'));
	        } else {
	            return json(array('code' => 0, 'msg' => '删除失败'));
	        }
    	} elseif (!$data['endTime']) {
    		$startTime = strtotime($data['startTime']);
	        if (Db::name('goods')->where('startTime','>=',$startTime)->delete()) {
	            return json(array('code' => 200, 'msg' => '删除成功'));
	        } else {
	            return json(array('code' => 0, 'msg' => '删除失败'));
	        }       		
    	} else {
			$startTime = strtotime($data['startTime']);
	   		$endTime = strtotime($data['endTime']);    	
	        if (Db::name('goods')->where('startTime','>=',$startTime)->where('startTime','<',$endTime)->delete()) {
	            return json(array('code' => 200, 'msg' => '删除成功'));
	        } else {
	            return json(array('code' => 0, 'msg' => '删除失败'));
	        }
    	}
    }

    public function dels_all_stale()
    {
        if (Db::name('goods')->where('endTime','<',time())->delete()) {
            return json(array('code' => 200, 'msg' => '删除成功'));
        } else {
            return json(array('code' => 0, 'msg' => '删除失败'));
        }
    }

    public function dels_stale()
    {
        $GoodsMd = new GoodsModel();
        $ks=input('ks');
        $GoodsList = $GoodsMd->alias('G')->join('category C', 'C.id=G.cid')->field('G.*,C.id as cid,C.name')->order('G.id desc')->where('title','like','%'.$ks.'%')->where('endTime','<',time())->find();
        if($GoodsList){
            if ($GoodsMd->destroy($GoodsList['id'])) {
                return json(array('code' => 200,'data'=>$GoodsList['id'])); //成功加一
            } else {
                return json(array('code' => 201,'data'=>$GoodsList['id'])); //失败加一
            }
        }else{
            return json(array('code' => 202)); //完结
        }
    }


    public function changechoice()
    {
        if (request()->isAjax()) {
            $change = input('change');
            $choice = Db::name('goods')->field('choice')->where('id', $change)->find();
            $choice = $choice['choice'];
            if ($choice == 1) {
                Db::name('goods')->where('id', $change)->update(['choice' => 0]);
                echo 1;
            } else {
                Db::name('goods')->where('id', $change)->update(['choice' => 1]);
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
            $settop = Db::name('goods')->field('settop')->where('id', $change)->find();
            $settop = $settop['settop'];
            if ($settop == 1) {
                Db::name('goods')->where('id', $change)->update(['settop' => 0]);
                echo 1;
            } else {
                Db::name('goods')->where('id', $change)->update(['settop' => 1]);
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
            $show = Db::name('goods')->field('show')->where('id', $change)->find();
            $show = $show['show'];
            if ($show == 1) {
                Db::name('goods')->where('id', $change)->update(['show' => 0]);
                echo 1;
            } else {
                Db::name('goods')->where('id', $change)->update(['show' => 1]);
                echo 2;
            }
        } else {
            $this->error('非法操作');
        }
    }
}