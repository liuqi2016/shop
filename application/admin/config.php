<?php

//URL地址转换
/*if (!function_exists('url')) {
    function url($url = '', $vars = '', $suffix = true, $domain = false){
        $url =  Url::build($url, $vars, $suffix, $domain);
		$url = str_replace('/admin.php','/admin.php?s=',$url);
		return $url;
    }
}*/

return [
    
	'template'=> [
    'view_suffix' => 'html',
	'view_depr'    => '_',
    ],

];
