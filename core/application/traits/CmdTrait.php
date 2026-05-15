<?php

namespace Core\Application\Traits;

use Kit\Terminal\Output\Colorizer\Facade as Colorizer;

/**
 * 应用目录
 * @property string $root
 * @property string $namespace
 * 
 * @method mixed runAction(string $path)
 * 
 */
trait CmdTrait
{
    // 初始化命令模式
    protected function initCmdTrait()
    {
        if (!$this->verifyCmdTrait()) {
            return;
        }
        $this->namespace = '\\App\\Command';
    }

    // 命令模式逻辑
    protected function startCmdServer()
    {
        $path = $_SERVER['argv'][1] ?? 'index/index';

        $result = $this->runAction($path);

        is_scalar($result) or ($result = json_encode($result, JSON_UNESCAPED_UNICODE));
        echo Colorizer::fg("#00ff00")->text($result),PHP_EOL;
    }

    // 检查是否命令行模式
    protected function verifyCmdTrait() : bool
    {
        return PHP_SAPI == 'cli';
    }

    // 运行命令模式
    protected function runCmdTrait()
    {
        if (!$this->verifyCmdTrait()) {
            return false;
        }
        return $this->startCmdServer();
    }

}
