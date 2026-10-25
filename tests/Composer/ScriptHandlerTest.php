<?php

declare(strict_types=1);

namespace Framework\Tests\Composer;

use Framework\Composer\ScriptHandler;
use PHPUnit\Framework\TestCase;

final class ScriptHandlerTest extends TestCase
{
    public function testInstallCopiesMissingFilesWithoutOverwriting(): void
    {
        $packageRoot = dirname(__DIR__, 2);
        $projectRoot = sys_get_temp_dir() . '/novaphp-install-' . uniqid('', true);
        self::assertTrue(@mkdir($projectRoot, 0755, true));

        try {
            $first = ScriptHandler::install($projectRoot, false, $packageRoot);
            self::assertSame([], $first['errors']);
            self::assertNotSame([], $first['copied']);
            self::assertFileExists($projectRoot . '/config/app.php');
            self::assertFileExists($projectRoot . '/.env');
            self::assertFileExists($projectRoot . '/resource/view/errors/404.html.twig');

            $marker = "<?php return ['installed_by' => 'test'];";
            file_put_contents($projectRoot . '/config/app.php', $marker);

            $second = ScriptHandler::install($projectRoot, false, $packageRoot);
            self::assertSame([], $second['errors']);
            self::assertSame($marker, file_get_contents($projectRoot . '/config/app.php'));

            $forced = ScriptHandler::install($projectRoot, true, $packageRoot);
            self::assertSame([], $forced['errors']);
            self::assertNotSame($marker, file_get_contents($projectRoot . '/config/app.php'));
        } finally {
            self::removeDirectory($projectRoot);
        }
    }

    private static function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $file) {
            $file->isDir() ? @rmdir($file->getPathname()) : @unlink($file->getPathname());
        }

        @rmdir($directory);
    }
}
