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

readonly class AssetToBuild implements AssetToBuildInterface
{
    /**
     * @param array<string, mixed> $config
     */
    public function __construct(
        private string $name,
        private string $assetBuilder,
        private string $input,
        private string $output,
        private array $config = []
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getAssetBuilder(): string
    {
        return $this->assetBuilder;
    }

    public function getInput(): string
    {
        return $this->input;
    }

    public function getOutput(): string
    {
        return $this->output;
    }

    public function getConfig(): array
    {
        return $this->config;
    }
}
