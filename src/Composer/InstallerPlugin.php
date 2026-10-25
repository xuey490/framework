<?php

declare(strict_types=1);

namespace Framework\Composer;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;

/**
 * Composer 插件：在 require / update 后自动把框架配置复制到项目根目录。
 *
 * 依赖包的 composer scripts 默认不会执行，因此用插件在消费项目里一键完成初始化。
 * Linux / Windows 行为一致，已有文件默认不覆盖。
 */
class InstallerPlugin implements PluginInterface, EventSubscriberInterface
{
    private ?Composer $composer = null;

    private ?IOInterface $io = null;

    public function activate(Composer $composer, IOInterface $io): void
    {
        $this->composer = $composer;
        $this->io = $io;
    }

    public function deactivate(Composer $composer, IOInterface $io): void
    {
        $this->composer = null;
        $this->io = null;
    }

    public function uninstall(Composer $composer, IOInterface $io): void
    {
        $this->composer = null;
        $this->io = null;
    }

    /**
     * @return array<string, string>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            ScriptEvents::POST_INSTALL_CMD => 'onPostCmd',
            ScriptEvents::POST_UPDATE_CMD => 'onPostCmd',
        ];
    }

    public function onPostCmd(Event $event): void
    {
        $vendorDir = $event->getComposer()->getConfig()->get('vendor-dir');
        $projectRoot = is_string($vendorDir) && $vendorDir !== ''
            ? dirname($vendorDir)
            : (string) getcwd();

        $result = ScriptHandler::install($projectRoot, false);

        $io = $this->io ?? $event->getIO();
        $copied = count($result['copied']);
        $errors = count($result['errors']);

        if ($copied > 0) {
            $io->write('<info>NovaPHP:</info> copied ' . $copied . ' default file(s) into ' . $result['projectRoot']);
        }

        if ($errors > 0) {
            foreach ($result['errors'] as $error) {
                $io->writeError('<warning>NovaPHP: ' . $error . '</warning>');
            }
        }
    }
}
