<?php
namespace app\index\controller;
use think\Controller;
use think\Db;
class Index extends Controller
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
        $settop['settop'] = 1;
        $sort = preg_replace('# #','',config('web.WEB_SORT'));
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
        $SettopGoodsList = $GoodsDb->alias('G')->join('category C', 'C.id=G.cid')->join('member M', 'M.userid=G.uid')->field('G.*,C.id as cid,M.userid,M.userhead,M.username,C.name')->where($show)->where($settop)->order('G.id desc')->limit(5)->select();
        $this->assign('SettopGoodsList', $SettopGoodsList);
        $GoodsList = $GoodsDb->alias('G')->join('category C', 'C.id=G.cid')->join('member M', 'M.userid=G.uid')->field('G.*,C.id as cid,M.userid,M.userhead,M.username,C.name')->where($show)->order($order)->paginate(40);
        $this->assign('GoodsList', $GoodsList);
        $this->assign('nav_curr', 'index');
        return view();
    }

    public function cat()
    {
        $id = input('id');
        $this->assign('cid', $id);
        $sort = input('sort');
        $this->assign('sort', $sort);
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
        if (empty($id)) {
            return $this->error('亲！你迷路了');
        } else {
            $CategoryDb = Db::name('category'); 
            $Category = $CategoryDb->where("id = {$id}")->find();
            $this->assign('Category', $Category);
            if ($Category) {
                $GoodsDb = Db::name('goods');
                if (config('web.WEB_ADC') == 1) {
                    $this->collect_unit($Category['name'],$Category['id']);
                }
                $show['G.show'] = 1;
                $GoodsList = $GoodsDb->alias('G')->join('category C', 'C.id=G.cid')->join('member M', 'M.userid=G.uid')->field('G.*,C.id as cid,M.userid,M.userhead,M.username,C.name')->where("G.cid={$id}")->where($show)->order($order)->paginate(40);
                $this->assign('GoodsList', $GoodsList);
                return view();
            } else {
                $this->error("亲！你迷路了！");
            }
        }
    }

    public function dtl()
    {
        $id = input('id');
        if (empty($id)) {
            return $this->error('亲！你迷路了');
        } else {
            $GoodsDb = Db::name('goods');
            $query = $GoodsDb->where("id = {$id}")->find();
            if ($query) {
                //写入浏览记录cookie
                $person_dtl_history = cookie('person_dtl_history');
                if(!$person_dtl_history || !in_array($id,$person_dtl_history)){
                    $person_dtl_history[] = $id;
                }
                cookie('person_dtl_history',$person_dtl_history);
                //取商品内容
                $GoodsDb->where("id = {$id}")->setInc('view', 1);
                $Goods = $GoodsDb->alias('G')->join('category C', 'C.id=G.cid')->join('member M', 'M.userid=G.uid')->field('G.*,C.id as cid,C.name,M.userid,M.grades,M.point,M.userhead,M.username')->find($id);
                $this->assign('Goods', $Goods);
                $content = $Goods['content'];
                $content = htmlspecialchars_decode($content);
                $this->assign('content', $content);
                //取同所在类目
                $CategoryDb = Db::name('category'); 
                $Category = $CategoryDb->where("id = {$Goods['cid']}")->find();
                $this->assign('Category', $Category);
                //取同类目数据
                $show['G.show'] = 1;
                $GoodsList = $GoodsDb->alias('G')->join('category C', 'C.id=G.cid')->join('member M', 'M.userid=G.uid')->field('G.*,C.id as cid,M.userid,M.userhead,M.username,C.name')->where("G.cid={$Goods['cid']}")->where($show)->order('G.id desc')->paginate(40);
                $this->assign('GoodsList', $GoodsList);
                return view();
            } else {
                return $this->error('亲！你迷路了');
            }
        }
    }

    public function taobao()
    {
        $Goods['numIid'] = input('id');//商品ID
        $Goods['title'] = input('title');//标题
        $Goods['pic'] = input('pic');//图片
        $Goods['couponAmount'] = input('couponAmount');//优惠卷
        $Goods['price'] = input('price');//原价
        $Goods['couponPrice'] = input('couponPrice');//现价
        $Goods['volume'] = input('volume');//30天销量
        $Goods['clickUrl'] = input('clickUrl');//推广链接
        $Goods['description'] = input('description');//描述
        if (empty($Goods['numIid'])) {
            return $this->error('亲！你迷路了');
        } else {
            $this->assign('Goods', $Goods);
            return view();
        }
    }

	public function search()
    {
        $ks = input('ks');
        $this->assign('ks', $ks);
        if (empty($ks)) {
            return $this->error('亲！你迷路了');
        } else {
			$GoodsDb = Db::name('goods');
			$show['G.show'] = 1;
			$GoodsList = $GoodsDb->alias('G')->join('category C', 'C.id=G.cid')->join('member M', 'M.userid=G.uid')->field('G.*,C.id as cid,M.userid,M.userhead,M.username,C.name')->order('G.id desc')->where($show)->where('title','like','%'.$ks.'%')->paginate(16,false,$config = ['query'=>array('ks'=>$ks)]);
            $this->assign('GoodsList', $GoodsList);

            if ($GoodsList[0] == '') {
                vendor('taobao.TopClient');
                vendor('taobao.ResultSet');
                vendor('taobao.RequestCheckUtil');
                vendor('taobao.TopLogger');
                vendor('taobao.request.TbkDgItemCouponGetRequest');
                $c = new \TopClient();
                $c->appkey = config('web.WEB_TBKEY');
                $c->secretKey = config('web.WEB_TBSECREC');
                $req = new \TbkDgItemCouponGetRequest;
                $req->setAdzoneId(get_adzoneid(config('web.WEB_YHQPID')));
                $req->setPlatform("1");
                //$req->setCat($info['cid']);
                $req->setPageSize("100");
                $req->setQ($ks);
                $req->setPageNo("1");
                $resp = $c->execute($req);
                $resp = object_to_array($resp);
                if(isset($resp['code'])){
                    return $this->error('采集器接口错误：'.$resp['msg']);
                }
                if(!isset($resp['results'])){
                    return $this->error('采集器接口错');
                }
                $result_data = $resp['results']['tbk_coupon'];
                $result_data = array_slice($result_data,0,100);//取100条
                $item = array();
                foreach($result_data as $k=>$v){
                    if(false) {
                        /*continue;*/
                    }else{
                        $item[$k]['cid'] = $info['cid'];//分类
                        $item[$k]['uid'] = session('userid');//发布者ID
                        $item[$k]['title'] = $v['title'];//标题
                        $item[$k]['pic'] = str_replace('https', 'http', $v['pict_url']);//图片
                        $item[$k]['view'] = 1;//浏览量
                        $item[$k]['numIid'] = $v['num_iid'];//商品ID
                        $item[$k]['price'] = $v['zk_final_price'];//原价
                        $item[$k]['couponPrice'] = $item[$k]['price'] - get_word($v['coupon_info'], '减', '元');//现价
                        $item[$k]['couponRate'] = round(($item[$k]['couponPrice'] / $item[$k]['price']) * 10, 1);//折扣
                        $item[$k]['commissionRate'] = $v['commission_rate'];//佣金率  
                        $item[$k]['commission'] = round($item[$k]['price'] * ($item[$k]['commissionRate'] / 100), 1);//佣金  
                        $item[$k]['volume'] = $v['volume'];//30天销量
                        $item[$k]['nick'] = $v['nick'];//掌柜旺旺名
                        $item[$k]['sellerId'] = $v['seller_id'];//卖家id
                        $item[$k]['clickUrl'] = $v['coupon_click_url'];//推广链接
                        $item[$k]['taoToken'] = '';//淘口令
                        $item[$k]['couponAmount'] = get_word($v['coupon_info'], '减', '元');//优惠卷
                        $item[$k]['couponTotalcount'] = $v['coupon_total_count'];//优惠券总量
                        $item[$k]['couponRemaincount'] = $v['coupon_remain_count'];//优惠券剩余量
                        if($v['user_type'] == 1) {
                            $item[$k]['userType'] = "0";
                        } else {
                            $item[$k]['userType'] = "1";
                        }
                        $item[$k]['dxjhType'] = "0";//定向计划
                        $item[$k]['startTime'] = time();//开始时间
                        $item[$k]['endTime'] = strtotime($v['coupon_end_time']."+1 day");//结束时间
                        $item[$k]['keywords'] = implode(',', get_tags($item[$k]['title']));//关键词
                        if ($v['item_description']) {
                            $item[$k]['description'] = $v['item_description'];//描述
                        } else {
                            $item[$k]['description'] = $item[$k]['title'].'，现价只需要'.$item[$k]['price'].'元，领券后下单还可优惠'.$item[$k]['couponAmount'].'元，赶紧抢购吧！';//描述
                        }
                        $item[$k]['content'] = '';//内容
                    }
                }
                $GoodsSList = $item;
                $this->assign('GoodsSList', $GoodsSList);
            }
			return view();
		}
    }

    //采集程序
    private function collect_unit($keyword,$cid)
    {
        vendor('taobao.TopClient');
        vendor('taobao.ResultSet');
        vendor('taobao.RequestCheckUtil');
        vendor('taobao.TopLogger');
        vendor('taobao.request.TbkDgItemCouponGetRequest');
        $c = new \TopClient();
        $c->appkey = config('web.WEB_TBKEY');
        $c->secretKey = config('web.WEB_TBSECREC');
        $req = new \TbkDgItemCouponGetRequest;
        $req->setAdzoneId(get_adzoneid(config('web.WEB_YHQPID')));
        $req->setPlatform("1");
        //$req->setCat($info['cid']);
        $req->setPageSize("20");
        $req->setQ($keyword);
        $req->setPageNo("1");
        $resp = $c->execute($req);
        $resp = object_to_array($resp);
        if(isset($resp['code'])){
            return $this->error('采集器接口错误：'.$resp['msg']);
        }
        if(!isset($resp['results'])){
            return $this->error('采集器接口错');
        }
        $result_data = $resp['results']['tbk_coupon'];
        $result_data = array_slice($result_data,0,20);//取100条
        $item = $items =  [];
        foreach($result_data as $k=>$v){
            if(false) {
                /*continue;*/
            }else{
                $item['cid'] = $cid;//分类
                if (session('userid')) {
                    $item['uid'] = session('userid');//发布者ID
                } else {
                    $item['uid'] = '1';//发布者ID
                }
                $item['title'] = $v['title'];//标题
                $item['pic'] = str_replace('https', 'http', $v['pict_url']);//图片
                $item['view'] = 1;//浏览量
                $item['numIid'] = $v['num_iid'];//商品ID
                $item['price'] = $v['zk_final_price'];//原价
                $item['couponPrice'] = $item['price'] - get_word($v['coupon_info'], '减', '元');//现价
                $item['couponRate'] = round(($item['couponPrice'] / $item['price']) * 10, 1);//折扣
                $item['commissionRate'] = $v['commission_rate'];//佣金率  
                $item['commission'] = round($item['price'] * ($item['commissionRate'] / 100), 1);//佣金  
                $item['volume'] = $v['volume'];//30天销量
                $item['nick'] = $v['nick'];//掌柜旺旺名
                $item['sellerId'] = $v['seller_id'];//卖家id
                $item['clickUrl'] = $v['coupon_click_url'];//推广链接
                $item['taoToken'] = '';//淘口令
                $item['couponAmount'] = get_word($v['coupon_info'], '减', '元');//优惠卷
                $item['couponTotalcount'] = $v['coupon_total_count'];//优惠券总量
                $item['couponRemaincount'] = $v['coupon_remain_count'];//优惠券剩余量
                if($v['user_type'] == 1) {
                    $item['userType'] = "0";
                } else {
                    $item['userType'] = "1";
                }
                $item['dxjhType'] = "0";//定向计划
                $item['startTime'] = time();//开始时间
                $item['endTime'] = strtotime($v['coupon_end_time']."+1 day");//结束时间
                $item['keywords'] = implode(',', get_tags($item['title']));//关键词
                if ($v['item_description']) {
                    $item['description'] = $v['item_description'];//描述
                } else {
                    $item['description'] = $item['title'].'，现价只需要'.$item['price'].'元，领券后下单还可优惠'.$item['couponAmount'].'元，赶紧抢购吧！';//描述
                }
                $item['content'] = '';//内容
            }
            $GoodsDb = Db::name('goods');
            $query = $GoodsDb->where('numIid','=',$item['numIid'])->select();
            if (!$query) {
                $items[] = $item;
            } else {
                $GoodsDb->where('numIid',$item['numIid'])->update($item);
            }
        }
        if(!empty($items)){
            $result = $GoodsDb->insertAll($items);
        }
    }    
}