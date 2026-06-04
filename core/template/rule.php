<?php

namespace Core\Template;

/**
 * 模板解析规则类
 * 定义解析规则的正则表达式和处理回调
 */
class Rule
{
    /**
     * 规则名称
     */
    private string $name;

    /**
     * 正则表达式模式
     */
    private string $pattern;

    /**
     * 回调函数
     */
    private \Closure $callback;

    /**
     * 构造函数
     * 
     * @param string $name 规则名称
     * @param string $pattern 正则表达式模式
     * @param callable $callback 处理回调函数
     */
    public function __construct(string $name, string $pattern, callable $callback)
    {
        $this->name = $name;
        $this->pattern = $pattern;
        $this->callback = $callback instanceof \Closure ? $callback : \Closure::fromCallable($callback);
    }

    /**
     * 获取规则名称
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * 获取正则表达式模式
     */
    public function getPattern(): string
    {
        return $this->pattern;
    }

    /**
     * 应用规则到内容
     * 
     * @param string $content 要处理的内容
     * @return string 处理后的内容
     */
    public function apply(string $content): string
    {
        return preg_replace_callback($this->pattern, $this->callback, $content);
    }

    /**
     * 执行回调函数
     * 
     * @param array $matches 正则匹配结果
     * @return string 回调结果
     */
    public function execute(array $matches): string
    {
        return (string) call_user_func($this->callback, $matches);
    }
}
