<?php

// src/Composer/ScriptHandler.php 或 framework/Composer/ScriptHandler.php

namespace Framework\Composer;

use Symfony\Component\Filesystem\Filesystem;

class ScriptHandler
{
    public static function copyDefaultConfig()
    {
        $filesystem = new Filesystem();

        // 获取当前包（framework）的根目录
        // __DIR__ = .../vendor/xuey490/framework/src/Composer
        $packageRoot = dirname(__DIR__, 2); // 回退到 vendor/xuey490/framework
		//echo $packageRoot ."\r\n";//NovaPHP0.0.9\TEST\vendor\xuey490\framework
        // 源：包内的 config 目录
        $sourceConfig = $packageRoot . '/config';

        // 目标：用户项目根目录下的 config（因为脚本从项目根目录调用）
        $targetConfig = getcwd() . '/config';

        if (!$filesystem->exists($targetConfig)) {
            $filesystem->mirror($sourceConfig, $targetConfig);
            echo "Copied config files to: $targetConfig\n";
        } else {
            echo "Config directory already exists. Skipping.\n";
        }
    }
}