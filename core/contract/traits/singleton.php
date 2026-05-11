<?php

namespace Core\Contract\Traits;

trait Singleton
{
    /** @static $instance */
    private static self $instance;

    private function __construct() {}
    
    private function __clone() {}
    
    public function __wakeup()
    {
        throw new \Error("不可序列化");
    }

    public function __unserialize($v)
    {
        throw new \Error('不可反序列化');
    }

    // 获取实例
    public static function instance(mixed ...$args): static
    {
        if (empty(static::$instance)) {
            static::$instance = new static();
            // 存在初始化方法就调用
            method_exists(static::$instance, 'init') AND (call_user_func([static::$instance, 'init'], ...$args));
        }
        return static::$instance;
    }
}
