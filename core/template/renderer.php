<?php

namespace Core\Template;

use Core\Contract\Traits\Singleton;

/**
 * 模板渲染器
 * 提供便捷的模板渲染 API
 */
class Renderer
{
    use Singleton;

    protected Template $template;

    public function init()
    {
        $this->template = new Template();
    }

    /**
     * 注册解析规则
     */
    public function setRules(array $rules): self
    {
        foreach ($rules as $rule) {
            if (!$rule instanceof Rule) {
                throw new \InvalidArgumentException("规则必须是 Rule 实例");
            }
            $this->template->registerRule($rule);
        }
        return $this;
    }

    /**
     * 直接渲染并输出
     * 
     * @param string $templatePath 模板文件路径
     * @param array $data 模板参数
     * @return string 渲染结果
     */
    public function render(string $templatePath, array $data = []): string
    {
        // 设置参数并渲染
        return $this->template->setTemplate($templatePath)->setData($data)->render();
    }

    /**
     * 渲染到文件
     * 
     * @param string $templatePath 模板文件路径
     * @param string $outputPath 输出文件路径
     * @param array $data 模板参数
     * @param bool $overwrite 是否覆盖现有文件
     * @return bool
     */
    public function renderToFile(
        string $templatePath,
        string $outputPath,
        array $data = [],
        bool $overwrite = true
    ): bool {
        return $this->template->setTemplate($templatePath)->setData($data)->renderToFile($outputPath, $overwrite);
    }

}
