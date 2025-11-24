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

use RuntimeException;
use Sylius\Bundle\ThemeBundle\Model\Theme;
use Sylius\Bundle\ThemeBundle\Model\ThemeInterface;

class RichTheme extends Theme implements RichThemeInterface
{
    private string $prefix = '';

    private string $parameterName;

    /**
     * @var array<array<string,string|bool|float|int|array<string, mixed>|null>>
     */
    private array $vars = [];

    private ?string $currentScope = null;

    /**
     * @var array<string, ThemeDependencyInterface>
     */
    private array $dependencies = [];

    /**
     * @var array<string, AssetToBuildInterface>
     */
    private array $buildPipeline = [];

    public function cleanParents(): void
    {
        $this->parents = [];
    }

    public function addParent(ThemeInterface $theme): void
    {
        $this->parents[$theme->getName()] = $theme;
    }

    public function removeParent(ThemeInterface $theme): void
    {
        unset($this->parents[$theme->getName()]);
    }

    public function getPrefix(): string
    {
        return $this->prefix;
    }

    public function setPrefix(string $prefix): void
    {
        $this->prefix = $prefix;
    }

    public function getParameterName(): string
    {
        return $this->parameterName;
    }

    public function setParameterName(string $parameterName): void
    {
        $this->parameterName = $parameterName;
    }

    public function addVar(string $scope, string $key, string|bool|float|int|array|null $value): void
    {
        $this->vars[$scope][$key] = $value;
    }

    public function getVar(string $scope, string $key): string|bool|float|int|array|null
    {
        return $this->vars[$scope][$key] ?? null;
    }

    public function getVars(?string $scope = null): array
    {
        $scope ??= $this->currentScope;
        if (null === $scope) {
            throw new RuntimeException('No scope defined');
        }
        if (false === isset($this->vars[$scope])) {
            throw new RuntimeException('Scope not found');
        }

        return $this->vars[$scope];
    }

    public function getAllVars(): array
    {
        return $this->vars;
    }

    public function setCurrentScope(string $scope): void
    {
        $this->currentScope = $scope;
    }

    public function getCurrentScope(): ?string
    {
        return $this->currentScope;
    }

    public function addDependency(ThemeDependencyInterface $dependency): void
    {
        $this->dependencies[$dependency->getName()] = $dependency;
    }

    public function getDependency(string $name): ?ThemeDependencyInterface
    {
        return $this->dependencies[$name] ?? null;
    }

    public function getDependencies(): array
    {
        return $this->dependencies;
    }

    public function addToBuildPipeline(AssetToBuildInterface $assetToBuild): void
    {
        $this->buildPipeline[$assetToBuild->getName()] = $assetToBuild;
    }

    public function getFromBuildPipeline(string $name): ?AssetToBuildInterface
    {
        return $this->buildPipeline[$name] ?? null;
    }

    public function getBuildPipeline(): array
    {
        return $this->buildPipeline;
    }
}
