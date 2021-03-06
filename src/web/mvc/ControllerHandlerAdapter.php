<?php
/**
 * 南京灵衍信息科技有限公司
 * User: jinghao@duohuo.net
 * Date: 17/9/1
 * Time: 下午4:18
 */

namespace rap\web\mvc;

use rap\exception\MsgException;
use rap\ioc\Ioc;
use rap\web\Request;
use rap\web\Response;

class ControllerHandlerAdapter extends HandlerAdapter
{

    private $controllerClass;


    private $viewBase;


    /**
     * @param $controllerClass
     * @param $method
     */
    public function __construct($controllerClass, $method)
    {
        $this->controllerClass=$controllerClass;
        $this->method=$method;
    }

    public function handle(Request $request, Response $response)
    {
        try {
            $clazzInstance=Ioc::get($this->controllerClass);
        } catch (\Error $exception) {
            throw new MsgException($exception->getMessage()."对应的路径不存在控制器");
        }
        if (method_exists($clazzInstance, '_before')) {
            //这里只接受参数 方法名,不支持注入
            $method = new \ReflectionMethod(get_class($clazzInstance), '_before');
            $method->invokeArgs($clazzInstance, [$this->method]);
        }
        if (method_exists($clazzInstance, '_before_'.$this->method)) {
            $this->invokeRequest($clazzInstance, '_before_'.$this->method, $request, $response);
        }
        $value=$this->invokeRequest($clazzInstance, $this->method, $request, $response);
        return $value;
    }

    public function viewBase()
    {
        if (!$this->viewBase) {
            $func = new \ReflectionClass($this->controllerClass);
            $this->viewBase=substr(substr($func->getFileName(), 0, stripos($func->getFileName(), DIRECTORY_SEPARATOR
                    ."controller".DIRECTORY_SEPARATOR))
                .DIRECTORY_SEPARATOR.
                'view'.
                DIRECTORY_SEPARATOR, strlen(ROOT_PATH));
        }
        return $this->viewBase;
    }
}
