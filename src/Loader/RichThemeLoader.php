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

namespace MonsieurBiz\SyliusThemeCompanionPlugin\Loader;

use MonsieurBiz\SyliusThemeCompanionPlugin\Model\AssetToBuild;
use MonsieurBiz\SyliusThemeCompanionPlugin\Model\RichTheme;
use MonsieurBiz\SyliusThemeCompanionPlugin\Model\RichThemeInterface;
use MonsieurBiz\SyliusThemeCompanionPlugin\Model\ThemeDependency;
use Sylius\Bundle\ThemeBundle\Loader\ThemeLoaderInterface;
use Sylius\Bundle\ThemeBundle\Model\ThemeInterface;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\AutowireDecorated;

#[AsDecorator('sylius.theme.loader')]
readonly class RichThemeLoader implements ThemeLoaderInterface
{
    public function __construct(
        #[AutowireDecorated]
        private ThemeLoaderInterface $originalThemeLoader,
        #[Autowire(param: 'theme_companion.themes')]
        private array $themes,
    ) {
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function load(): array
    {
        $richThemes = [];
        foreach ($this->originalThemeLoader->load() as $originalTheme) {
            $richThemes[$originalTheme->getName()] = $this->buildRichTheme($originalTheme);
        }

        foreach ($richThemes as $richTheme) {
            $parents = $richTheme->getParents();
            $richTheme->cleanParents();
            foreach ($parents as $parent) {
                if (!isset($richThemes[$parent->getName()])) {
                    continue;
                }

                $richTheme->addParent($richThemes[$parent->getName()]);
            }
        }

        return $richThemes;
    }

    protected function buildRichTheme(ThemeInterface $originalTheme): RichThemeInterface
    {
        $richTheme = $this->copyOriginalThemeDataToRichTheme($originalTheme);

        $config = $this->themes[$richTheme->getName()] ?? null;
        if (null === $config) {
            return $richTheme;
        }

        $richTheme->setPrefix($config['prefix']);
        $richTheme->setParameterName($config['parameter_name']);

        foreach ($config['vars'] ?? [] as $scope => $vars) {
            foreach ($vars as $key => $value) {
                $richTheme->addVar($scope, $key, $value);
            }
        }

        $this->addDependencies($richTheme, $config);
        $this->initBuildPipeline($richTheme, $config);

        return $richTheme;
    }

    protected function addDependencies(RichThemeInterface $theme, array $config): void
    {
        foreach ($config['dependencies'] ?? [] as $name => $data) {
            if (!isset($data['package_file']) || !isset($data['package_manager'])) {
                continue;
            }

            $dependency = new ThemeDependency($name, $data['package_manager'], $data['package_file'], $data['config'] ?? []);
            $theme->addDependency($dependency);
        }
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function initBuildPipeline(RichThemeInterface $theme, array $config): void
    {
        foreach ($config['build_pipeline'] ?? [] as $name => $data) {
            if (!isset($data['input']) || !isset($data['output']) || !isset($data['runner'])) {
                continue;
            }

            $assetToBuild = new AssetToBuild($name, $data['runner'], $data['input'], $data['output'], $data['config'] ?? []);
            $theme->addToBuildPipeline($assetToBuild);
        }
    }

    protected function copyOriginalThemeDataToRichTheme(ThemeInterface $originalTheme): RichThemeInterface
    {
        $richTheme = new RichTheme($originalTheme->getName(), $originalTheme->getPath());
        $richTheme->setTitle($originalTheme->getTitle());
        $richTheme->setDescription($originalTheme->getTitle());
        foreach ($originalTheme->getAuthors() as $author) {
            $richTheme->addAuthor($author);
        }
        foreach ($originalTheme->getParents() as $parent) {
            $richTheme->addParent($parent);
        }
        foreach ($originalTheme->getScreenshots() as $screenshot) {
            $richTheme->addScreenshot($screenshot);
        }

        return $richTheme;
    }
}
