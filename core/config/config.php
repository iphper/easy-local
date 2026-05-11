<?php

namespace Core\Config;

use Core\Contract\Traits\Singleton;

class Config
{
    use Singleton;

    /** @var string $root 配置文件目录 */
    protected string $root;

    // 扩展名
    protected string $extension = 'json';

    protected string $defaultSpe = '.';

    // 配置容器
    protected array $config = [];

    /**
     * 获取配置信息
     * @method get
     * @param string $key 键
     * @param mixed $def 默认值 
     * @return mixed
     */
    public function get(string $key, $def = null)
    {
        $keys = $this->expKey($key);

        $value = $this->config;
        foreach($keys as $k) {
            $value = $value[$k] ?? $def;
        }

        return $value;
    }

    /**
     * 修改配置
     * @method set
     * @param string $key 键
     * @param mixed $value 值
     * @return static
     */
    public function set(string $key, $value)
    {
        $keys = $this->expKey($key);
        $keys = array_reverse($keys);

        foreach($keys as $k) {
            $val = [];
            $val[$k] = $value;
            $value = $val;
        }

        // 深度合并数组
        $this->config = array_replace_recursive($this->config, $value);

        return $this;
    }

    protected function init(string $root)
    {
        $this->root = $root;
        
        $this->load($root);
    }

    protected function load(string $dir)
    {
        // 查询当前目录下的所有文件
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() == $this->extension) {
                // 处理文件
                $path = $file->getPathName();
                if ($content = file_get_contents($path)) {
                    $content = json_decode($content, true);

                    // 获取相对路径
                    $realPath = str_replace($this->root, '', $path);
                    $realPath = rtrim($realPath, ".{$this->extension}");

                    $this->set(
                        str_replace(DIRECTORY_SEPARATOR, '.', $realPath),
                        $content
                    );
                }
            }
        }
    }

    protected function expKey(string $key) : array
    {
        return explode($this->defaultSpe, $key);
    }

}
