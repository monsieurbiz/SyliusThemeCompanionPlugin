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

namespace MonsieurBiz\SyliusThemeCompanionPlugin\Twig\Extension;

use MonsieurBiz\SyliusThemeCompanionPlugin\Model\RichThemeInterface;
use Sylius\Bundle\ThemeBundle\Context\ThemeContextInterface;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Filesystem\Path;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ThemeAssetExtension extends AbstractExtension
{
    public function __construct(
        private readonly Packages $packages,
        private readonly ThemeContextInterface $themeContext,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('theme_asset', $this->getThemeAssetUrl(...)),
        ];
    }

    public function getThemeAssetUrl(string $path, ?string $packageName = null): string
    {
        /** @var ?RichThemeInterface $theme */
        $theme = $this->themeContext->getTheme();
        if (null === $theme) {
            return $this->packages->getUrl($path, $packageName);
        }

        return $this->packages->getUrl(Path::join($theme->getPrefix(), $path), $packageName);
    }
}
