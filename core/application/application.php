<?php

namespace Core\Application;

use Core\Config\Config;
use Core\Contract\Traits\Singleton;

use Core\Application\Traits\{
    DirTrait, HttpTrait, CmdTrait
};

class Application
{
    
    use Singleton;
    use DirTrait, HttpTrait, CmdTrait;

    /** @var string $root */
    protected string $root;

    // 命名空间
    protected string $namespace = '\\App\\Controller';

    /**
     * 应用执行
     * @method run
     * 
     */
    public function run()
    {
        return $this->runTraitMethod('run');
    }

    // 初始化方法
    protected function init(string $root)
    {
        // 保存项目根目录
        $this->root = $root;

        // 获取
        $this->initTrait();

        // 加载配置
        $this->initConfig();
    }

    // 初始化所有Trait
    protected function initTrait()
    {
        return $this->runTraitMethod('init');
    }

    protected function runTraitMethod(string $name)
    {

        // 获取所有trait
        $traits = class_uses($this);

        foreach ($traits as $trait) {
            // 约定：init + Trait名
            $method = $name . (new \ReflectionClass($trait))->getShortName();

            if (method_exists($this, $method)) {
                $this->$method();
            }
        }
    }

    // 初始化配置信息
    protected function initConfig()
    {
        Config::instance($this->configDir());
    }

    // 运行操作
    protected function runAction(string $path)
    {
        $arr = array_map('ucfirst', explode('/', $path));
        $class = $this->namespace . '\\' . implode('\\', $arr);

        // 判断是否存在
        $method = 'index';
        if (class_exists($class)) {
            // 直接运行这个类的index方法
            return call_user_func([new $class, $method]);
        }

        // 没有就执行后面的
        $method = strtolower(array_pop($arr));
        $class = $this->namespace . '\\' . implode('\\', $arr);
        
        if (class_exists($class)) {
            // 直接运行这个类的index方法
            return call_user_func([new $class, $method]);
        }

        throw new \Exception($class .' Not Found', 404);
    }

}
