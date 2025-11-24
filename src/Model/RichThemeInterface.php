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

use Sylius\Bundle\ThemeBundle\Model\ThemeInterface;

interface RichThemeInterface extends ThemeInterface
{
    public function cleanParents(): void;

    public function getPrefix(): string;

    public function setPrefix(string $prefix): void;

    public function getParameterName(): string;

    public function setParameterName(string $parameterName): void;

    /**
     * @param string|bool|float|int|array<string, mixed>|null $value
     */
    public function addVar(string $scope, string $key, string|bool|float|int|array|null $value): void;

    /**
     * @return string|bool|float|int|array<string, mixed>|null
     */
    public function getVar(string $scope, string $key): string|bool|float|int|array|null;

    /**
     * @return array<string,string|bool|float|int|array<string, mixed>|null>
     */
    public function getVars(?string $scope = null): array;

    /**
     * @return array<string, array<string, mixed>>
     */
    public function getAllVars(): array;

    public function setCurrentScope(string $scope): void;

    public function getCurrentScope(): ?string;

    public function addDependency(ThemeDependencyInterface $dependency): void;

    public function getDependency(string $name): ?ThemeDependencyInterface;

    /**
     * @return array<string, ThemeDependencyInterface>
     */
    public function getDependencies(): array;

    public function addToBuildPipeline(AssetToBuildInterface $assetToBuild): void;

    public function getFromBuildPipeline(string $name): ?AssetToBuildInterface;

    /**
     * @return array<string, AssetToBuildInterface>
     */
    public function getBuildPipeline(): array;
}
