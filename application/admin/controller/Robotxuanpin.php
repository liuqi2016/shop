<?php
namespace app\admin\controller;
use think\Controller;
use think\Db;
class Robotxuanpin extends Common{
    public function index()
    {
        //分页
        $listRows = 40;//每页商品数
        $config   = config('paginate');
        $class = false !== strpos($config['type'], '\\') ? $config['type'] : '\\think\\paginator\\driver\\' . ucwords($config['type']);
        $page  = isset($config['page']) ? (int) $config['page'] : call_user_func([$class,'getCurrentPage',], $config['var_page']);
        $page = $page < 1 ? 1 : $page;
        $config['path'] = isset($config['path']) ? $config['path'] : call_user_func([$class, 'getCurrentPath']);

        $keyword = input('keyword');
        $startPrice = input('startPrice');
        $endPrice = input('endPrice');
        $startTkRate = input('startTkRate');
        $endTkRate = input('endTkRate');
        $startBiz30day = input('startBiz30day');
        $sort = preg_replace('# #','',input('sort'));//清除空格
        if (!check_url($keyword)) {//判断是否url
            $source = get_web_page('http://pub.alimama.com/items/channel/qqhd.json?q='.$keyword.'&startPrice='.$startPrice.'&endPrice='.$endPrice.'&startTkRate='.$startTkRate.'&endTkRate='.$endTkRate.'&startBiz30day='.$startBiz30day.'&queryType=0&sortType='.$sort.'&dpyhq=1&shopTag=dpyhq&channel=qqhd&toPage='.$page.'&perPageSize='.$listRows.'&shopTag=&t=1501744576739&_tb_token_=f6D5OIxRIsq');
        }else{
            $source = get_web_page('http://pub.alimama.com/items/channel/qqhd.json?q='.$keyword.'&channel=qqhd&perPageSize=40&shopTag=&t=1501744698835&_tb_token_=f6D5OIxRIsq');
        }
        $result_data = json_decode($source, true);
        $result_data = $result_data['data'];

        $total = $result_data['paginator']['items'] ? $result_data['paginator']['items'] : 0;
        if ($total > 4000) {//最多取100页，4000条数据
            $total = 4000;
        }
        if($keyword){
            $config['query']['keyword'] = $keyword;
        }
        if($startPrice){
            $config['query']['startPrice'] = $startPrice;
        }
        if($endPrice){
            $config['query']['endPrice'] = $endPrice;
        }
        if($startTkRate){
            $config['query']['startTkRate'] = $startTkRate;
        }
        if($endTkRate){
            $config['query']['endTkRate'] = $endTkRate;
        }
        if($startBiz30day){
            $config['query']['startBiz30day'] = $startBiz30day;
        }
        if($sort){
            $config['query']['sort'] = $sort;
        } 
        $page_obj = $class::make($result_data['pageList'], $listRows, $page, $total, false, $config);

        if ($result_data['head']['status'] == 'OK') {
            for ($i = 0; $i < $result_data['paginator']['length']; $i++) {
                //阿里妈妈缓存处理
                $item['title'] = str_replace("</span>", "", str_replace("<span class=H>", "", $result_data['pageList'][$i]['title']));//标题
                $item['pic'] = str_replace('https', 'http', $result_data['pageList'][$i]['pictUrl']);//图片
                $item['view'] = 1;//浏览量
                $item['numIid'] = $result_data['pageList'][$i]['auctionId'];//商品ID
                $item['price'] = $result_data['pageList'][$i]['zkPrice'];//原价
                $item['couponPrice'] = $item['price'] - $result_data['pageList'][$i]['couponAmount'];//现价
                $item['couponRate'] = round(($item['couponPrice'] / $item['price']) * 10, 1);//折扣
                $item['commissionRate'] = $result_data['pageList'][$i]['eventRate'];//佣金率
                $item['commission'] = round($item['price'] * ($item['commissionRate'] / 100), 1);//佣金
                $item['volume'] = $result_data['pageList'][$i]['biz30day'];//30天销量
                $item['nick'] = $result_data['pageList'][$i]['nick'];//掌柜旺旺名
                $item['sellerId'] = $result_data['pageList'][$i]['sellerId'];//卖家id
                $item['couponAmount'] = $result_data['pageList'][$i]['couponAmount'];//优惠卷
                $item['couponTotalcount'] = $result_data['pageList'][$i]['couponTotalCount'];//优惠券总量
                $item['couponRemaincount'] = $result_data['pageList'][$i]['couponLeftCount'];//优惠券剩余量                
                if($result_data['pageList'][$i]['userType'] == 0) {
                    $item['userType'] = "0";
                } else {
                    $item['userType'] = "1";
                }
                if ($result_data['pageList'][$i]['includeDxjh'] == 1) {//1代表定向计划
                    $item['dxjhType'] = "0";
                } else {
                    $item['dxjhType'] = "0";
                }
                $item['dayLeft'] = $result_data['pageList'][$i]['dayLeft'];//剩余时间         
                $result['item_list'][] = $item;
            }
            $item_list = $result['item_list'];
            foreach ($item_list as $k=>$v){
                $RobotxuanpinList[$k] = $v;
                $RobotxuanpinList[$k]['id'] = $k+1;
                if (Db::name('goods')->where(array('numIid' => $v['numIid']))->count()){
                    $RobotxuanpinList[$k]['yes'] = 1;
                } else {
                    $RobotxuanpinList[$k]['yes'] = 0;
                }
            }
        } else {
            $RobotxuanpinList = null;
        }
        $this->assign('RobotxuanpinList', $RobotxuanpinList);
        $this->assign('RobotxuanpinPages',$page_obj->render());//输出分页
        $CategoryDb = Db::name('category');
        $CategoryList = $CategoryDb->select();
        $this->assign('CategoryList', $CategoryList);
        return view();
    }

    public function collect_all()
    {
        $params = input('post.');
        $cid = $params['cid'];
        $i = 0;
        foreach ($params['ids'] as $k=>$v) {
            $res = $this->collect_unit($v,$cid);
            if($res['code'] == 202){
                return json($res);
            }else{
                $i = $i+1;
            }
        }
        return json(array('code' => 200, 'msg' => '采集已完成，共采集到商品'.$i.'件。'));
    }

    public function collect_one()
    {
        $data = input('post.');
        $res = $this->collect_unit($data['id'],$data['cid']);
        if($res['code'] == 202){
            return json($res);
        }else{
            return json(array('code' => 200, 'msg' => '采集成功！'));
        }
    }

    public function islogin()
    {
        $cookie = get_cookie(config('web.WEB_MMCOOKIE'));
        $ip = request()->ip();
        $microtime = microtime(true) * 1000;
        $microtime = explode('.', $microtime);
        $api_url = 'http://pub.alimama.com/overview/unionaccountinfo.json?t='.$microtime[0].'&_tb_token_=9ix3ygeZUln&_input_charset=utf-8';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_url);
        curl_setopt($ch, CURLOPT_REFERER, 'http://www.alimama.com/index.htm');
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Cookie:{' . $cookie['HTTPHEADER'] . '}'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);  
/*        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);*/
        curl_setopt($ch, CURLOPT_MAXREDIRS, 2);
        $source = curl_exec($ch);
        curl_close($ch);
        $result_api = json_decode($source, true);
        if($result_api){       
            return true;
        }else{
            return false;
        }
    }

    /**
     * 采集程序
     * @param int $numIid 商品id
     * @return array
     * @throws \think\Exception
     */
    private function collect_unit($numIid,$cid)
    {
        $source = get_web_page('http://pub.alimama.com/items/channel/qqhd.json?q=http://item.taobao.com/item.htm?id='.$numIid.'&channel=qqhd&perPageSize=40&shopTag=&t=1501744698835&_tb_token_=f6D5OIxRIsq'); 
        if(!$source){
            return ['code'=>202,'msg'=>'采集程序错误，无法继续'];
        }
        $result_data = json_decode($source, true);
        if(!$result_data){
            return ['code'=>202,'msg'=>'没有采集数据'];
        }
        if($this->islogin() == false){
            return ['code'=>202,'msg'=>'请登录阿里妈妈获取cookie后再来采集吧！'];
        }   
        //获取数据
        $GoodsDb = Db::name('goods');
        $item = $items = [];
        $i = 0;
        //设定取前多少条
        $result_data = $result_data['data']['pageList'];
        //阿里妈妈缓存处理
        $cookie = get_cookie(config('web.WEB_MMCOOKIE'));
        $ip = request()->ip();
        $microtime = microtime(true) * 1000;
        $microtime = explode('.', $microtime);
        $api_url = 'http://pub.alimama.com/common/code/getAuctionCode.json?auctionid='.$result_data[0]['auctionId'].'&adzoneid='.get_adzoneid(config('web.WEB_YHQPID')).'&siteid='.get_siteid(config('web.WEB_YHQPID')).'&scenes=3&channel=tk_qqhd&t='.$microtime[0].'&_tb_token_='.$cookie['tb_token'].'&pvid=19_'.$ip.'_7067_'.$microtime[0].'';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_url);
        curl_setopt($ch, CURLOPT_REFERER, 'http://www.alimama.com/index.htm');
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Cookie:{' . $cookie['HTTPHEADER'] . '}'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);  
/*        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);*/
        curl_setopt($ch, CURLOPT_MAXREDIRS, 2);
        $source = curl_exec($ch);
        curl_close($ch);
        $result_api = json_decode($source, true);   
        if(!$result_api) {
            /*continue;*/
        }else{
            $item['cid'] = $cid;//分类
            $item['uid'] = session('userid');//发布者ID
            $item['title'] = str_replace("</span>", "", str_replace("<span class=H>", "", $result_data[0]['title']));//标题
            $item['pic'] = str_replace('https', 'http', $result_data[0]['pictUrl']);//图片
            $item['view'] = 1;//浏览量
            $item['numIid'] = $result_data[0]['auctionId'];//商品ID
            $item['price'] = $result_data[0]['zkPrice'];//原价
            $item['couponPrice'] = $item['price'] - $result_data[0]['couponAmount'];//现价
            $item['couponRate'] = round(($item['couponPrice'] / $item['price']) * 10, 1);//折扣
            $item['commissionRate'] = $result_data[0]['eventRate'];//佣金率
            $item['commission'] = round($item['price'] * ($item['commissionRate'] / 100), 1);//佣金
            $item['volume'] = $result_data[0]['biz30day'];//30天销量
            $item['nick'] = $result_data[0]['nick'];//掌柜旺旺名
            $item['sellerId'] = $result_data[0]['sellerId'];//卖家id
            $item['clickUrl'] = $result_api['data']['couponLink'];//推广链接
            $item['taoToken'] = $result_api['data']['couponLinkTaoToken'];//淘口令
            $item['couponAmount'] = $result_data[0]['couponAmount'];//优惠卷
            $item['couponTotalcount'] = $result_data[0]['couponTotalCount'];//优惠券总量
            $item['couponRemaincount'] = $result_data[0]['couponLeftCount'];//优惠券剩余量
            if($result_data[0]['userType'] == 0) {
                $item['userType'] = "0";
            } else {
                $item['userType'] = "1";
            }
            if ($result_data[0]['tkMktStatus'] == 1) {
                $item['dxjhType'] = "0";//定向计划
            } else {
                $item['dxjhType'] = "1";
            }
            $item['startTime'] = time();//开始时间
            $item['endTime'] = strtotime(date("Y-m-d",time()).'+'.$result_data[0]['dayLeft'].' day');//结束时间
            $item['keywords'] = implode(',', get_tags($item['title']));//关键词
            $item['description'] = $item['title'].'，现价只需要'.$item['price'].'元，领券后下单还可优惠'.$item['couponAmount'].'元，赶紧抢购吧！';//描述
            $item['content'] = '';//内容
        }

        $query = $GoodsDb->where('numIid','=',$item['numIid'])->select();
        if (!$query) {
            $items[] = $item;
        } else {
            $GoodsDb->where('numIid',$item['numIid'])->update($item);
        }
        if(!empty($items)){
            $result = $GoodsDb->insertAll($items);
        }
        return ['code'=>200];
    }
}