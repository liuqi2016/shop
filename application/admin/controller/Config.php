<?php
namespace app\admin\controller;
use think\Controller;
class Config extends Common
{
    	
	public function index()
    {
       return view();
    }

	public function add()
    {
       $path = 'application/extra/web.php';
       $file = include $path;      
       $config = array(
        'WEB_TIT' => input('WEB_TIT'),
        'WEB_COM' => input('WEB_COM'),
        'WEB_AUT' => input('WEB_AUT'),
        'WEB_QQ'  => input('WEB_QQ'),
        'WEB_ICP' => input('WEB_ICP'),
        'WEB_QRCODE' => input('WEB_QRCODE'),         
        'WEB_REG' => input('WEB_REG'),
        'WEB_KEY' => input('WEB_KEY'),
        'WEB_DES' => input('WEB_DES'),
        'WEB_TJCODE' => input('WEB_TJCODE'),
        'WEB_TAG' => input('WEB_TAG'),
        'WEB_TPT' => input('WEB_TPT'),
        'WEB_WTPT' => input('WEB_WTPT'),
        'WEB_SORT' => input('WEB_SORT'),                   
        'WEB_URL' => input('WEB_URL'),
        'WEB_OPE' => input('WEB_OPE'),
        'WEB_LOGO' => input('WEB_LOGO'),
        'WEB_FORUMLOGO' => input('WEB_FORUMLOGO'),
        'WEB_WAPLOGO' => input('WEB_WAPLOGO'), 
        'WEB_EXPTIME' => preg_replace('# #','',input('WEB_EXPTIME')),
        'WEB_ADC' => input('WEB_ADC'),        
        'WEB_TDJ' => input('WEB_TDJ'),
        'WEB_TBUID' => preg_replace('# #','',input('WEB_TBUID')),
        'WEB_QQPID' => preg_replace('# #','',input('WEB_QQPID')),
        'WEB_YHQPID' => preg_replace('# #','',input('WEB_YHQPID')),
        'WEB_DTKKEY' => preg_replace('# #','',input('WEB_DTKKEY')),
        'WEB_MMCOOKIE' => input('WEB_MMCOOKIE'),
        'WEB_TBKEY' => preg_replace('# #','',input('WEB_TBKEY')),
        'WEB_TBSECREC' => preg_replace('# #','',input('WEB_TBSECREC')),
        'WEB_WXTOKEN' => preg_replace('# #','',input('WEB_WXTOKEN')),
        'WEB_WXINFO' => input('WEB_WXINFO'),                                                                                          
       );
       $res = array_merge($file, $config);
       $str = '<?php return [';
       foreach ($res as $key => $value){
           $str .= '\''.$key.'\''.'=>'.'\''.str_replace("'", "&apos;", $value).'\''.',';
       };
       $str .= ']; ';
       if(file_put_contents($path, $str)){
           return json(array('code'=>200,'msg'=>'修改成功'));
       }else {
           return json(array('code'=>0,'msg'=>'修改失败'));
       }
    }   

}
