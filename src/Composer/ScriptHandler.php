<?php

// framework/Composer/ScriptHandler.php
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