<?php

declare(strict_types=1);

namespace Framework\Composer;

/**
 * Composer / CLI 脚本处理器
 *
 * 将框架默认配置、错误页模板和 .env 复制到用户项目根目录。
 * 默认不覆盖已有文件；传入 force=true 或 --force 时覆盖。
 * 仅使用 PHP 内置函数，兼容 Linux / macOS / Windows。
 */
class ScriptHandler
{
    /**
     * Composer scripts / 插件入口。
     *
     * @param mixed $event Composer 事件对象，或 null（CLI 调用）
     */
    public static function copyDefaultConfig(mixed $event = null): void
    {
        $options = self::resolveOptions($event);
        $result = self::install($options['projectRoot'], $options['force'], $options['packageRoot']);
        self::printReport($result);
    }

    /**
     * 执行复制。
     *
     * @param string      $projectRoot 用户项目根目录
     * @param bool        $force       是否覆盖已存在文件
     * @param string|null $packageRoot 框架包根目录，null 时自动探测
     *
     * @return array{copied: list<string>, skipped: list<string>, errors: list<string>, projectRoot: string, packageRoot: string}
     */
    public static function install(string $projectRoot, bool $force = false, ?string $packageRoot = null): array
    {
        $packageRoot = self::normalizePath($packageRoot ?? self::detectPackageRoot());
        $projectRoot = self::normalizePath($projectRoot);

        $copied = [];
        $skipped = [];
        $errors = [];

        if ($packageRoot === '' || !is_dir($packageRoot)) {
            return [
                'copied' => $copied,
                'skipped' => $skipped,
                'errors' => ['Framework package root not found: ' . $packageRoot],
                'projectRoot' => $projectRoot,
                'packageRoot' => $packageRoot,
            ];
        }

        self::ensureDirectory($projectRoot, $errors);

        self::mirrorDirectory(
            $packageRoot . '/config',
            $projectRoot . '/config',
            $force,
            $copied,
            $skipped,
            $errors
        );

        self::mirrorDirectory(
            $packageRoot . '/view/errors',
            $projectRoot . '/resource/view/errors',
            $force,
            $copied,
            $skipped,
            $errors
        );

        self::copyFileIfNeeded(
            $packageRoot . '/.env',
            $projectRoot . '/.env',
            $force,
            $copied,
            $skipped,
            $errors
        );

        if (!is_file($projectRoot . '/.env') && is_file($packageRoot . '/.env.example')) {
            self::copyFileIfNeeded(
                $packageRoot . '/.env.example',
                $projectRoot . '/.env',
                $force,
                $copied,
                $skipped,
                $errors
            );
        }

        self::copyFileIfNeeded(
            $packageRoot . '/.env.example',
            $projectRoot . '/.env.example',
            $force,
            $copied,
            $skipped,
            $errors
        );

        return [
            'copied' => $copied,
            'skipped' => $skipped,
            'errors' => $errors,
            'projectRoot' => $projectRoot,
            'packageRoot' => $packageRoot,
        ];
    }

    /**
     * 探测框架包根目录（含 composer.json + config/）。
     */
    public static function detectPackageRoot(): string
    {
        $candidates = [
            dirname(__DIR__, 2),
            dirname(__DIR__, 3),
        ];

        foreach ($candidates as $candidate) {
            if (self::isPackageRoot($candidate)) {
                return self::normalizePath($candidate);
            }
        }

        return self::normalizePath(dirname(__DIR__, 2));
    }

    /**
     * 探测用户项目根目录。
     */
    public static function detectProjectRoot(?string $hint = null): string
    {
        $candidates = [];

        if (is_string($hint) && $hint !== '') {
            $candidates[] = $hint;
        }

        $cwd = getcwd();
        if (is_string($cwd) && $cwd !== '') {
            $candidates[] = $cwd;
        }

        $packageRoot = self::detectPackageRoot();
        $candidates[] = $packageRoot;
        $candidates[] = dirname($packageRoot, 2);
        $candidates[] = dirname($packageRoot, 3);

        foreach ($candidates as $candidate) {
            $normalized = self::normalizePath($candidate);
            if ($normalized !== '' && is_file($normalized . '/composer.json')) {
                return $normalized;
            }
        }

        return self::normalizePath(is_string($cwd) ? $cwd : $packageRoot);
    }

    /**
     * @param mixed $event
     *
     * @return array{projectRoot: string, packageRoot: string, force: bool}
     */
    private static function resolveOptions(mixed $event): array
    {
        $force = false;
        $projectRoot = null;
        $packageRoot = self::detectPackageRoot();

        if (is_object($event) && method_exists($event, 'getArguments')) {
            $arguments = $event->getArguments();
            if (is_array($arguments)) {
                $force = in_array('--force', $arguments, true) || in_array('-f', $arguments, true);
            }
        }

        if (is_object($event) && method_exists($event, 'getComposer')) {
            try {
                $composer = $event->getComposer();
                if (is_object($composer) && method_exists($composer, 'getConfig')) {
                    $vendorDir = $composer->getConfig()->get('vendor-dir');
                    if (is_string($vendorDir) && $vendorDir !== '') {
                        $projectRoot = dirname($vendorDir);
                    }
                }
            } catch (\Throwable) {
                // fall through to filesystem detection
            }
        }

        return [
            'projectRoot' => self::detectProjectRoot($projectRoot),
            'packageRoot' => $packageRoot,
            'force' => $force,
        ];
    }

    /**
     * @param list<string> $copied
     * @param list<string> $skipped
     * @param list<string> $errors
     */
    private static function mirrorDirectory(
        string $source,
        string $target,
        bool $force,
        array &$copied,
        array &$skipped,
        array &$errors
    ): void {
        $source = self::normalizePath($source);
        $target = self::normalizePath($target);

        if (!is_dir($source)) {
            $skipped[] = $target . ' (source missing)';

            return;
        }

        self::ensureDirectory($target, $errors);

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        /** @var \SplFileInfo $file */
        foreach ($iterator as $file) {
            $relative = substr($file->getPathname(), strlen($source));
            $destination = $target . $relative;

            if ($file->isDir()) {
                self::ensureDirectory($destination, $errors);
                continue;
            }

            if (!$file->isFile()) {
                continue;
            }

            self::copyFileIfNeeded($file->getPathname(), $destination, $force, $copied, $skipped, $errors);
        }
    }

    /**
     * @param list<string> $copied
     * @param list<string> $skipped
     * @param list<string> $errors
     */
    private static function copyFileIfNeeded(
        string $source,
        string $target,
        bool $force,
        array &$copied,
        array &$skipped,
        array &$errors
    ): void {
        $source = self::normalizePath($source);
        $target = self::normalizePath($target);

        if (!is_file($source)) {
            return;
        }

        if (self::isSamePath($source, $target)) {
            $skipped[] = $target . ' (same path)';

            return;
        }

        if (is_file($target) && !$force) {
            $skipped[] = $target;

            return;
        }

        self::ensureDirectory(dirname($target), $errors);

        if (@copy($source, $target) === false) {
            $errors[] = 'Failed to copy ' . $source . ' -> ' . $target;

            return;
        }

        $copied[] = $target;
    }

    /**
     * @param list<string> $errors
     */
    private static function ensureDirectory(string $directory, array &$errors): void
    {
        if ($directory === '' || is_dir($directory)) {
            return;
        }

        if (!@mkdir($directory, 0755, true) && !is_dir($directory)) {
            $errors[] = 'Failed to create directory: ' . $directory;
        }
    }

    /**
     * @param array{copied: list<string>, skipped: list<string>, errors: list<string>, projectRoot: string, packageRoot: string} $result
     */
    private static function printReport(array $result): void
    {
        $copiedCount = count($result['copied']);
        $skippedCount = count($result['skipped']);
        $errorCount = count($result['errors']);

        echo "NovaPHP install\n";
        echo '  package: ' . $result['packageRoot'] . "\n";
        echo '  project: ' . $result['projectRoot'] . "\n";

        if ($copiedCount > 0) {
            echo "  copied ({$copiedCount}):\n";
            foreach ($result['copied'] as $path) {
                echo '    + ' . $path . "\n";
            }
        }

        if ($skippedCount > 0) {
            echo "  skipped existing ({$skippedCount})\n";
        }

        if ($errorCount > 0) {
            echo "  errors ({$errorCount}):\n";
            foreach ($result['errors'] as $error) {
                echo '    ! ' . $error . "\n";
            }
        }

        if ($copiedCount === 0 && $errorCount === 0) {
            echo "  nothing new to copy (already initialized)\n";
        }
    }

    private static function isPackageRoot(string $path): bool
    {
        $path = self::normalizePath($path);

        return $path !== ''
            && is_file($path . '/composer.json')
            && is_dir($path . '/config')
            && is_file($path . '/bin/nova');
    }

    private static function isSamePath(string $left, string $right): bool
    {
        return strcasecmp(self::normalizePath($left), self::normalizePath($right)) === 0;
    }

    private static function normalizePath(string $path): string
    {
        $path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
        $path = rtrim($path, DIRECTORY_SEPARATOR);

        $resolved = realpath($path);
        if ($resolved !== false) {
            return $resolved;
        }

        return $path;
    }
}
