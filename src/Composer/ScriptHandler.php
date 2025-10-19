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

        // 获取当前包的根目录（关键！）
        $packageDir = dirname(__DIR__, 2); // 假设类在 framework/Composer/

        // 目标：项目根目录（即 vendor 的父目录）
        $projectRoot = dirname(__DIR__, 4); // vendor/xuey490/framework/ → 退回4层

        // 或更安全的方式：从 composer.json 推断
        // 但简单起见，我们假设从 bin/nova 调用时，当前工作目录是项目根目录
        $projectRoot = getcwd();

        $configSource = $packageDir . '/config';
        $configTarget = $projectRoot . '/config';

        if (!$filesystem->exists($configTarget)) {
            $filesystem->mirror($configSource, $configTarget);
            echo "Copied config files to: $configTarget\n";
        } else {
            echo "Config directory already exists. Skipping.\n";
        }
    }
}