<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

return [
    '__pattern__' => [
        'name' => '\w+',
    ],
    '[home]' => [
        ':id'   => ['index/user/home', ['id' => '\d+']],
    ],   
	'[frm]' => [
        ':id'   => ['index/forum/frm', ['id' => '\d+']],
    ],
	'[trd]' => [
        ':id'   => ['index/forum/trd', ['id' => '\d+']],
    ],
    '[cat]' => [
        ':id'   => ['index/index/cat', ['id' => '\d+']],
    ],
    '[dtl]' => [
        ':id'   => ['index/index/dtl', ['id' => '\d+']],
    ],
    '[taobao]' => [
        ':id'   => ['index/index/taobao', ['id' => '\d+']],
    ],        
	'[edit]' => [
        ':id'   => ['index/thread/edit', ['id' => '\d+']],
    ],
	'[edits]' => [
        ':id'   => ['index/comment/edit', ['id' => '\d+']],
    ], 
    'add' => ['index/thread/add',['ext'=>'html']],     
	'choice' => ['index/forum/choice',['ext'=>'html']],
    'forum' => ['index/forum/index',['ext'=>'html']],  
	'search' => ['index/index/search',['ext'=>'html']],
    'jiu' => ['index/jiu/index',['ext'=>'html']],
    'shijiu' => ['index/shijiu/index',['ext'=>'html']],
    'history' => ['index/history/index',['ext'=>'html']],
    'live' => ['index/live/index',['ext'=>'html']],
];
