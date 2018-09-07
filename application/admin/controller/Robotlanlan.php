<?php
namespace app\admin\controller;
use think\Controller;
use think\Db;
use app\admin\model\Robotlanlan as RobotlanlanModel;
class Robotlanlan extends Common
{
    
    public function index()
    {
        $RobotlanlanMd = new RobotlanlanModel();
        $RobotlanlanList = $RobotlanlanMd->order('id ASC')->select();
        $this->assign('RobotlanlanList', $RobotlanlanList);
        $today = strtotime(date('Y-m-d',time()));
        $this->assign('Today', $today);
        return view();
    }
    public function add()
    {
        $RobotlanlanMd = new RobotlanlanModel();
        if (request()->isPost()) {
            $data = input('post.');
            $data['sort'] = preg_replace('# #','',$data['sort']);//清除空格
            $data['userType'] = preg_replace('# #','',$data['userType']);
            $data['time'] = time();
            if ($RobotlanlanMd->add($data)) {
                return json(array('code' => 200, 'msg' => '添加成功'));
            } else {
                return json(array('code' => 0, 'msg' => '添加失败'));
            }
        }
        $RobotlanlanList = $RobotlanlanMd->order('id ASC')->select();
        $this->assign('RobotlanlanList', $RobotlanlanList);
        $CategoryDb = Db::name('category');
        $CategoryList = Db::name('category')->select();
        $this->assign('CategoryList', $CategoryList);        
        return view();
    }
    public function edit()
    {
        $RobotlanlanMd = new RobotlanlanModel();
        if (request()->isPost()) {
            $data = input('post.');
            $data['sort'] = preg_replace('# #','',$data['sort']);//清除空格
            $data['userType'] = preg_replace('# #','',$data['userType']);
            if ($RobotlanlanMd->edit($data)) {
                return json(array('code' => 200, 'msg' => '修改成功'));
            } else {
                return json(array('code' => 0, 'msg' => '修改失败'));
            }
        }
        $Robotlanlan = $RobotlanlanMd->find(input('id'));
        $RobotlanlanList = $RobotlanlanMd->order('id ASC')->select();
        $this->assign(array('RobotlanlanList' => $RobotlanlanList, 'Robotlanlan' => $Robotlanlan));
        $CategoryDb = Db::name('category');
        $CategoryList = Db::name('category')->select();
        $this->assign('CategoryList', $CategoryList);        
        return view();
    }
    public function dels()
    {
        $dels = Db::name('robotlanlan')->delete(input('id'));
        if ($dels) {
            return json(array('code' => 200, 'msg' => '删除成功'));
        } else {
            return json(array('code' => 0, 'msg' => '删除失败'));
        }
    }
    public function delss()
    {
        $RobotlanlanMd = new RobotlanlanModel();
        $params = input('post.');
        $ids = implode(',', $params['ids']);
        $result = $RobotlanlanMd->batches('delete', $ids);
        if ($result) {
            return json(array('code' => 200, 'msg' => '批量删除成功'));
        } else {
            return json(array('code' => 0, 'msg' => '批量删除失败'));
        }
    }

    public function collect_all()
    {
        $RobotlanlanMd = new RobotlanlanModel();
        $data = input('post.');
        $info = null;
        if(!isset($data['num'])){
            $data['num'] = 0;
        }
        $today = strtotime(date('Y-m-d',time()));
        if(!isset($data['id'])){
            $info = $RobotlanlanMd->where("lastPage<page or lastTime<=".$today)->order('id asc')->find();
            if(!$info){
                $query = $RobotlanlanMd->order('id ASC')->select();
                foreach ($query as $k => $v) {
                    $RobotlanlanMd->where(['id'=>$v['id']])->update(['lastPage'=>0]); 
                }
                return json(array('code' => 202, 'msg' => '采集已完成，请在商品管理中查看！'));
            }
            if($info['lastTime'] <= $today){//重置页数
                $info['lastPage'] = 0;
            }
        }else{
            $info = $RobotlanlanMd->where(['id'=>$data['id']])->find();
            if($info['lastPage'] >= $info['page'] && date('Y-m-d',$info['lastTime'])  == date('Y-m-d',time())){
                $info = $RobotlanlanMd->where("lastPage<page or lastTime<=".$today)->order('id asc')->find();
                if(!$info){
                    $query = $RobotlanlanMd->order('id ASC')->select();
                    foreach ($query as $k => $v) {
                        $RobotlanlanMd->where(['id'=>$v['id']])->update(['lastPage'=>0]); 
                    }
                    return json(array('code' => 202, 'msg' => '采集已完成，请在商品管理中查看！'));
                }
                if($info['lastTime'] <= $today){//重置页数
                    $info['lastPage'] = 0;
                }
            }
        }
        //没完成，开始采集
        $res = $this->collect_unit($info,80);//设定每页采集条数
        if($res['code'] == 202){
            return json($res);
        }else{
            //更新采集信息
            $RobotlanlanMd->where(['id'=>$info['id']])->update(['lastPage'=>$info['lastPage']+1,'lastTime'=>time()]);
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
        $RobotlanlanMd = new RobotlanlanModel();
        $info = $RobotlanlanMd->where(['id'=>$data['id']])->find();
        if($info['lastPage'] >= $info['page'] && date('Y-m-d',$info['lastTime']) == date('Y-m-d',time())){
            $RobotlanlanMd->where(['id'=>$data['id']])->update(['lastPage'=>0]);
            return json(array('code' => 202, 'msg' => '采集已完成，请在商品管理中查看！'));
        }else{
            if($info['lastPage'] >= $info['page']){
                $info['lastPage']= 0;
            }
            //没完成，开始采集
            $res = $this->collect_unit($info,80);//设定每页采集条数
            if($res['code'] == 202){
                return json($res);
            }else{
                //更新采集信息
                $RobotlanlanMd->where(['id'=>$data['id']])->update(['lastPage'=>$info['lastPage']+1,'lastTime'=>time()]);
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
        $source = get_web_page('http://www.lanlanlife.com/taoke/item/list?time=all&startDate=&endDate=&startHour=&endHour=&shopRank='.$info['userType'].'&dsr=all&jpdp=0&xfzbz=0&maxPrice='.$info['endPrice'].'&minPrice='.$info['startPrice'].'&maxTkPrice=&minTkPrice=&maxTkRate='.$info['endTkRate'].'&minTkRate='.$info['startTkRate'].'&spyxl=&maxqzkb=&minqzkb=&qzkb=&baoyou=0&deliveryIns=0&pinpai=0&minUsedSpeed=&maxUsedSpeed=&maxCountDown=&minAmount='.$info['startCouponAmount'].'&maxAmount='.$info['endCouponAmount'].'&minAvailableCount=&maxAvailableCount=&keywords=&pCategoryId=&cCategoryName=&fCategory='.$info['catIds'].'&sort='.$info['sort'].'&qun=all&source_all=all&source_kind=all&jutao=0&brand=all&maxYxe=&minYxe=&&monthSales2h='.$info['startBiz30day'].'page='.$curPage.'&type=index');   
        if(!$source){
            return ['code'=>202,'msg'=>'采集程序错误，无法继续'];
        }
        $result_data = json_decode($source, true);
        if(!$result_data){
            return ['code'=>202,'msg'=>'没有采集数据'];
        }
        //获取数据
        $GoodsDb = Db::name('goods');
        $item = $items =  [];
        $i = 0;
        //设定取前多少条
        $result_data = $result_data['result']['vm']['list'];
        if($pageNum){
            $result_data = array_slice($result_data,0,$pageNum);
        }
        foreach($result_data as $k=>$v){
            if(false) {
                /*continue;*/
            }else{
                $item['cid'] = $info['cid'];//分类
                $item['uid'] = session('userid');//发布者ID
                $item['title'] = $v['itemTitle'];//标题
                $item['pic'] = str_replace('https', 'http', $v['coverImage']);//图片
                $item['view'] = 1;//浏览量
                $item['numIid'] = $v['itemId'];//商品ID
                $item['price'] = str_replace('¥', '', $v['originPrice']);//原价
                $item['couponPrice'] = str_replace('¥', '', $v['price']);//现价
                $item['couponRate'] = $v['discount'];//折扣
                $item['commissionRate'] = str_replace('%', '', $v['tkRate']);//佣金率                     
                $item['commission'] = str_replace('¥', '', $v['tkPrice']);//佣金  
                $item['volume'] = $v['monthSales'];//30天销量
                $item['nick'] = $v['shopName'];//掌柜旺旺名
                $item['sellerId'] = $v['sellerId'];//卖家id
                $item['clickUrl'] = 'https://uland.taobao.com/coupon/edetail?activityId='.$v['activityId'].'&pid='.config('web.WEB_YHQPID').'&itemId='.$item['numIid'].'&src=cd_cdll';//推广链接
/*              $item['taoToken'] = $result_api['data']['couponLinkTaoToken'];//淘口令*/
                $item['couponAmount'] = $v['amount'];//优惠卷
                $item['couponTotalcount'] = $v['totalCount'];//优惠券总量
                $item['couponRemaincount'] = $v['surplus'];//优惠券剩余量
                if($v['source'] == 'taobao') {
                    $item['userType'] = "0";
                } else {
                    $item['userType'] = "1";
                }
                if ($v['rateType'] == 2) {//1代表定向计划
                    $item['dxjhType'] = "1";
                } else {
                    $item['dxjhType'] = "0";
                }
                $item['startTime'] = time();//开始时间
                $item['endTime'] = strtotime($v['endTime']."+1 day");//结束时间
                $item['keywords'] = implode(',', get_tags($item['title']));//关键词
                $item['description'] = $v['recommend'];//描述
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
