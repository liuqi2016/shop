<?php
namespace app\index\controller;
use think\Controller;
use think\Db;
class Live extends Controller
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
/*        $req->setQ($ks);*/
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
                continue;
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
        $GoodsList = $item;
        $this->assign('GoodsList', $GoodsList);
        $this->assign('nav_curr', 'live');
		return view();
	}
}