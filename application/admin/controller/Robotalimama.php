<?php
namespace app\admin\controller;
use think\Controller;
use think\Db;
use app\admin\model\Robotalimama as RobotalimamaModel;
class Robotalimama extends Common
{
    
    public function index()
    {
        $RobotalimamaMd = new RobotalimamaModel();
        $RobotalimamaList = $RobotalimamaMd->order('id ASC')->select();
        $this->assign('RobotalimamaList', $RobotalimamaList);
        $today = strtotime(date('Y-m-d',time()));
        $this->assign('Today', $today);
        return view();
    }
    public function add()
    {
        $RobotalimamaMd = new RobotalimamaModel();
        if (request()->isPost()) {
            $data = input('post.');
            $data['sort'] = preg_replace('# #','',$data['sort']);//清除空格
            $data['userType'] = preg_replace('# #','',$data['userType']);
            $data['time'] = time();
            if ($RobotalimamaMd->add($data)) {
                return json(array('code' => 200, 'msg' => '添加成功'));
            } else {
                return json(array('code' => 0, 'msg' => '添加失败'));
            }
        }
        $RobotalimamaList = $RobotalimamaMd->order('id ASC')->select();
        $this->assign('RobotalimamaList', $RobotalimamaList);
        $CategoryDb = Db::name('category');
        $CategoryList = Db::name('category')->select();
        $this->assign('CategoryList', $CategoryList);        
        return view();
    }
    public function edit()
    {
        $RobotalimamaMd = new RobotalimamaModel();
        if (request()->isPost()) {
            $data = input('post.');
            $data['sort'] = preg_replace('# #','',$data['sort']);//清除空格
            $data['userType'] = preg_replace('# #','',$data['userType']);
            if ($RobotalimamaMd->edit($data)) {
                return json(array('code' => 200, 'msg' => '修改成功'));
            } else {
                return json(array('code' => 0, 'msg' => '修改失败'));
            }
        }
        $Robotalimama = $RobotalimamaMd->find(input('id'));
        $RobotalimamaList = $RobotalimamaMd->order('id ASC')->select();
        $this->assign(array('RobotalimamaList' => $RobotalimamaList, 'Robotalimama' => $Robotalimama));
        $CategoryDb = Db::name('category');
        $CategoryList = Db::name('category')->select();
        $this->assign('CategoryList', $CategoryList);        
        return view();
    }
    public function dels()
    {
        $dels = Db::name('robotalimama')->delete(input('id'));
        if ($dels) {
            return json(array('code' => 200, 'msg' => '删除成功'));
        } else {
            return json(array('code' => 0, 'msg' => '删除失败'));
        }
    }
    public function delss()
    {
        $RobotalimamaMd = new RobotalimamaModel();
        $params = input('post.');
        $ids = implode(',', $params['ids']);
        $result = $RobotalimamaMd->batches('delete', $ids);
        if ($result) {
            return json(array('code' => 200, 'msg' => '批量删除成功'));
        } else {
            return json(array('code' => 0, 'msg' => '批量删除失败'));
        }
    }

    public function collect_all()
    {
        $RobotalimamaMd = new RobotalimamaModel();
        $data = input('post.');
        $info = null;
        if(!isset($data['num'])){
            $data['num'] = 0;
        }
        $today = strtotime(date('Y-m-d',time()));
        if(!isset($data['id'])){
            $info = $RobotalimamaMd->where("lastPage<page or lastTime<=".$today)->order('id asc')->find();
            if(!$info){
                $query = $RobotalimamaMd->order('id ASC')->select();
                foreach ($query as $k => $v) {
                    $RobotalimamaMd->where(['id'=>$v['id']])->update(['lastPage'=>0]); 
                }
                return json(array('code' => 202, 'msg' => '采集已完成，请在商品管理中查看！'));
            }
            if($info['lastTime'] <= $today){//重置页数
                $info['lastPage'] = 0;
            }
        }else{
            $info = $RobotalimamaMd->where(['id'=>$data['id']])->find();
            if($info['lastPage'] >= $info['page'] && date('Y-m-d',$info['lastTime'])  == date('Y-m-d',time())){
                $info = $RobotalimamaMd->where("lastPage<page or lastTime<=".$today)->order('id asc')->find();
                if(!$info){
                    $query = $RobotalimamaMd->order('id ASC')->select();
                    foreach ($query as $k => $v) {
                        $RobotalimamaMd->where(['id'=>$v['id']])->update(['lastPage'=>0]); 
                    }
                    return json(array('code' => 202, 'msg' => '采集已完成，请在商品管理中查看！'));
                }
                if($info['lastTime'] <= $today){//重置页数
                    $info['lastPage'] = 0;
                }
            }
        }
        //没完成，开始采集
        $res = $this->collect_unit($info,40);//设定每页采集条数
        if($res['code'] == 202){
            return json($res);
        }else{
            //更新采集信息
            $RobotalimamaMd->where(['id'=>$info['id']])->update(['lastPage'=>$info['lastPage']+1,'lastTime'=>time()]);
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
        $RobotalimamaMd = new RobotalimamaModel();
        $info = $RobotalimamaMd->where(['id'=>$data['id']])->find();
        if($info['lastPage'] >= $info['page'] && date('Y-m-d',$info['lastTime']) == date('Y-m-d',time())){
            $RobotalimamaMd->where(['id'=>$data['id']])->update(['lastPage'=>0]);
            return json(array('code' => 202, 'msg' => '采集已完成，请在商品管理中查看！'));
        }else{
            if($info['lastPage'] >= $info['page']){
                $info['lastPage']= 0;
            }
            //没完成，开始采集
            $res = $this->collect_unit($info,40);//设定每页采集条数
            if($res['code'] == 202){
                return json($res);
            }else{
                //更新采集信息
                $RobotalimamaMd->where(['id'=>$data['id']])->update(['lastPage'=>$info['lastPage']+1,'lastTime'=>time()]);
                $res['num'] = $data['num']+$res['num'];
                return json(array('code' => 200, 'msg' => '已采集' . ($info['lastPage']+1) . '页,目前共采集到商品' . $res['num'] .'件。','num'=>$res['num']));
            }
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
     * @param $curPage
     * @param int $pageNum 每页采集个数
     * @return array
     * @throws \think\Exception
     */
    private function collect_unit($info,$pageNum=0)
    {
        $curPage = $info['lastPage']+1;
        if ($info['userType'] == '') {
            $shopTag = "dpyhq";
        } else {
            $shopTag = "b2c%2Cdpyhq";
        }
        $source = get_web_page('http://pub.alimama.com/items/search.json?q='.$info['keyword'].'&catIds='.$info['catIds'].'&queryType=0&sortType='.$info['sort'].'&freeShipment='.$info['freeShipment'].'&startPrice='.$info['startPrice'].'&endPrice='.$info['endPrice'].'&startTkRate='.$info['startTkRate'].'&endTkRate='.$info['endTkRate'].'&startBiz30day='.$info['startBiz30day'].'&npxType='.$info['npxType'].'&picQuality='.$info['picQuality'].'&dpyhq=1&shopTag='.$shopTag.'&perPageSize=40&toPage='.$curPage.'&t=1501309040486&_tb_token_=nqj1LGFZ9p');
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
        $item = $items =  [];
        $i = 0;
        //设定取前多少条
        $result_data = $result_data['data']['pageList'];
        if($pageNum){
            $result_data = array_slice($result_data,0,$pageNum);
        }
        foreach($result_data as $k=>$v){
            //阿里妈妈缓存处理
            $cookie = get_cookie(config('web.WEB_MMCOOKIE'));
            $ip = request()->ip();
            $microtime = microtime(true) * 1000;
            $microtime = explode('.', $microtime);
            $api_url = 'http://pub.alimama.com/common/code/getAuctionCode.json?auctionid=' . $v['auctionId'] . '&adzoneid=' . get_adzoneid(config('web.WEB_YHQPID')) . '&siteid=' . get_siteid(config('web.WEB_YHQPID')) . '&scenes=1&t='. $microtime[0] .'&_tb_token_='. $cookie['tb_token'] .'&pvid=10_'. $ip .'_314_'. $microtime[0] .'';
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $api_url);
            curl_setopt($ch, CURLOPT_REFERER, 'http://www.alimama.com/index.htm');
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Cookie:{' . $cookie['HTTPHEADER'] . '}'));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);  
/*            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);*/
            curl_setopt($ch, CURLOPT_MAXREDIRS, 2);
            $source = curl_exec($ch);
            curl_close($ch);
            $result_api = json_decode($source, true);
            if(!$result_api) {
                /*continue;*/
            }else{
                $item['cid'] = $info['cid'];//分类
                $item['uid'] = session('userid');//发布者ID
                $item['title'] = str_replace("</span>", "", str_replace("<span class=H>", "", $v['title']));//标题
                $item['pic'] = str_replace('https', 'http', $v['pictUrl']);//图片
                $item['view'] = 1;//浏览量
                $item['numIid'] = $v['auctionId'];//商品ID
                $item['price'] = $v['zkPrice'];//原价
                $item['couponPrice'] = $item['price'] - $v['couponAmount'];//现价
                $item['couponRate'] = round(($item['couponPrice'] / $item['price']) * 10, 1);//折扣
                $item['commissionRate'] = $v['tkRate'];//佣金率                     
                $item['commission'] = round($item['price'] * ($item['commissionRate'] / 100), 1);//佣金  
                $item['volume'] = $v['biz30day'];//30天销量
                $item['nick'] = $v['nick'];//掌柜旺旺名
                $item['sellerId'] = $v['sellerId'];//卖家id
                $item['clickUrl'] = $result_api['data']['couponLink'];//推广链接
                $item['taoToken'] = $result_api['data']['couponLinkTaoToken'];//淘口令
                $item['couponAmount'] = $v['couponAmount'];//优惠卷
                $item['couponTotalcount'] = '';//优惠券总量
                $item['couponRemaincount'] = '';//优惠券剩余量
                if($v['userType'] == 0) {
                    $item['userType'] = "0";
                } else {
                    $item['userType'] = "1";
                }
                if ($v['includeDxjh'] == 1) {//1代表定向计划
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
