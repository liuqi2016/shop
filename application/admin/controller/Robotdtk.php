<?php
namespace app\admin\controller;
use think\Controller;
use think\Db;
use app\admin\model\Robotdtk as RobotdtkModel;
class Robotdtk extends Common
{
    
    public function index()
    {
        $RobotdtkMd = new RobotdtkModel();
        $RobotdtkList = $RobotdtkMd->order('id ASC')->select();
        $this->assign('RobotdtkList', $RobotdtkList);
        return view();
    }
    public function add()
    {
        $RobotdtkMd = new RobotdtkModel();
        if (request()->isPost()) {
            $data = input('post.');
            $data['time'] = time();
            if ($RobotdtkMd->add($data)) {
                return json(array('code' => 200, 'msg' => '添加成功'));
            } else {
                return json(array('code' => 0, 'msg' => '添加失败'));
            }
        }
        $RobotdtkList = $RobotdtkMd->order('id ASC')->select();
        $this->assign('RobotdtkList', $RobotdtkList);
        $CategoryDb = Db::name('category');
        $CategoryList = Db::name('category')->select();
        $this->assign('CategoryList', $CategoryList);        
        return view();
    }
    public function edit()
    {
        $RobotdtkMd = new RobotdtkModel();
        if (request()->isPost()) {
            $data = input('post.');
            if ($RobotdtkMd->edit($data)) {
                return json(array('code' => 200, 'msg' => '修改成功'));
            } else {
                return json(array('code' => 0, 'msg' => '修改失败'));
            }
        }
        $Robotdtk = $RobotdtkMd->find(input('id'));
        $RobotdtkList = $RobotdtkMd->order('id ASC')->select();
        $this->assign(array('RobotdtkList' => $RobotdtkList, 'Robotdtk' => $Robotdtk));
        $CategoryDb = Db::name('category');
        $CategoryList = Db::name('category')->select();
        $this->assign('CategoryList', $CategoryList);        
        return view();
    }
    public function dels()
    {
        $dels = Db::name('robotdtk')->delete(input('id'));
        if ($dels) {
            return json(array('code' => 200, 'msg' => '删除成功'));
        } else {
            return json(array('code' => 0, 'msg' => '删除失败'));
        }
    }
    public function delss()
    {
        $RobotdtkMd = new RobotdtkModel();
        $params = input('post.');
        $ids = implode(',', $params['ids']);
        $result = $RobotdtkMd->batches('delete', $ids);
        if ($result) {
            return json(array('code' => 200, 'msg' => '批量删除成功'));
        } else {
            return json(array('code' => 0, 'msg' => '批量删除失败'));
        }
    }

    public function collect()
    {
        //获取采集信息
        $data = input('post.');
        if(!isset($data['num'])){
            $data['num'] = 0;
        }
        if (session('curPage')) {
            $curPage = session('curPage'); 
        } else {
            $curPage = 0;
        }
        if($curPage >= 20){//设定采集页数
            session('curPage',0);
            return json(array('code' => 202, 'msg' => '采集已完成，请在商品管理中查看！'));
        }else{
            //开始采集
            $res = $this->collect_unit($curPage+1,50);//设定每页采集条数
            if($res['code'] == 202){
                return json($res); 
            }else{
                //更新采集信息
                session('curPage',$curPage+1);
                $res['num'] = $data['num']+$res['num'];
                return json(array('code' => 200, 'msg' => '已采集' . ($curPage+1) . '页,目前共采集到商品' . $res['num'] .'件。','num'=>$res['num']));
            }
        }
    }

    //采集程序
    private function collect_unit($curPage,$pageNum=0)
    {
        $source = get_web_page('http://api.dataoke.com/index.php?r=Port/index&type=total&appkey='. config('web.WEB_DTKKEY') .'&v=2&page='. $curPage);
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
        $result_data = $result_data['result'];
        if($pageNum){
            $result_data = array_slice($result_data,0,$pageNum);
        }

        foreach($result_data as $k=>$v){
            //这里添加过滤机制
            if(false){
                /*continue;*/
            }else{
                $item['cid'] = Db::name('robotdtk')->where(['dtkcid'=>$v['Cid']])->find()['cid'];//分类
                $item['uid'] = session('userid');//发布者ID
                $item['title'] = $v['Title'];//标题
                $item['pic'] = str_replace('https', 'http', $v['Pic']);//图片
                $item['view'] = 1;//浏览量
                $item['numIid'] = $v['GoodsID'];//商品ID
                $item['price'] = $v['Org_Price'];//原价
                $item['couponPrice'] = $v['Price'];//现价
                $item['couponRate'] = round(($item['couponPrice'] / $item['price']) * 10, 1);//折扣
                $item['commissionRate'] = $v['Commission'];//佣金率                     
                $item['commission'] = $item['price'] * ($item['commissionRate'] / 100);//佣金          
                $item['volume'] = $v['Sales_num'];//30天销量
                $item['nick'] = $v['SellerID'];//掌柜旺旺名
                $item['sellerId'] = $v['SellerID'];//卖家id
                $item['clickUrl'] = 'https://uland.taobao.com/coupon/edetail?activityId='.$v['Quan_id'].'&pid='.config('web.WEB_YHQPID').'&itemId='.$item['numIid'].'&src=cd_cdll';//推广链接
                $item['taoToken'] = '';//淘口令
                $item['couponAmount'] = $v['Quan_price'];//优惠卷
                $item['couponTotalcount'] = $v['Quan_surplus'];//优惠券总量
                $item['couponRemaincount'] = $v['Quan_surplus'] - $v['Quan_condition'];//优惠券剩余量
                if($v['IsTmall'] == 1) {
                    $item['userType'] = "0";
                } else {
                    $item['userType'] = "1";
                }
                if ($v['Jihua_link'] == '') {
                    $item['dxjhType'] = "0";//定向计划
                } else {
                    $item['dxjhType'] = "1";
                }                
                $item['startTime'] = time();//开始时间
                $item['endTime'] = strtotime($v['Quan_time']);//结束时间
                $item['keywords'] = implode(',', get_tags($item['title']));//关键词
                if ($v['Introduce']) {
                    $item['description'] = $v['Introduce'];//描述
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
