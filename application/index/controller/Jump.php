<?php
namespace app\index\controller;
use think\Controller;
use think\Db;
class Jump extends Controller
{
    public function index() 
    {
        $id = input('id', '');
        $GoodsDb = Db::name('goods');
        if (empty($id)) {
            return $this->error('亲！你迷路了');
        } else {
            $Goods = $GoodsDb->alias('G')->join('category C', 'C.id=G.cid')->join('member M', 'M.userid=G.uid')->field('G.*,C.id as cid,C.name,M.userid,M.grades,M.point,M.userhead,M.username')->find($id);
            $this->assign('Goods', $Goods);
        }
        $tdj = config('web.WEB_TDJ');
        if (strpos($tdj, 'text/javascript')) {
            $pid = get_word($tdj, 'pid: "', '"');
        } else {
            $pid = $tdj;
        }
        $this->assign('pid', $pid);
        return view();
    }

    public function taobao() 
    {
        $Goods['numIid'] = input('id');//商品ID
        $Goods['title'] = input('title');//标题
        $Goods['pic'] = input('pic');//图片
        $Goods['couponAmount'] = input('couponAmount');//优惠卷
        $Goods['couponPrice'] = input('couponPrice');//现价
        $Goods['clickUrl'] = input('clickUrl');//推广链接
        if (empty($Goods['numIid'])) {
            return $this->error('亲！你迷路了');
        } else {
            $this->assign('Goods', $Goods);
        }
        $tdj = config('web.WEB_TDJ');
        if (strpos($tdj, 'text/javascript')) {
            $pid = get_word($tdj, 'pid: "', '"');
        } else {
            $pid = $tdj;
        }
        $this->assign('pid', $pid);
        return view();
    }        
}