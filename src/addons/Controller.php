<?php

namespace speed\addons;

use think\App;
use think\facade\Lang;
use think\facade\View;
use think\facade\Config;
use app\common\controller\Base;

/**
 * 插件基类控制器.
 */
class Controller extends Base
{
    // 当前插件操作
    protected $addon = null;
    //插件路径
    protected $addon_path = null;
    protected $controller = null;
    protected $action = null;
    // 当前template
    protected $template;
    protected $param;

    /**
     * 无需登录的方法,同时也就不需要鉴权了.
     *
     * @var array
     */
    protected $noNeedLogin = ['*'];

    /**
     * 无需鉴权的方法,但需要登录.
     *
     * @var array
     */
    protected $noNeedRight = ['*'];


    /**
     * 布局模板
     *
     * @var string
     */
    protected $layout = 'layout/main';

    /**
     * 架构函数.
     */
    public function __construct(App $app)
    {
        //移除HTML标签
        app()->request->filter('trim,strip_tags,htmlspecialchars');
        // 是否自动转换控制器和操作名
        $convert = Config::get('url_convert');
        $filter = $convert ? 'strtolower' : 'trim';
        // 处理路由参数
        $this->param = $param = app()->request->param();
        $route = app()->request->rule()->getName();
        if(empty($param) || !isset($param['addon'])){
            $route = explode('@',$route);
            $param['action'] = $route[1];
            [
                $param['addons'],
                $param['addon'],
                $param['module'],
                $param['controllers'],
                $param['controller'],
            ] = explode('\\',$route[0]);
            $param['controller'] = $param['controller']. (isset(explode('\\',$route[0])[5])? DIRECTORY_SEPARATOR.explode('\\',$route[0])[5]:'');
        }
        $addon = isset($param['addon']) ? $param['addon'] : '';
        $module = isset($param['module']) ? $param['module'] : 'index';
        $controller = isset($param['controller']) ? $param['controller'] : app()->request->controller();
        $action = isset($param['action']) ? $param['action'] : app()->request->action();
        $this->addon = $addon ? call_user_func($filter, $addon) : '';
        $this->module = $module ? call_user_func($filter, $module) : '';
        $this->addon_path = $app->addons->getAddonsPath() . $this->addon . DIRECTORY_SEPARATOR;
        $this->controller = $controller ? call_user_func($filter, $controller) : 'index';
        $this->action = $action ? call_user_func($filter, $action) : 'index';
        // 父类的调用必须放在设置模板路径之后
        $this->_initialize();
        parent::__construct($app);
    }

    protected function _initialize()
    {   

         // 渲染配置到视图中
        if($this->param){
            View::engine('Think')->config([
                'view_path' => $this->addon_path .'view' .DIRECTORY_SEPARATOR
            ]);
        }else{
            View::engine('Think')->config([
                'view_path' => $this->addon_path .'view'.DIRECTORY_SEPARATOR.$this->module.DIRECTORY_SEPARATOR.str_replace('.','/',$this->controller) .DIRECTORY_SEPARATOR
            ]);
        }
        // 如果有使用模板布局
        $this->layout && app()->view->engine()->layout($this->module.DIRECTORY_SEPARATOR.trim($this->layout,'/'));

        $config = get_addons_config($this->addon);
        View::assign(['config'=>$config]);
        // 加载系统语言包
        Lang::load([
            $this->addon_path . 'lang' . DIRECTORY_SEPARATOR . Lang::getLangset() . '.php',
        ]);
        parent::initialize();

    }



}
