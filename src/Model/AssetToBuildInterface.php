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

namespace MonsieurBiz\SyliusThemeCompanionPlugin\Model;

interface AssetToBuildInterface
{
    public function getName(): string;

    public function getAssetBuilder(): string;

    public function getInput(): string;

    public function getOutput(): string;

    /**
     * @return array<string, mixed>
     */
    public function getConfig(): array;
}
