<?php
namespace app\admin\controller;
use think\Controller;
use think\Db;
use app\admin\model\Robotxuanpinku as RobotxuanpinkuModel;
class Robotxuanpinku extends Common
{
    
    public function index()
    {
        vendor('taobao.TopClient');
        vendor('taobao.ResultSet');
        vendor('taobao.RequestCheckUtil');
        vendor('taobao.TopLogger');
        vendor('taobao.request.TbkUatmFavoritesGetRequest');
		$c = new \TopClient;
        $c->appkey = config('web.WEB_TBKEY');
        $c->secretKey = config('web.WEB_TBSECREC');
		$req = new \TbkUatmFavoritesGetRequest;
		$req->setPageNo("1");
		$req->setPageSize("20");
		$req->setFields("favorites_title,favorites_id,type");
		$req->setType("-1");
		$resp = $c->execute($req);
        $resp = object_to_array($resp);
        if(isset($resp['code'])){
            return ['code'=>202,'msg'=>'采集器接口错误：'.$resp['msg']];
        }
        if(!isset($resp['results'])){
            return ['code'=>202,'msg'=>'采集器接口错'];
        }
        $result_data = $resp['results']['tbk_favorites'];
        $RobotxuanpinkuMd = new RobotxuanpinkuModel();
		foreach($result_data as $k=>$v){
			$item['catIds'] = $v['favorites_id'];//选品库ID
			$item['name'] = $v['favorites_title'];//选品库名称
			$item['xpkType'] = $v['type'];//选品库类型，1：普通类型，2高佣金类型
	        $query = $RobotxuanpinkuMd->where('catIds','=',$item['catIds'])->select();
	        if (!$query) {
	            $RobotxuanpinkuMd->add($item);
	        }
        }
        $RobotxuanpinkuList = $RobotxuanpinkuMd->order('id ASC')->select();
        $this->assign('RobotxuanpinkuList', $RobotxuanpinkuList);
        $today = strtotime(date('Y-m-d',time()));
        $this->assign('Today', $today);
        return view();
    }
    public function add()
    {
        $RobotxuanpinkuMd = new RobotxuanpinkuModel();
        if (request()->isPost()) {
            $data = input('post.');
            $data['sort'] = preg_replace('# #','',$data['sort']);//清除空格
            $data['userType'] = preg_replace('# #','',$data['userType']);
            $data['time'] = time();
            if ($RobotxuanpinkuMd->add($data)) {
                return json(array('code' => 200, 'msg' => '添加成功'));
            } else {
                return json(array('code' => 0, 'msg' => '添加失败'));
            }
        }
        $RobotxuanpinkuList = $RobotxuanpinkuMd->order('id ASC')->select();
        $this->assign('RobotxuanpinkuList', $RobotxuanpinkuList);
        $CategoryDb = Db::name('category');
        $CategoryList = Db::name('category')->select();
        $this->assign('CategoryList', $CategoryList);        
        return view();
    }
    public function edit()
    {
        $RobotxuanpinkuMd = new RobotxuanpinkuModel();
        if (request()->isPost()) {
            $data = input('post.');
            $data['sort'] = preg_replace('# #','',$data['sort']);//清除空格
            $data['userType'] = preg_replace('# #','',$data['userType']);
            if ($RobotxuanpinkuMd->edit($data)) {
                return json(array('code' => 200, 'msg' => '修改成功'));
            } else {
                return json(array('code' => 0, 'msg' => '修改失败'));
            }
        }
        $Robotxuanpinku = $RobotxuanpinkuMd->find(input('id'));
        $RobotxuanpinkuList = $RobotxuanpinkuMd->order('id ASC')->select();
        $this->assign(array('RobotxuanpinkuList' => $RobotxuanpinkuList, 'Robotxuanpinku' => $Robotxuanpinku));
        $CategoryDb = Db::name('category');
        $CategoryList = Db::name('category')->select();
        $this->assign('CategoryList', $CategoryList);        
        return view();
    }
    public function dels()
    {
        $dels = Db::name('robotxuanpinku')->delete(input('id'));
        if ($dels) {
            return json(array('code' => 200, 'msg' => '删除成功'));
        } else {
            return json(array('code' => 0, 'msg' => '删除失败'));
        }
    }
    public function delss()
    {
        $RobotxuanpinkuMd = new RobotxuanpinkuModel();
        $params = input('post.');
        $ids = implode(',', $params['ids']);
        $result = $RobotxuanpinkuMd->batches('delete', $ids);
        if ($result) {
            return json(array('code' => 200, 'msg' => '批量删除成功'));
        } else {
            return json(array('code' => 0, 'msg' => '批量删除失败'));
        }
    }

    public function collect_all()
    {
        $RobotxuanpinkuMd = new RobotxuanpinkuModel();
        $data = input('post.');
        $info = null;
        if(!isset($data['num'])){
            $data['num'] = 0;
        }
        $today = strtotime(date('Y-m-d',time()));
        if(!isset($data['id'])){
            $info = $RobotxuanpinkuMd->where("lastPage<page or lastTime<=".$today)->order('id asc')->find();
            if(!$info){
                $query = $RobotxuanpinkuMd->order('id ASC')->select();
                foreach ($query as $k => $v) {
                    $RobotxuanpinkuMd->where(['id'=>$v['id']])->update(['lastPage'=>0]); 
                }
                return json(array('code' => 202, 'msg' => '采集已完成，请在商品管理中查看！'));
            }
            if($info['lastTime'] <= $today){//重置页数
                $info['lastPage'] = 0;
            }
        }else{
            $info = $RobotxuanpinkuMd->where(['id'=>$data['id']])->find();
            if($info['lastPage'] >= $info['page'] && date('Y-m-d',$info['lastTime'])  == date('Y-m-d',time())){
                $info = $RobotxuanpinkuMd->where("lastPage<page or lastTime<=".$today)->order('id asc')->find();
                if(!$info){
                    $query = $RobotxuanpinkuMd->order('id ASC')->select();
                    foreach ($query as $k => $v) {
                        $RobotxuanpinkuMd->where(['id'=>$v['id']])->update(['lastPage'=>0]); 
                    }
                    return json(array('code' => 202, 'msg' => '采集已完成，请在商品管理中查看！'));
                }
                if($info['lastTime'] <= $today){//重置页数
                    $info['lastPage'] = 0;
                }
            }
        }
        //没完成，开始采集
        $res = $this->collect_unit($info,100);//设定每页采集条数
        if($res['code'] == 202){
            return json($res);
        }else{
            //更新采集信息
            $RobotxuanpinkuMd->where(['id'=>$info['id']])->update(['lastPage'=>$info['lastPage']+1,'lastTime'=>time()]);
            $data['num'] = $data['num']+$res['num'];
            return json(array('code' => 200,'num'=>$data['num'],'id'=>$info['id'], 'msg' => '当前正在运行采集器【' . $info['name'] . '】，已采集' . ($info['lastPage']+1) . '页,目前共采集到商品' . $data['num'].'件。'));
        }
    }

    public function collect_one()
    {
        //获取采集信息
        $data = input('post.');
        if(!isset($data['num'])){
            $data['num'] = 0;
        }
        $RobotxuanpinkuMd = new RobotxuanpinkuModel();
        $info = $RobotxuanpinkuMd->where(['id'=>$data['id']])->find();
        if($info['lastPage'] >= $info['page'] && date('Y-m-d',$info['lastTime']) == date('Y-m-d',time())){
            $RobotxuanpinkuMd->where(['id'=>$data['id']])->update(['lastPage'=>0]);
            return json(array('code' => 202, 'msg' => '采集已完成，请在商品管理中查看！'));
        }else{
            if($info['lastPage'] >= $info['page']){
                $info['lastPage']= 0;
            }
            //没完成，开始采集
            $res = $this->collect_unit($info,100);//设定每页采集条数
            if($res['code'] == 202){
                return json($res);
            }else{
                //更新采集信息
                $RobotxuanpinkuMd->where(['id'=>$data['id']])->update(['lastPage'=>$info['lastPage']+1,'lastTime'=>time()]);
                $res['num'] = $data['num']+$res['num'];
                return json(array('code' => 200, 'msg' => '已采集' . ($info['lastPage']+1) . '页,目前共采集到商品' . $res['num'] .'件。','num'=>$res['num']));
            }
        }
    }

    /**
     * 采集程序
     * @param $curPage
     * @param int $pageNum 每页采集个数
     * @return array
     * @throws \think\Exception
     */
    private function collect_unit($info,$pageNum=0)
    {
		$curPage = $info['lastPage']+1;
        vendor('taobao.TopClient');
        vendor('taobao.ResultSet');
        vendor('taobao.RequestCheckUtil');
        vendor('taobao.TopLogger');
        vendor('taobao.request.TbkUatmFavoritesItemGetRequest');
		$c = new \TopClient;
        $c->appkey = config('web.WEB_TBKEY');
        $c->secretKey = config('web.WEB_TBSECREC');
		$req = new \TbkUatmFavoritesItemGetRequest;
		$req->setPlatform($info['platform']);
		$req->setPageSize("100");
		$req->setAdzoneId(get_adzoneid(config('web.WEB_YHQPID')));
		$req->setUnid("3456");
		$req->setFavoritesId($info['catIds']);
		$req->setPageNo($curPage);
		$req->setFields("num_iid,title,pict_url,small_images,reserve_price,reserve_price,zk_final_price,user_type,provcity,item_url,seller_id,volume,nick,shop_title,zk_final_price_wap,event_start_time,event_end_time,tk_rate,status,type,click_url,coupon_click_url,coupon_end_time,coupon_info,coupon_start_time,coupon_total_count,coupon_remain_coun");
		$resp = $c->execute($req);
        $resp = object_to_array($resp);
        if(isset($resp['code'])){
            return ['code'=>202,'msg'=>'采集器接口错误：'.$resp['msg']];
        }
        if(!isset($resp['results'])){
            return ['code'=>202,'msg'=>'采集器接口错'];
        }     
        $result_data = $resp['results']['uatm_tbk_item'];
        //获取数据
        $GoodsDb = Db::name('goods');
        $item = $items =  [];
        $i = 0;
        //设定取前多少条
        if($pageNum){
            $result_data = array_slice($result_data,0,$pageNum);
        }
        foreach($result_data as $k=>$v){
            if($info['cid'] == 0) {
            	 return ['code'=>202,'msg'=>'入库分类未设置，请设置后再来采集！'];
                /*continue;*/
            }else{
                $item['cid'] = $info['cid'];//分类
                $item['uid'] = session('userid');//发布者ID
                $item['title'] = $v['title'];//标题
                $item['pic'] = str_replace('https', 'http', $v['pict_url']);//图片
                $item['view'] = 1;//浏览量
                $item['numIid'] = $v['num_iid'];//商品ID
                if (isset($v['coupon_click_url'])) {
	                $item['price'] = $v['zk_final_price'];//原价
	                $item['couponPrice'] = $item['price'] - get_word($v['coupon_info'], '减', '元');//现价
	                $item['clickUrl'] = $v['coupon_click_url'];//推广链接
	                $item['couponAmount'] = get_word($v['coupon_info'], '减', '元');//优惠卷
	                $item['couponTotalcount'] = $v['coupon_total_count'];//优惠券总量
	                $item['couponRemaincount'] = $v['coupon_remain_coun'];//优惠券剩余量	                
                } else {
	                $item['price'] = $v['reserve_price'];//原价
	                $item['couponPrice'] = $v['zk_final_price'];//现价
	                $item['clickUrl'] = $v['click_url'];//推广链接
	                $item['couponAmount'] = '';//优惠卷
	                $item['couponTotalcount'] = '';//优惠券总量
	                $item['couponRemaincount'] = '';//优惠券剩余量	                
                }
                $item['couponRate'] = round(($item['couponPrice'] / $item['price']) * 10, 1);//折扣
                $item['commissionRate'] = $v['tk_rate'];//佣金率                     
                $item['commission'] = round($item['price'] * ($item['commissionRate'] / 100), 1);//佣金  
                $item['volume'] = $v['volume'];//30天销量
                $item['nick'] = $v['nick'];//掌柜旺旺名
                $item['sellerId'] = $v['seller_id'];//卖家id
                if($v['user_type'] == 0) {
                    $item['userType'] = "0";
                } else {
                    $item['userType'] = "1";
                }
                if ($v['type'] == 3) {//1代表定向计划
                    $item['dxjhType'] = "1";
                } else {
                    $item['dxjhType'] = "0";
                }
                $item['startTime'] = time();//开始时间
                if (isset($v['coupon_click_url'])) {
                    $item['endTime'] = strtotime($v['coupon_end_time']."+1 day");//结束时间
                } else {
	                $endTime = config('web.WEB_EXPTIME');
	                if ($endTime) {
	                    $item['endTime'] = (int)(time() + $endTime * 3600);//结束时间
	                } else {
	                    $item['endTime'] = (int)(time() + 72 * 86400);
	                }
                }
                $item['keywords'] = implode(',', get_tags($item['title']));//关键词
                $item['description'] = $item['title'].'，现价只需要'.$item['price'].'元，领券后下单还可优惠'.$item['couponAmount'].'元，赶紧抢购吧！';//描述
                $item['content'] = '';//内容
            }

            $query = $GoodsDb->where('numIid','=',$item['numIid'])->select();
            if (!$query) {
                $items[] = $item;
            } else {
                $GoodsDb->where('numIid',$item['numIid'])->update($item);
                //$i++;
            }
        }
        if(!empty($items)){
            $result = $GoodsDb->insertAll($items);
            $i = $i + count($items);
        }
        return ['code'=>200,'num'=>$i];
    }
}
