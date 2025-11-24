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

readonly class ThemeDependency implements ThemeDependencyInterface
{
    /**
     * @param array<string, mixed> $config
     */
    public function __construct(
        private string $name,
        private string $packageManager,
        private string $packageFile,
        private array $config = []
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPackageManager(): string
    {
        return $this->packageManager;
    }

    public function getPackageFile(): string
    {
        return $this->packageFile;
    }

    public function getConfig(): array
    {
        return $this->config;
    }
}
