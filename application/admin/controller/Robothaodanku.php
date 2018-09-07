<?php
namespace app\admin\controller;
use think\Controller;
use think\Db;
use app\admin\model\Robothaodanku as RobothaodankuModel;
class Robothaodanku extends Common
{
    
    public function index()
    {
        $RobothaodankuMd = new RobothaodankuModel();
        $RobothaodankuList = $RobothaodankuMd->order('id ASC')->select();
        $this->assign('RobothaodankuList', $RobothaodankuList);
        $today = strtotime(date('Y-m-d',time()));
        $this->assign('Today', $today);
        return view();
    }
    public function add()
    {
        $RobothaodankuMd = new RobothaodankuModel();
        if (request()->isPost()) {
            $data = input('post.');
            $data['sort'] = preg_replace('# #','',$data['sort']);//清除空格
            $data['userType'] = preg_replace('# #','',$data['userType']);
            $data['time'] = time();
            if ($RobothaodankuMd->add($data)) {
                return json(array('code' => 200, 'msg' => '添加成功'));
            } else {
                return json(array('code' => 0, 'msg' => '添加失败'));
            }
        }
        $RobothaodankuList = $RobothaodankuMd->order('id ASC')->select();
        $this->assign('RobothaodankuList', $RobothaodankuList);
        $CategoryDb = Db::name('category');
        $CategoryList = Db::name('category')->select();
        $this->assign('CategoryList', $CategoryList);        
        return view();
    }
    public function edit()
    {
        $RobothaodankuMd = new RobothaodankuModel();
        if (request()->isPost()) {
            $data = input('post.');
            $data['sort'] = preg_replace('# #','',$data['sort']);//清除空格
            $data['userType'] = preg_replace('# #','',$data['userType']);
            if ($RobothaodankuMd->edit($data)) {
                return json(array('code' => 200, 'msg' => '修改成功'));
            } else {
                return json(array('code' => 0, 'msg' => '修改失败'));
            }
        }
        $Robothaodanku = $RobothaodankuMd->find(input('id'));
        $RobothaodankuList = $RobothaodankuMd->order('id ASC')->select();
        $this->assign(array('RobothaodankuList' => $RobothaodankuList, 'Robothaodanku' => $Robothaodanku));
        $CategoryDb = Db::name('category');
        $CategoryList = Db::name('category')->select();
        $this->assign('CategoryList', $CategoryList);        
        return view();
    }
    public function dels()
    {
        $dels = Db::name('robothaodanku')->delete(input('id'));
        if ($dels) {
            return json(array('code' => 200, 'msg' => '删除成功'));
        } else {
            return json(array('code' => 0, 'msg' => '删除失败'));
        }
    }
    public function delss()
    {
        $RobothaodankuMd = new RobothaodankuModel();
        $params = input('post.');
        $ids = implode(',', $params['ids']);
        $result = $RobothaodankuMd->batches('delete', $ids);
        if ($result) {
            return json(array('code' => 200, 'msg' => '批量删除成功'));
        } else {
            return json(array('code' => 0, 'msg' => '批量删除失败'));
        }
    }

    public function collect_all()
    {
        $RobothaodankuMd = new RobothaodankuModel();
        $data = input('post.');
        $info = null;
        if(!isset($data['num'])){
            $data['num'] = 0;
        }
        $today = strtotime(date('Y-m-d',time()));
        if(!isset($data['id'])){
            $info = $RobothaodankuMd->where("lastPage<page or lastTime<=".$today)->order('id asc')->find();
            if(!$info){
                $query = $RobothaodankuMd->order('id ASC')->select();
                foreach ($query as $k => $v) {
                    $RobothaodankuMd->where(['id'=>$v['id']])->update(['lastPage'=>0]); 
                }
                return json(array('code' => 202, 'msg' => '采集已完成，请在商品管理中查看！'));
            }
            if($info['lastTime'] <= $today){//重置页数
                $info['lastPage'] = 0;
            }
        }else{
            $info = $RobothaodankuMd->where(['id'=>$data['id']])->find();
            if($info['lastPage'] >= $info['page'] && date('Y-m-d',$info['lastTime'])  == date('Y-m-d',time())){
                $info = $RobothaodankuMd->where("lastPage<page or lastTime<=".$today)->order('id asc')->find();
                if(!$info){
                    $query = $RobothaodankuMd->order('id ASC')->select();
                    foreach ($query as $k => $v) {
                        $RobothaodankuMd->where(['id'=>$v['id']])->update(['lastPage'=>0]); 
                    }
                    return json(array('code' => 202, 'msg' => '采集已完成，请在商品管理中查看！'));
                }
                if($info['lastTime'] <= $today){//重置页数
                    $info['lastPage'] = 0;
                }
            }
        }
        //没完成，开始采集
        $res = $this->collect_unit($info,120);//设定每页采集条数
        if($res['code'] == 202){
            return json($res);
        }else{
            //更新采集信息
            $RobothaodankuMd->where(['id'=>$info['id']])->update(['lastPage'=>$info['lastPage']+1,'lastTime'=>time()]);
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
        $RobothaodankuMd = new RobothaodankuModel();
        $info = $RobothaodankuMd->where(['id'=>$data['id']])->find();
        if($info['lastPage'] >= $info['page'] && date('Y-m-d',$info['lastTime']) == date('Y-m-d',time())){
            $RobothaodankuMd->where(['id'=>$data['id']])->update(['lastPage'=>0]);
            return json(array('code' => 202, 'msg' => '采集已完成，请在商品管理中查看！'));
        }else{
            if($info['lastPage'] >= $info['page']){
                $info['lastPage']= 0;
            }
            //没完成，开始采集
            $res = $this->collect_unit($info,120);//设定每页采集条数
            if($res['code'] == 202){
                return json($res);
            }else{
                //更新采集信息
                $RobothaodankuMd->where(['id'=>$data['id']])->update(['lastPage'=>$info['lastPage']+1,'lastTime'=>time()]);
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
        $source = get_web_page('http://www.haodanku.com/index/index/nav/6/cid/'.$info['catIds'].'/sort/1'.$info['sort'].'/starttime/30/p/'.$curPage.'.html?json=true&api=list&price_min='.$info['startPrice'].'&price_max='.$info['endPrice'].'&sale_min='.$info['startBiz30day'].'&tkmoney_min='.$info['commission'].'');
        if(!$source){
            return ['code'=>202,'msg'=>'采集程序错误，无法继续'];
        }
        $result_data = json_decode(str_replace('=&gt;', '=>', $source), true);
/*        $result_data = iconv('GBK', 'UTF-8//IGNORE', $result_data);*/        
        if(!$result_data){
            return ['code'=>202,'msg'=>'没有采集数据'];
        }
        //获取数据
        $GoodsDb = Db::name('goods');
        $item = $items =  [];
        $i = 0;
        //设定取前多少条
        if($pageNum){
            $result_data = array_slice($result_data,0,$pageNum);
        }
        foreach($result_data as $k=>$v){
            if(false) {
                /*continue;*/
            }else{
                $item['cid'] = $info['cid'];//分类
                $item['uid'] = session('userid');//发布者ID
                $item['title'] = $v['itemtitle'];//标题
                $item['pic'] = str_replace('https', 'http', $v['itempic']);//图片
                $item['view'] = 1;//浏览量
                $item['numIid'] = $v['itemid'];//商品ID
                $item['price'] = $v['itemprice'];//原价
                $item['couponPrice'] = $v['itemendprice'];//现价
                $item['couponRate'] = round(($item['couponPrice'] / $item['price']) * 10, 1);//折扣
                $item['commissionRate'] = $v['tkrates'];//佣金率                     
                $item['commission'] = $v['tkmoney'];//佣金  
                $item['volume'] = $v['itemsale'];//30天销量
                if ($v['sellernick']) {
                    $item['nick'] = $v['sellernick'];//掌柜旺旺名
                } else {
                    $item['nick'] = 'sellernick';//掌柜旺旺名
                }
                $item['sellerId'] = $v['userid'];//卖家id
                if ($v['couponurl']) {
                    if (!1 !== strpos($v['couponurl'], 'activity_id')) {
                        $item['clickUrl'] = 'https://uland.taobao.com/coupon/edetail?activityId='.get_word($v['couponurl'] . '&', 'activity_id=', '&').'&pid='.config('web.WEB_YHQPID').'&itemId='.$item['numIid'].'&src=cd_cdll';//推广链接
                    }
                    if (!1 !== strpos($v['couponurl'], 'activityId')) {
                        $item['clickUrl'] = 'https://uland.taobao.com/coupon/edetail?activityId='.get_word($v['couponurl'] . '&', 'activityId=', '&').'&pid='.config('web.WEB_YHQPID').'&itemId='.$item['numIid'].'&src=cd_cdll';//推广链接                        
                    }
                }
/*              $item['taoToken'] = $result_api['data']['couponLinkTaoToken'];//淘口令*/
                $item['couponAmount'] = $v['couponmoney'];//优惠卷
                $item['couponTotalcount'] = $v['couponnum'];//优惠券总量
                $item['couponRemaincount'] = $v['couponsurplus'];//优惠券剩余量
                if($v['source'] == 'B') {
                    $item['userType'] = "0";
                } else {
                    $item['userType'] = "1";
                }
                if ($v['tktype'] == '定向计划') {//1代表定向计划
                    $item['dxjhType'] = "1";
                } else {
                    $item['dxjhType'] = "0";
                }
                $item['startTime'] = time();//开始时间
                $item['endTime'] = $v['couponendtime'] + 24 * 3600;//结束时间
                $item['keywords'] = implode(',', get_tags($item['title']));//关键词
                $item['description'] = $v['itemdesc'];//描述
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
