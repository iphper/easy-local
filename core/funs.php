<?php

// 导入所有funs.php文件，提供全局函数支持
(function () {
    foreach(glob('core/*/funs.php') as $file) {
        require_once $file;
    }
})();
