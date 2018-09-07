<?php
namespace app\index\controller;
use think\Controller;
use think\Db;
use app\index\model\Thread as ThreadModel;
class Thread extends Controller
{
    public function _initialize()
    {
        $show['show'] = 1;
        $LinksList = Db::name('links')->where($show)->order('id desc')->select();
        $this->assign('LinksList', $LinksList);
    }

    public function add()
    {
        if (!session('userid') || !session('username')) {
            $this->error('亲！请登录',url('login/index'));
        } else {
            $ThreadMd = new ThreadModel();
            if (request()->isPost()) {
                $data = input('post.');
                $data['time'] = time();
                $data['show'] = config('web.WEB_OPE');
                $data['view'] = 1;
                $data['uid'] = session('userid');
                $description = htmlspecialchars_decode(strip_tags($data['content']));
                $description = preg_replace( "@<script(.*?)</script>@is", "", $description );
                $description = preg_replace( "@<iframe(.*?)</iframe>@is", "", $description );
                $description = preg_replace( "@<style(.*?)</style>@is", "", $description );
                $description = preg_replace( "@<(.*?)>@is", "", $description );
                $description = str_replace("&nbsp;", '', $description);
                $description = str_replace(" ", '', mb_substr($description, 0, 200, 'utf-8'));

                $data['description'] = $description;
                $MemberDb = Db::name('member');
                $MemberDb->where('userid', session('userid'))->setInc('point', config('point.ADD_POINT'));
                if ($ThreadMd->add($data)) {
                    return json(array('code' => 200, 'msg' => '添加成功'));
                } else {
                    return json(array('code' => 0, 'msg' => '添加失败'));
                }
            }
            $ForumDb = Db::name('forum');
            $ForumList = $ForumDb->select();
            $this->assign('ForumList', $ForumList);
            $webtag = config('web.WEB_TAG');
            $TagList = explode(',', $webtag);
            $this->assign('TagList', $TagList);
            return view();
        }
    }
    public function edit()
    {
        if (!session('userid') || !session('username')) {
            $this->error('亲！请登录',url('login/index'));
        } else {
            $id = input('id');
            $uid = session('userid');
            $ThreadMd = new ThreadModel();
            $dtl = $ThreadMd->find($id);
            if (empty($id) || $dtl == null || $dtl['uid'] != $uid) {
                $this->error('亲！您迷路了');
            } else {
                if (request()->isPost()) {
                    $data = input('post.');

                    $description = htmlspecialchars_decode(strip_tags($data['content']));
                    $description = preg_replace( "@<script(.*?)</script>@is", "", $description );
                    $description = preg_replace( "@<iframe(.*?)</iframe>@is", "", $description );
                    $description = preg_replace( "@<style(.*?)</style>@is", "", $description );
                    $description = preg_replace( "@<(.*?)>@is", "", $description );
                    $description = str_replace("&nbsp;", '', $description);
                    $description = str_replace(" ", '', mb_substr($description, 0, 200, 'utf-8'));

                    $data['description'] = $description;                    
                    /*$data['description'] = mb_substr(strip_tags($data['content']), 0, 200, 'utf-8');*/
                    if ($ThreadMd->edit($data)) {
                        return json(array('code' => 200, 'msg' => '修改成功'));
                    } else {
                        return json(array('code' => 0, 'msg' => '修改失败'));
                    }
                }
                $ForumDb = Db::name('forum');
                $Thread = $ThreadMd->find($id);
                $ForumList = $ForumDb->select();
                $this->assign(array('ForumList' => $ForumList, 'Thread' => $Thread));
				$webtag = config('web.WEB_TAG');
                $TagList = explode(',', $webtag);
		        $this->assign('TagList', $TagList);
                return view();
            }
        }
    }
    public function doUploadPic()
    {
        if (!session('userid') || !session('username')) {
            $this->error('亲！请登录',url('login/index'));
        } else {
			$file = request()->file('FileName');
			$info = $file->move(ROOT_PATH . DS . 'upload');
			if ($info) {
				$path = WEB_URL . DS . 'upload' . DS . $info->getSaveName();
				echo str_replace("\\", "/", $path);
			}
		}
    }
}