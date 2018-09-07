<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------
error_reporting(E_ERROR | E_WARNING | E_PARSE);//禁止空数组报错

// 应用公共文件
function newicon($time)
{
    $date = '';
    if (date('Y-m-d') == date('Y-m-d',$time)){
        $date = '<span class="new-icon">新品</span>';
    }
    return $date;
}

function get_tags($title, $num=5)
{
    require_once VENDOR_PATH . 'pscws4/pscws4.class.php';
    $pscws = new PSCWS4();
    $pscws->set_dict(VENDOR_PATH . 'pscws4/scws/dict.utf8.xdb');
    $pscws->set_rule(VENDOR_PATH . 'pscws4/scws/rules.utf8.ini');
    $pscws->set_ignore(true);
    $pscws->send_text($title);
    $words = $pscws->get_tops($num);
    $pscws->close();
    $tags = array();
    foreach ($words as $val) {
        $tags[] = $val['word'];
    }
    return $tags;
}

function get_word($html,$star,$end)
{
    $word = 0;
    $pat = '/'.$star.'(.*?)'.$end.'/s';
    if(!preg_match_all($pat, $html, $mat)) {                
    }else{
        $word = $mat[1][0];
    }
    return $word;
}

function get_adzoneid($pid)
{
    $adzoneid = substr($pid,strrpos($pid,"_")+1);
/*    $pid = explode('_', $pid);
    $adzoneid = $pid[3];*/
    return $adzoneid;
}

function get_siteid($pid)
{
    $pid = str_replace('mm_', '', $pid);
    $firstNum = strpos($pid,"_")+1;
    $lastNum = strrpos($pid,"_");
    $siteid = substr($pid, $firstNum, $lastNum - $firstNum);
    return $siteid;
}

function object_to_array($obj)
{
    $obj = (array) $obj;
    foreach ($obj as $key => $val) {
        if (gettype($val) == 'resource') {
            return;
        }
        if (gettype($val) == 'object' || gettype($val) == 'array') {
            $obj[$key] = (array) object_to_array($val);
        }
    }
    return $obj;
}

function get_cookie($cookie)
{
    $search = array(' ', '　', '' . "\n" . '', '' . "\r" . '', '' . "\t" . '');
    $replace = array("", "", "", "", "");        
    $cookie = str_replace($search, $replace, $cookie);
    $cookie = $cookie . ';';
    $tb_token = get_word($cookie, '_tb_token_=', ';');
    $t = get_word($cookie, 't=', ';');
    $cna = get_word($cookie, 'cna=', ';');
    $l = get_word($cookie, 'l=', ';');
    $isg = get_word($cookie, 'isg=', ';');
    $guidance = get_word($cookie, 'mm-guidance3', ';');
    $umdata = get_word($cookie, '_umdata=', ';');
    $cookie2 = get_word($cookie, 'cookie2=', ';');
    $cookie32 = get_word($cookie, 'cookie32=', ';');
    $cookie31 = get_word($cookie, 'cookie31=', ';');
    $alimamapwag = get_word($cookie, 'alimamapwag=', ';');
    $login = get_word($cookie, 'login=', ';');
    $alimamapw = get_word($cookie, 'alimamapw=', ';');
    $HTTPHEADER = 't=' . $t . ';cna=' . $cna . ';l=' . $l . ';isg=' . $isg . ';mm-guidance3=' . $guidance . ';_umdata=' . $umdata . ';cookie2=' . $cookie2 . ';_tb_token_=' . $tb_token . ';v=0;cookie32=' . $cookie32 . ';cookie31=' . $cookie31 . ';alimamapwag=' . $alimamapwag . ';login=' . $login . ';alimamapw=' . $alimamapw;
    return ['tb_token'=>$tb_token,'HTTPHEADER'=>$HTTPHEADER];
}

function get_web_page($url)
{
    $cookie_file = dirname(__FILE__)."/cookie.txt";
    $options = array(
        CURLOPT_RETURNTRANSFER => true,     //返回网页
        CURLOPT_HEADER         => false,    //不返回头信息
/*        CURLOPT_FOLLOWLOCATION => true,     //抓取重定向*/
        CURLOPT_ENCODING       => "gzip,deflate",       //处理编码
        CURLOPT_SSL_VERIFYPEER => 0,     //验证对等证书
        CURLOPT_SSL_VERIFYHOST => 1,     //检查服务器SSL证书
        CURLOPT_USERAGENT      => "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.57 Safari/537.17 SE 2.X MetaSr 1.0", // 设置UserAgent
        CURLOPT_COOKIEJAR      => $cookie_file,      //储存Cookie信息
        CURLOPT_COOKIEFILE     => $cookie_file,     //读取上面所储存的Cookie信息
        CURLOPT_AUTOREFERER    => true,     //引用页重定向
        CURLOPT_CONNECTTIMEOUT => 120,      //连接超时
        CURLOPT_TIMEOUT        => 120,      //回复超时
        CURLOPT_MAXREDIRS      => 10,       //最多的HTTP重定向的数量
    );
    $ch = curl_init($url);
    curl_setopt_array($ch,$options);
    $content = curl_exec($ch);
    curl_close($ch);
    return $content;
}

function post_web_page($url,$data='')
{
    $cookie_file = dirname(__FILE__)."/cookie.txt";
    $options = array(
        CURLOPT_RETURNTRANSFER => true,     //返回网页
        CURLOPT_HEADER         => false,    //不返回头信息
/*        CURLOPT_FOLLOWLOCATION => true,     //抓取重定向*/
        CURLOPT_ENCODING       => "gzip,deflate",       //处理编码
        CURLOPT_SSL_VERIFYPEER => 0,     //验证对等证书
        CURLOPT_SSL_VERIFYHOST => 1,     //检查服务器SSL证书        
        CURLOPT_USERAGENT      => "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.57 Safari/537.17 SE 2.X MetaSr 1.0", // 设置UserAgent
        CURLOPT_POSTFIELDS     => $data,    //模拟POST
        CURLOPT_COOKIEJAR      => $cookie_file,      //储存Cookie信息
        CURLOPT_COOKIEFILE     => $cookie_file,     //读取上面所储存的Cookie信息
        CURLOPT_AUTOREFERER    => true,     //引用页重定向
        CURLOPT_CONNECTTIMEOUT => 120,      //连接超时
        CURLOPT_TIMEOUT        => 120,      //回复超时
        CURLOPT_MAXREDIRS      => 10,       //最多的HTTP重定向的数量
    );
    $ch = curl_init($url);
    curl_setopt_array($ch,$options);
    $content = curl_exec($ch);
    curl_close($ch);
    return $content;
}

function check_url($url)
{
    if (!preg_match('/http:\/\/[\w.]+[\w\/]*[\w.]*\??[\w=&\+\%]*/is',$url)  && !preg_match('/https:\/\/[\w.]+[\w\/]*[\w.]*\??[\w=&\+\%]*/is',$url)) {
        return false;
    }
    return true;
}

function isMobile()
{ 
    // 如果有HTTP_X_WAP_PROFILE则一定是移动设备
    if (isset ($_SERVER['HTTP_X_WAP_PROFILE'])){
        return true;
    } 
    // 如果via信息含有wap则一定是移动设备,部分服务商会屏蔽该信息
    if (isset ($_SERVER['HTTP_VIA'])){ 
        // 找不到为flase,否则为true
        return stristr($_SERVER['HTTP_VIA'], "wap") ? true : false;
    } 
    // 脑残法，判断手机发送的客户端标志,兼容性有待提高
    if (isset ($_SERVER['HTTP_USER_AGENT'])){
        $clientkeywords = array ('nokia',
            'sony',
            'ericsson',
            'mot',
            'samsung',
            'htc',
            'sgh',
            'lg',
            'sharp',
            'sie-',
            'philips',
            'panasonic',
            'alcatel',
            'lenovo',
            'iphone',
            'ipod',
            'blackberry',
            'meizu',
            'android',
            'netfront',
            'symbian',
            'ucweb',
            'windowsce',
            'palm',
            'operamini',
            'operamobi',
            'openwave',
            'nexusone',
            'cldc',
            'midp',
            'wap',
            'mobile'
            ); 
        // 从HTTP_USER_AGENT中查找手机浏览器的关键字
        if (preg_match("/(" . implode('|', $clientkeywords) . ")/i", strtolower($_SERVER['HTTP_USER_AGENT']))){
            return true;
        } 
    } 
    // 协议法，因为有可能不准确，放到最后判断
    if (isset ($_SERVER['HTTP_ACCEPT'])){ 
        // 如果只支持wml并且不支持html那一定是移动设备
        // 如果支持wml和html但是wml在html之前则是移动设备
        if ((strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') !== false) && (strpos($_SERVER['HTTP_ACCEPT'], 'text/html') === false || (strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') < strpos($_SERVER['HTTP_ACCEPT'], 'text/html'))))
        {
            return true;
        } 
    } 
    return false;
} 