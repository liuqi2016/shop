<?php
namespace app\index\controller;
use think\Controller;
use think\Db;
class WechatSearch extends Controller
{
	public function index()
	{
		if (!input('get.echostr')) {
			$data = file_get_contents('php://input');
			$from_xml = '';
			if (!empty($data)) {
				$cdata = simplexml_load_string($data, 'SimpleXMLElement', LIBXML_NOCDATA);
				$msg_type = trim($cdata->MsgType);
				switch ($msg_type) {
					case 'event':
					if ($cdata->Event == 'subscribe') {
						$text = '<xml>
                        <ToUserName><![CDATA[%s]]></ToUserName>
                        <FromUserName><![CDATA[%s]]></FromUserName>
                        <CreateTime>%s</CreateTime>
                        <MsgType><![CDATA[text]]></MsgType>
                        <Content><![CDATA[%s]]></Content>
                        </xml>';
						$from_xml = sprintf($text, $cdata->FromUserName, $cdata->ToUserName, time().'', config('web.WEB_WXINFO'));
					}
					break;
					case 'text':
					$items = [];
					$keyword = trim($cdata->Content);
					$result_data = $this->GetTaobaoApi($keyword);
					foreach ($result_data as $v) {
						$title = $v['title'];//标题
						$numIid = $v['num_iid'];//商品ID
						$pic = str_replace('https', 'http', $v['pict_url']);//图片
						$couponAmount = get_word($v['coupon_info'], '减', '元');//优惠卷
						$price = $v['zk_final_price'];//原价
						$couponPrice = $price - $couponAmount;//现价
						$volume = $v['volume'];//30天销量
                		$clickUrl = $v['coupon_click_url'];//推广链接
		                if ($v['item_description']) {
		                    $description = $v['item_description'];//描述
		                } else {
		                    $description = $title.'，现价只需要'.$price.'元，领券后下单还可优惠'.$couponAmount.'元，赶紧抢购吧！';//描述
		                }                		
						$click_url = url('taobao/'.$numIid).'?clickUrl='.urlencode($clickUrl).'&title='.urlencode($title).'&pic='.urlencode($pic).'&couponAmount='.$couponAmount.'&price='.$price.'&couponPrice='.$couponPrice.'&volume='.$volume.'&description='.urlencode($description);
						$click_url = config('web.WEB_COM').'/'.ltrim($click_url, '/');
						$items[] = ['Title' => '【优惠券'.$couponAmount.'元】'.$title, 'Description' => '券后价：'.$couponPrice, 'PicUrl' => $pic, 'Url' => $click_url];
					}
					if (is_array($items)) {
						$text = '<item>
            　　　　　　<Title><![CDATA[%s]]></Title>
            　　　　　　<Description><![CDATA[%s]]></Description>
            　　　　　 <PicUrl><![CDATA[%s]]></PicUrl>
            　　　　　　<Url><![CDATA[%s]]></Url>
            　　　　　 </item>
            　　　　　　';
						$item = '';
						foreach ($items as $v) {
							$item .= sprintf($text, $v['Title'], $v['Description'], $v['PicUrl'], $v['Url']);
						}
						$format = '<xml>
                　　　　　　<ToUserName><![CDATA[%s]]></ToUserName>
                　　　　　　<FromUserName><![CDATA[%s]]></FromUserName>
                　　　　　　<CreateTime>%s</CreateTime>
                　　　　　　<MsgType><![CDATA[news]]></MsgType>
                　　　　　 <Content><![CDATA[]]></Content>
                　　　　　 <ArticleCount>%s</ArticleCount>
                　　　　　 <Articles>
                            %s
                　　　　　 </Articles>
                　　　　　 </xml>';
						$from_xml = sprintf($format, $cdata->FromUserName, $cdata->ToUserName, time().'', count($items).'', $item);
					}
					break;
				}
			}
			echo $from_xml;
			exit;
		} else {
			$this->checkWeixinInfo();
		}
	}

	public function checkWeixinInfo()
	{
		$echostr = input('get.echostr');
		if ($this->checkSignature()) {
			echo $echostr;
			exit;
		}
	}

	public function checkSignature()
	{
		$signature = input('get.signature');
		$timestamp = input('get.timestamp');
		$nonce = input('get.nonce');
		$token = config('web.WEB_WXTOKEN');
		$msg = [];
		$msg = [$timestamp, $nonce, $token];
		sort($msg);
		$err_code = sha1(implode($msg));
		if ($err_code == $signature) {
			return !0;
		} else {
			return !1;
		}
	}

	private function GetTaobaoApi($keyword)
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
        $req->setPageSize("7");
        $req->setPageNo('1');
        $req->setQ($keyword);
        $req->setPageNo('1');
        $resp = $c->execute($req);
        $result_data = object_to_array($resp);
        return $result_data['results']['tbk_coupon'];
	}
}