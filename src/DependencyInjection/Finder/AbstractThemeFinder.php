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

abstract readonly class AbstractThemeFinder
{
    protected function buildThemeData(array $composerData, string $path): array
    {
        $themeData = $composerData['extra']['sylius-theme'];
        unset($composerData['extra']);

        return [
            ...$composerData,
            ...$themeData,
            'path' => $path,
        ];
    }
}
