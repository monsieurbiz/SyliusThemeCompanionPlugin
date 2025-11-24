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

namespace MonsieurBiz\SyliusThemeCompanionPlugin\Resolver;

use MonsieurBiz\SyliusThemeCompanionPlugin\Model\RichThemeInterface;

interface CurrentThemePathResolverInterface
{
    public function resolve(RichThemeInterface $theme, string $path): string;
}
