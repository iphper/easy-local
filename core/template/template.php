<?php

namespace Core\Template;

use Core\Template\Rule;

/**
 * 模板渲染引擎
 * 支持参数传入、自定义解析规则、静态文件生成
 */
class Template
{
    /**
     * 模板文件路径
     */
    private string $templatePath = '';

    /**
     * 模板参数
     */
    private array $data = [];

    /**
     * 解析规则集合
     */
    private array $rules = [];

    /**
     * 缓存渲染结果
     */
    private string $content = '';

    /**
     * 是否已渲染
     */
    private bool $rendered = false;

    /**
     * 构造函数
     */
    public function __construct()
    {
        $this->registerDefaultRules();
    }

    /**
     * 设置模板文件路径
     */
    public function setTemplate(string $templatePath): self
    {
        $this->templatePath = $templatePath;
        $this->rendered = false;
        return $this;
    }

    /**
     * 设置模板参数
     */
    public function setData(array $data): self
    {
        $this->data = array_merge($this->data, $data);
        $this->rendered = false;
        return $this;
    }

    /**
     * 设置单个参数
     */
    public function set(string $key, mixed $value): self
    {
        $this->data[$key] = $value;
        $this->rendered = false;
        return $this;
    }

    /**
     * 获取参数
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    /**
     * 注册解析规则
     * 
     * @param string $name 规则名称
     * @param Rule $rule 规则对象
     */
    public function registerRule(Rule $rule): self
    {
        $this->rules[$rule->getName()] = $rule;
        $this->rendered = false;
        return $this;
    }

    /**
     * 注册默认规则
     */
    protected function registerDefaultRules(): void
    {
        // 条件判断规则: {{ if ($expr) }}...{{ elseif ($expr) }}...{{ else }}...{{ endif }} - 支持嵌套表达式
        $this->rules['condition'] = new Rule(
            name: 'condition',
            pattern: '/\{\s*\{\s*if\s*\(\s*(\$[^)]+)\s*\)\s*\}\s*\}(.*?)\{\s*\{\s*endif\s*\}\s*\}/s',
            callback: function ($matches) {
                $ifExpr = $matches[1];
                $content = $matches[2];

                // 检查 if 条件
                $ifValue = $this->evaluateExpression($ifExpr);
                if (!empty($ifValue)) {
                    // 提取 if 块的内容（到第一个 elseif 或 else 或 endif）
                    if (preg_match('/^(.*?)\{\s*\{\s*(?:elseif|else)\s*(?:\([^)]*\))?\s*\}\s*\}/s', $content, $m)) {
                        return $m[1];
                    }
                    // 没有 elseif/else，返回全部内容
                    return $content;
                }

                // if 条件为假，检查 elseif 和 else
                return $this->handleElseIfElse($content);
            }
        );

        // 循环规则: {{ foreach ($array as $item) }}...{{ endforeach }} - 支持嵌套数组表达式
        $this->rules['foreach'] = new Rule(
            name: 'foreach',
            pattern: '/\{\s*\{\s*foreach\s*\(\s*(\$[a-zA-Z_][a-zA-Z0-9_]*(?:(?:\s*->\s*[a-zA-Z_][a-zA-Z0-9_]*)|(?:\s*\[[^\]]+\]))*)\s+as\s+(\$[a-zA-Z_][a-zA-Z0-9_]*)\s*\)\s*\}\s*\}(.*?)\{\s*\{\s*endforeach\s*\}\s*\}/s',
            callback: function ($matches) {
                $arrayExpr = $matches[1];
                $itemKey = trim($matches[2], '$');
                $content = $matches[3];

                $array = $this->evaluateExpression($arrayExpr);
                if (!is_array($array)) {
                    return '';
                }

                $result = '';
                foreach ($array as $item) {
                    // 保存原始值，避免覆盖
                    $originalItem = $this->data[$itemKey] ?? null;
                    $this->data[$itemKey] = $item;
                    
                    // 在循环内容中处理表达式
                    $processedContent = $this->processExpressions($content);
                    $result .= $processedContent;
                    
                    // 恢复原始值
                    if ($originalItem !== null) {
                        $this->data[$itemKey] = $originalItem;
                    } else {
                        unset($this->data[$itemKey]);
                    }
                }

                return $result;
            }
        );

        // 表达式规则（包含方法调用和属性访问）: {{ $expr->method() }} 或 {{ $obj->prop->method()->prop }}
        $this->rules['expression'] = new Rule(
            name: 'expression',
            pattern: '/\{\s*\{\s*(\$[a-zA-Z_][a-zA-Z0-9_]*(?:(?:\s*->\s*(?:[a-zA-Z_][a-zA-Z0-9_]*|\([^)]*\)))|(?:\s*\[[^\]]+\]))*)\s*\}\s*\}/',
            callback: function ($matches) {
                $expr = trim($matches[1]);
                $value = $this->evaluateExpression($expr);
                
                // 如果是对象，返回其字符串表示
                if (is_object($value)) {
                    return method_exists($value, '__toString') ? (string) $value : '';
                }
                
                return (string) $value;
            }
        );

        // 函数调用规则: {{ func($arg1, $arg2) }}
        $this->rules['function'] = new Rule(
            name: 'function',
            pattern: '/\{\s*\{\s*([a-zA-Z_][a-zA-Z0-9_]*)\s*\(\s*([^)]*)\s*\)\s*\}\s*\}/',
            callback: function ($matches) {
                $func = $matches[1];
                $argsStr = trim($matches[2]);

                // 检查是否为 PHP 内置函数
                if (!function_exists($func)) {
                    return '';
                }

                // 解析参数
                $argList = $this->parseArguments($argsStr);

                return (string) call_user_func_array($func, $argList);
            }
        );

    }

    /**
     * 处理 elseif 和 else 分支
     */
    private function handleElseIfElse(string $content): string
    {
        // 按照 elseif/else 分割内容
        // 模式：{{ elseif ($expr) }} 或 {{ else }}
        
        // 处理 elseif
        if (preg_match('/\{\s*\{\s*elseif\s*\(\s*(\$[^)]+)\s*\)\s*\}\s*\}(.*?)(?=\{\s*\{\s*(?:elseif|else|endif)\s*(?:\([^)]*\))?\s*\}\s*\}|\Z)/s', $content, $m)) {
            $elseifExpr = $m[1];
            $elseifContent = $m[2];
            
            $elseifValue = $this->evaluateExpression($elseifExpr);
            if (!empty($elseifValue)) {
                return $elseifContent;
            }
            
            // 继续检查后续的 elseif 或 else
            $remainingContent = substr($content, strlen($m[0]));
            return $this->handleElseIfElse($remainingContent);
        }
        
        // 处理 else
        if (preg_match('/\{\s*\{\s*else\s*\}\s*\}(.*?)(?=\{\s*\{\s*endif\s*\}\s*\}|\Z)/s', $content, $m)) {
            return $m[1];
        }
        
        // 没有匹配任何条件
        return '';
    }

    /**
     * 处理内容中的表达式 - 应用表达式规则和函数规则
     */
    private function processExpressions(string $content): string
    {
        // 应用表达式规则（处理 {{ $var }}、{{ $obj->method() }} 等）
        if (isset($this->rules['expression'])) {
            $rule = $this->rules['expression'];
            $content = $rule->apply($content);
        }

        // 应用函数规则（处理 {{ func($arg) }} 等）
        if (isset($this->rules['function'])) {
            $rule = $this->rules['function'];
            $content = $rule->apply($content);
        }

        return $content;
    }

    /**
     * 计算表达式的值 - 支持多层嵌套的属性访问和方法调用
     * 支持: $var, $var->prop, $var->method(), $var['key'], $var->prop->method()->prop['key']
     */
    private function evaluateExpression(string $expr)
    {
        $expr = trim($expr);
        
        // 提取第一个变量名
        if (!preg_match('/^\$([a-zA-Z_][a-zA-Z0-9_]*)/', $expr, $m)) {
            return '';
        }

        $varName = $m[1];
        $value = $this->data[$varName] ?? null;
        
        if ($value === null) {
            return '';
        }

        // 获取剩余的表达式部分
        $remaining = substr($expr, strlen($m[0]));
        
        if (empty($remaining)) {
            return $value;
        }

        // 处理链式调用: ->prop, ->method(), ['key']
        while (!empty($remaining)) {
            $remaining = ltrim($remaining);
            
            // 处理方法调用: ->method(args)
            if (preg_match('/^->\s*([a-zA-Z_][a-zA-Z0-9_]*)\s*\(\s*([^)]*)\s*\)/', $remaining, $m)) {
                $method = $m[1];
                $argsStr = trim($m[2]);
                
                if (is_object($value) && method_exists($value, $method)) {
                    $argList = $this->parseArguments($argsStr);
                    $value = call_user_func_array([$value, $method], $argList);
                } else {
                    return '';
                }
                
                $remaining = substr($remaining, strlen($m[0]));
                continue;
            }
            
            // 处理属性访问: ->prop
            if (preg_match('/^->\s*([a-zA-Z_][a-zA-Z0-9_]*)/', $remaining, $m)) {
                $prop = $m[1];
                
                if (is_object($value)) {
                    $value = $value->$prop ?? null;
                } elseif (is_array($value)) {
                    $value = $value[$prop] ?? null;
                } else {
                    return '';
                }
                
                if ($value === null) {
                    return '';
                }
                
                $remaining = substr($remaining, strlen($m[0]));
                continue;
            }
            
            // 处理数组访问: ['key'] 或 ["key"]
            if (preg_match('/^\s*\[\s*[\'"]?([a-zA-Z0-9_]+)[\'"]?\s*\]/', $remaining, $m)) {
                $key = $m[1];
                
                if (is_array($value)) {
                    $value = $value[$key] ?? null;
                } elseif (is_object($value) && property_exists($value, $key)) {
                    $value = $value->$key ?? null;
                } else {
                    return '';
                }
                
                if ($value === null) {
                    return '';
                }
                
                $remaining = substr($remaining, strlen($m[0]));
                continue;
            }
            
            // 如果无法匹配，说明表达式格式有误，退出
            break;
        }
        
        return $value;
    }

    /**
     * 解析函数/方法的参数列表
     */
    private function parseArguments(string $argsStr): array
    {
        if (empty($argsStr)) {
            return [];
        }

        $argList = [];
        $args = explode(',', $argsStr);
        
        foreach ($args as $arg) {
            $arg = trim($arg);
            
            // 如果是表达式，计算其值
            if (str_starts_with($arg, '$')) {
                $value = $this->evaluateExpression($arg);
                $argList[] = $value;
            } else {
                // 否则作为字符串参数
                $argList[] = $arg;
            }
        }
        
        return $argList;
    }

    /**
     * 渲染模板
     */
    public function render(): string
    {
        header('Content-Type: text/html; charset=utf-8');
        
        if ($this->rendered && $this->content) {
            return $this->content;
        }

        if (!file_exists($this->templatePath)) {
            throw new \Exception("模板文件不存在: {$this->templatePath}");
        }

        // 读取模板内容
        $templateContent = file_get_contents($this->templatePath);

        // 按规则顺序应用解析
        foreach ($this->rules as $rule) {
            $templateContent = $rule->apply($templateContent);
        }

        // 创建临时文件并执行
        $tmpFile = tempnam(sys_get_temp_dir(), 'tpl_');
        file_put_contents($tmpFile, $templateContent);

        ob_start();

        extract($this->data);

        include $tmpFile;

        $output = ob_get_clean();
        
        // 清理临时文件
        @unlink($tmpFile);

        $this->rendered = true;
        $this->content = $output;

        return $output;
    }

    /**
     * 渲染成文件
     * 
     * @param string $outputPath 输出文件路径
     * @param bool $overwrite 是否覆盖现有文件
     */
    public function renderToFile(string $outputPath, bool $overwrite = true): bool
    {
        $renderedContent = $this->render();

        // 检查输出目录
        $outputDir = dirname($outputPath);
        if (!is_dir($outputDir)) {
            if (!mkdir($outputDir, 0755, true)) {
                throw new \Exception("无法创建输出目录: {$outputDir}");
            }
        }

        // 检查文件是否已存在
        if (file_exists($outputPath) && !$overwrite) {
            throw new \Exception("文件已存在: {$outputPath}");
        }

        // 写入文件
        if (file_put_contents($outputPath, $renderedContent) === false) {
            throw new \Exception("无法写入文件: {$outputPath}");
        }

        return true;
    }

    /**
     * 获取所有已注册的规则
     */
    public function getRules(): array
    {
        return $this->rules;
    }

    /**
     * 清除指定规则
     */
    public function removeRule(string $name): self
    {
        unset($this->rules[$name]);
        $this->rendered = false;
        return $this;
    }

    /**
     * 清除所有规则
     */
    public function clearRules(): self
    {
        $this->rules = [];
        $this->rendered = false;
        return $this;
    }

    /**
     * 获取所有参数
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * 魔术方法：支持 echo 直接输出
     */
    public function __toString()
    {
        $this->render();
    }
}
