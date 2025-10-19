<?php

// framework/Composer/ScriptHandler.php
/*
namespace Framework\Composer;

use Composer\Script\Event;
use Symfony\Component\Filesystem\Filesystem;

class ScriptHandler
{
    public static function copyDefaultConfig(Event $event)
    {
        $extra = $event->getComposer()->getPackage()->getExtra();
        $frameworkRoot = realpath(__DIR__ . '/../../..'); // 框架在vendor中的根目录
        $projectRoot = getcwd(); // 用户项目根目录
        $filesystem = new Filesystem();

        foreach ($extra['novaphp']['config-files'] as $relativePath) {
            $source = $frameworkRoot . '/' . $relativePath;
            $target = $projectRoot . '/' . $relativePath;

            // 仅复制用户项目中不存在的文件
            if ($filesystem->exists($source) && !$filesystem->exists($target)) {
                $filesystem->mkdir(dirname($target)); // 确保目标目录存在
                $filesystem->copy($source, $target);
                $event->getIO()->write("Copied default config: <info>$relativePath</info>");
            }
        }
    }
}
*/
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