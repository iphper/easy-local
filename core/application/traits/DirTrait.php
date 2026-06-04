<?php

namespace Core\Application\Traits;

/**
 * 应用目录
 * @property string $root
 */
trait DirTrait
{
    
    /** @var array $dirmap */
    protected array $dirmap = [
        'config' => 'config',
        'public' => 'public',
        'cache' => 'public'.DIRECTORY_SEPARATOR.'cache',
        'data' => 'public'.DIRECTORY_SEPARATOR.'data',
        'logs' => 'public'.DIRECTORY_SEPARATOR.'logs',
        'view' => 'app'.DIRECTORY_SEPARATOR.'view',
    ];

    protected function initDirTrait()
    {
        
    }

    public function dir(string $type)
    {
        if (empty($this->dirmap[$type])) {
            return null;
        }
        return $this->root . DIRECTORY_SEPARATOR . $this->dirmap[$type];
    }

    public function configDir()
    {
        return $this->dir('config');
    }

    public function publicDir()
    {
        return $this->dir('public');
    }

    public function cacheDir()
    {
        return $this->dir('cache');
    }

    public function logsDir()
    {
        return $this->dir('logs');
    }

    public function dataDir()
    {
        return $this->dir('data');
    }

    public function viewDir()
    {
        return $this->dir('view');
    }
}
