#!/usr/bin/env php
<?php

// bin/nova

if (PHP_SAPI !== 'cli') {
    exit(1);
}

$autoload = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($autoload)) {
    // 当从源码直接运行时（开发阶段）
    $autoload = __DIR__ . '/../framework/../vendor/autoload.php';
    if (!file_exists($autoload)) {
        echo "Error: Composer autoloader not found.\n";
        exit(1);
    }
}
require_once $autoload;

use Framework\Composer\ScriptHandler;

$command = $argv[1] ?? null;

if ($command === 'install') {
    ScriptHandler::copyDefaultConfig();
    echo "✅ NovaPHP framework installed successfully!\n";
    echo "📁 Config files copied to your project root.\n";
} else {
    echo "Usage: nova install\n";
    exit(1);
}