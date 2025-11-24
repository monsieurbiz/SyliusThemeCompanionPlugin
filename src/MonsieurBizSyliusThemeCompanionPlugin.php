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

namespace MonsieurBiz\SyliusThemeCompanionPlugin;

use Sylius\Bundle\CoreBundle\Application\SyliusPluginTrait;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class MonsieurBizSyliusThemeCompanionPlugin extends Bundle
{
    use SyliusPluginTrait;

    /**
     * Require for the new bundlle structure without a root config folder
     * instead of Resources/config folder.
     */
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
