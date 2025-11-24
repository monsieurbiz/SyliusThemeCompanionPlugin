<?php

/*
 * This file is part of Monsieur Biz' Theme Companion plugin for Sylius.
 *
 * (c) Monsieur Biz <sylius@monsieurbiz.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MonsieurBiz\SyliusThemeCompanionPlugin\DependencyInjection\Finder;

use Composer\InstalledVersions as ComposerRuntime;
use Symfony\Component\Filesystem\Path;

readonly class PackagedThemeFinder extends AbstractThemeFinder implements ThemeFinderInterface
{
    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function getThemes(): array
    {
        $themes = [];
        foreach ($this->getSyliusPluginPackages() as $packageName) {
            $path = ComposerRuntime::getInstallPath($packageName);
            if (null === $path || false === file_exists(Path::join($path, '/composer.json'))) {
                continue;
            }

            /** @var array $composerJson */
            $composerJson = json_decode(file_get_contents(Path::join($path, '/composer.json')) ?: '', true);
            if (true !== ($composerJson['extra']['sylius-theme'][self::NEED_COMPANION_FLAG] ?? false)) {
                continue;
            }

            $themes[$composerJson['name']] = $this->buildThemeData($composerJson, $path);
        }

        return $themes;
    }

    private function getSyliusPluginPackages(): array
    {
        return array_unique(ComposerRuntime::getInstalledPackagesByType('sylius-theme'));
    }
}
