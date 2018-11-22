<?php
namespace app\index\controller;
use think\Db;
use think\Request;
use app\common\lib\ReturnData;
use app\common\lib\Helper;
use app\common\logic\ArticleLogic;

class Article extends Base
{
    public function _initialize()
	{
		parent::_initialize();
    }
    
    public function getLogic()
    {
        return new ArticleLogic();
    }
    
    //列表
    public function index()
	{
        $where = [];
        $title = '';
        
        $key = input('key', null);
        if($key != null)
        {
            $arr_key = logic('Article')->getArrByString($key);
            if(!$arr_key){$this->error('您访问的页面不存在或已被删除', '/' , '', 3);}
            
            //分类id
            if(isset($arr_key['f']) && $arr_key['f']>0)
            {
                $where['type_id'] = $arr_key['f'];
                
                $post = model('ArticleType')->getOne(['id'=>$arr_key['f']]);
                $this->assign('post',$post);
                
                //面包屑导航
                $this->assign('bread', logic('Article')->get_article_type_path($where['type_id']));
            }
        }
        
        $where['delete_time'] = 0;
        $where['status'] = 0;
        $list = $this->getLogic()->getPaginate($where, 'id desc', ['content']);
        if(!$list){$this->error('您访问的页面不存在或已被删除', '/' , '', 3);}
        
        $page = $list->render();
        $page = preg_replace('/key=[a-z0-9]+&amp;/', '', $page);
        $page = preg_replace('/&amp;key=[a-z0-9]+/', '', $page);
        $page = preg_replace('/\?page=1"/', '"', $page);
        $this->assign('page', $page);
        $this->assign('list', $list);
        
        //最新
        $where2['delete_time'] = 0;
        $where2['status'] = 0;
        $zuixin_list = logic('Article')->getAll($where2, 'id desc', ['content'], 5);
        $this->assign('zuixin_list',$zuixin_list);
        
        //推荐
        $where3['delete_time'] = 0;
        $where3['status'] = 0;
        $where3['tuijian'] = 1;
        $where3['litpic'] = ['<>',''];
        $tuijian_list = logic('Article')->getAll($where3, 'id desc', ['content'], 5);
        $this->assign('tuijian_list',$tuijian_list);
        
        //seo标题设置
        $title = $title.'最新动态';
        $this->assign('title',$title);
        return $this->fetch();
    }
	
    //详情
    public function detail()
	{
        if(!checkIsNumber(input('id',null))){$this->error('您访问的页面不存在或已被删除', '/' , '', 3);}
        $id = input('id');
        
        $where['id'] = $id;
        $post = $this->getLogic()->getOne($where);
        if(!$post){$this->error('您访问的页面不存在或已被删除', '/' , '', 3);}
        
        $post['content']=$this->getLogic()->replaceKeyword($post['content']);
        
        $this->assign('post',$post);
        
        //随机文章
        $where2['delete_time'] = 0;
        $where2['status'] = 0;
        $rand_posts = logic('Article')->getAll($where2, 'rand()', ['content'], 5);
        $this->assign('rand_posts',$rand_posts);
        
        //最新文章
        $where3['delete_time'] = 0;
        $where3['status'] = 0;
        $where3['type_id'] = $post['type_id'];
        $zuixin_posts = logic('Article')->getAll($where3, 'id desc', ['content'], 5);
        $this->assign('zuixin_posts',$zuixin_posts);
        
        //面包屑导航
        $this->assign('bread', logic('Article')->get_article_type_path($post['type_id']));
        
        //上一篇、下一篇
        $this->assign($this->getPreviousNextArticle(['article_id'=>$id]));
        
        return $this->fetch();
    }
    
    /**
     * 获取文章上一篇，下一篇
     * @param int $param['article_id'] 当前文章id
     * @return array
     */
    public function getPreviousNextArticle(array $param)
    {
        $res['previous_article'] = [];
        $res['next_article'] = [];
        
        $where['id'] = $param['article_id'];
        $post = model('Article')->getOne($where,['content']);
        if(!$post)
        {
            return $res;
        }
        $res['previous_article'] = model('Article')->getOne(['id'=>['<',$param['article_id']],'type_id'=>$post['type_id']],['content']);
        $res['next_article'] = model('Article')->getOne(['id'=>['>',$param['article_id']],'type_id'=>$post['type_id']],['content']);
        return $res;
    }
}