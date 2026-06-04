<?php

namespace Core\Application\Traits;

/**
 * 应用目录
 * @property string $root
 * @property string $namespace
 * 
 * @method mixed runAction(string $path)
 */
trait HttpTrait
{
    // 初始化Http模式
    protected function initHttpTrait()
    {
        if (!$this->verifyHttpTrait()) {
            return ;
        }
        $this->namespace = '\\App\\Controller'; 
    }

    // Http模式逻辑
    protected function startHttpServer()
    {
        $uri = trim($_SERVER['REQUEST_URI'], '/') ?: 'index';

        $code = 200;
        $result = [];
        try {
            // 真实uri路径
            $path = explode('?', $uri)[0];
    
            $result = $this->runAction($path);

        } catch(\Throwable $e) {
            $code = $e->getCode();
            $result['error'] = $e->getMessage();
            $result['trace'] = $e->getTrace();
        }

        // 设置状态码
        http_response_code($code);

        // 非标量数据默认响应json
        if (!is_scalar($result)) {
            // 响应json数据
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($result, JSON_UNESCAPED_UNICODE);
            return;
        } else {
            echo $result;
            return;
        }

    }

    // 校验
    protected function verifyHttpTrait() : bool
    {
        return PHP_SAPI == 'fpm-fcgi';
    }

    // 执行http服务
    protected function runHttpTrait()
    {
        if (!$this->verifyHttpTrait()) {
            return false;
        }
        return $this->startHttpServer();
    }
}
