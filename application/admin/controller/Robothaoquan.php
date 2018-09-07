<?php
namespace app\admin\controller;
use think\Controller;
use think\Db;
use app\admin\model\Robothaoquan as RobothaoquanModel;
class Robothaoquan extends Common
{
    
    public function index()
    {
        $RobothaoquanMd = new RobothaoquanModel();
        $RobothaoquanList = $RobothaoquanMd->order('id ASC')->select();
        $this->assign('RobothaoquanList', $RobothaoquanList);
        $today = strtotime(date('Y-m-d',time()));
        $this->assign('Today', $today);        
        return view();
    }
    public function add()
    {
        $RobothaoquanMd = new RobothaoquanModel();
        if (request()->isPost()) {
            $data = input('post.');
            $data['time'] = time();
            if ($RobothaoquanMd->add($data)) {
                return json(array('code' => 200, 'msg' => '添加成功'));
            } else {
                return json(array('code' => 0, 'msg' => '添加失败'));
            }
        }
        $RobothaoquanList = $RobothaoquanMd->order('id ASC')->select();
        $this->assign('RobothaoquanList', $RobothaoquanList);
        $CategoryDb = Db::name('category');
        $CategoryList = Db::name('category')->select();
        $this->assign('CategoryList', $CategoryList);        
        return view();
    }
    public function edit()
    {
        $RobothaoquanMd = new RobothaoquanModel();
        if (request()->isPost()) {
            $data = input('post.');
            if ($RobothaoquanMd->edit($data)) {
                return json(array('code' => 200, 'msg' => '修改成功'));
            } else {
                return json(array('code' => 0, 'msg' => '修改失败'));
            }
        }
        $Robothaoquan = $RobothaoquanMd->find(input('id'));
        $RobothaoquanList = $RobothaoquanMd->order('id ASC')->select();
        $this->assign(array('RobothaoquanList' => $RobothaoquanList, 'Robothaoquan' => $Robothaoquan));
        $CategoryDb = Db::name('category');
        $CategoryList = Db::name('category')->select();
        $this->assign('CategoryList', $CategoryList);        
        return view();
    }
    public function dels()
    {
        $dels = Db::name('robothaoquan')->delete(input('id'));
        if ($dels) {
            return json(array('code' => 200, 'msg' => '删除成功'));
        } else {
            return json(array('code' => 0, 'msg' => '删除失败'));
        }
    }
    public function delss()
    {
        $RobothaoquanMd = new RobothaoquanModel();
        $params = input('post.');
        $ids = implode(',', $params['ids']);
        $result = $RobothaoquanMd->batches('delete', $ids);
        if ($result) {
            return json(array('code' => 200, 'msg' => '批量删除成功'));
        } else {
            return json(array('code' => 0, 'msg' => '批量删除失败'));
        }
    }

    public function collect_all()
    {
        $RobothaoquanMd = new RobothaoquanModel();
        $data = input('post.');
        $info = null;
        if(!isset($data['num'])){
            $data['num'] = 0;
        }
        $today = strtotime(date('Y-m-d',time()));
        if(!isset($data['id'])){
            $info = $RobothaoquanMd->where("lastPage<page or lastTime<=".$today)->order('id asc')->find();
            if(!$info){
                $query = $RobothaoquanMd->order('id ASC')->select();
                foreach ($query as $k => $v) {
                    $RobothaoquanMd->where(['id'=>$v['id']])->update(['lastPage'=>0]); 
                }                
                return json(array('code' => 202, 'msg' => '采集已完成，请在商品管理中查看！'));
            }
            if($info['lastTime'] <= $today){
                $info['lastPage'] = 0;//重置页数
            }
        }else{
            $info = $RobothaoquanMd->where(['id'=>$data['id']])->find();
            if($info['lastPage'] >= $info['page'] && date('Y-m-d',$info['lastTime'])  == date('Y-m-d',time())){
                $info = $RobothaoquanMd->where("lastPage<page or lastTime<=".$today)->order('id asc')->find();
                if(!$info){
                    $query = $RobothaoquanMd->order('id ASC')->select();
                    foreach ($query as $k => $v) {
                        $RobothaoquanMd->where(['id'=>$v['id']])->update(['lastPage'=>0]); 
                    }                    
                    return json(array('code' => 202, 'msg' => '采集已完成，请在商品管理中查看！'));
                }
                if($info['lastTime'] <= $today){
                    $info['lastPage'] = 0; //重置页数
                }
            }
        }
        //没完成，开始采集
        $res = $this->collect_unit($info,100);//设定每页采集条数
        if($res['code'] == 202){
            return json($res);
        }else{
            //更新采集信息
            $RobothaoquanMd->where(['id'=>$info['id']])->update(['lastPage'=>$info['lastPage']+1,'lastTime'=>time()]);
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
        $RobothaoquanMd = new RobothaoquanModel();
        $info = $RobothaoquanMd->where(['id'=>$data['id']])->find();
        if($info['lastPage'] >= $info['page'] && date('Y-m-d',$info['lastTime']) == date('Y-m-d',time())){
            $RobothaoquanMd->where(['id'=>$data['id']])->update(['lastPage'=>0]);
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
                $RobothaoquanMd->where(['id'=>$data['id']])->update(['lastPage'=>$info['lastPage']+1,'lastTime'=>time()]);
                $res['num'] = $data['num']+$res['num'];
                return json(array('code' => 200, 'msg' => '已采集' . ($info['lastPage']+1) . '页,目前共采集到商品' . $res['num'] .'件。','num'=>$res['num']));
            }
        }
    }

    //采集程序
    private function collect_unit($info,$pageNum=0)
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
        $req->setCat('');
        $req->setPageSize("100");
        $req->setQ($info['keyword']);
        $req->setPageNo($info['lastPage']+1);
        $resp = $c->execute($req);
        $resp = object_to_array($resp);
        /*halt($resp); //打印数据*/
        if(isset($resp['code'])){
            return ['code'=>202,'msg'=>'采集器接口错误：'.$resp['msg']];
        }

        if(!isset($resp['results'])){
            return ['code'=>202,'msg'=>'采集器接口错'];
        }

        $result_data = $resp['results']['tbk_coupon'];
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
