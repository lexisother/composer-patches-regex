<?php

namespace lexisother\Composer;

use lexisother\Composer\Provider\RegexPatcherProvider;
use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\Capable;
use Composer\Plugin\PluginInterface;
use cweagans\Composer\Capability\Patcher\PatcherProvider;
use cweagans\Composer\Event\PluginEvent;
use cweagans\Composer\Event\PluginEvents;

class Plugin implements PluginInterface, Capable, EventSubscriberInterface
{
    public function activate(Composer $composer, IOInterface $io): void
    {
        $io->log('info', '[regex-plugin] activate');
    }

    public function deactivate(Composer $composer, IOInterface $io): void
    {
        $io->log('info', '[regex-plugin] deactivate');
    }

    public function uninstall(Composer $composer, IOInterface $io): void
    {
        $io->log('info', '[regex-plugin] uninstall');
    }

    public function getCapabilities(): array
    {
        return [
            PatcherProvider::class => RegexPatcherProvider::class
        ];
    }

    public static function getSubscribedEvents()
    {
        return [
            PluginEvents::POST_DISCOVER_PATCHERS => ['moveToFront']
        ];
    }

    /**
     * A very hacky interceptor that moves our patcher to the front of
     * the list of patchers, making sure it always runs first.
     *
     * @param PluginEvent $event
     * @return void
     */
    public function moveToFront(PluginEvent $event): void
    {
        $patchers = $event->getCapabilities();

        // Find our patcher
        $index = array_search(true, array_map(function($item) {
            if (isset($item->__REGEXPATCHER__)) {
                return true;
            }
            return false;
        }, $patchers));

        // Move it to the front of the array
        $obj = $patchers[$index];
        unset($patchers[$index]);
        array_unshift($patchers, $obj);

        $event->setCapabilities($patchers);
    }
}
