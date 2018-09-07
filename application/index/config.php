<?php
if (request()->isMobile()) {
    $config = './template/'.config('web.WEB_WTPT').'/';
} else {
    $config = './template/'.config('web.WEB_TPT').'/';
}
return [

	'template'=> [
    'view_path'    => $config,
    'view_suffix' => 'html',
	'view_depr'    => '_',
    ],

	'url_html_suffix' => 'html',
	'url_route_on'  =>  true,

];
