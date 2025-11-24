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

namespace MonsieurBiz\SyliusThemeCompanionPlugin\DependencyInjection;

use MonsieurBiz\SyliusThemeCompanionPlugin\DependencyInjection\Finder\LocalThemeFinder;
use MonsieurBiz\SyliusThemeCompanionPlugin\DependencyInjection\Finder\PackagedThemeFinder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

use function Symfony\Component\String\u;

final class MonsieurBizSyliusThemeCompanionExtension extends Extension implements PrependExtensionInterface
{
    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../../config'));
        $loader->load('services.yaml');
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function prepend(ContainerBuilder $container): void
    {
        $thirdPartyBundlesViewFileLocator = (new FileLocator(__DIR__ . '/../../templates/bundles'));
        $container->loadFromExtension('twig', [
            'paths' => [
                $thirdPartyBundlesViewFileLocator->locate('SyliusThemeBundle') => 'SyliusTheme',
            ],
        ]);

        $bundles = $container->getParameter('kernel.bundles');
        if (false === \is_array($bundles) || !isset($bundles['SyliusThemeBundle'])) {
            return;
        }

        /** @var string $projectDir */
        $projectDir = $container->getParameter('kernel.project_dir');
        $localThemes = (new LocalThemeFinder($projectDir))->getThemes();
        $packagedThemes = (new PackagedThemeFinder())->getThemes();
        if (false === empty($packagedThemes)) {
            $this->addToSyliusThemeDirectoryPaths($container, $packagedThemes);
        }

        $allThemes = $this->setDefault([...$localThemes, ...$packagedThemes], $projectDir);
        $allThemes = $this->manageChildrenParentsMergedConfig($allThemes);
        $this->addParametersAndResources($container, $allThemes);

        $this->addAssetMapperPaths($container, $allThemes);
    }

    /**
     * @todo this method should be replace by something managed by Symfony\Component\Config\Definition\Processor
     */
    protected function setDefault(array $themes, string $projectDir): array
    {
        // Manage config default values
        foreach ($themes as $key => $theme) {
            $themes[$key] = array_merge([
                'prefix' => u($theme['name'])->trimPrefix('@')->camel()->title()->prepend('@')->toString(),
                'assets_path' => Path::join($theme['path'], '/assets'),
                'assets_generated_path' => null,
                'assets_managers' => [
                    'asset_mapper' => [
                        'paths' => [],
                        'excluded_patterns' => [],
                    ],
                ],
            ], $theme);
            $themes[$key]['parameter_name'] ??= u($themes[$key]['prefix'])->snake()->toString();
            $themes[$key]['assets_generated_path'] ??= Path::join($projectDir, '/var/assets_generated/', $themes[$key]['prefix']);
        }

        return $themes;
    }

    protected function manageChildrenParentsMergedConfig(array $themes): array
    {
        foreach ($themes as $key => $theme) {
            foreach ($theme['parents'] ?? [] as $parentName) {
                $parentTheme = $themes[$parentName] ?? null;
                if (null === $parentTheme) {
                    continue;
                }

                $themes[$key] = $this->deepMergeThemeConfig($parentTheme, $theme);
            }
        }

        return $themes;
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     *
     * @todo this method should be replace by something managed by Symfony\Component\Config\Definition\Processor
     */
    protected function deepMergeThemeConfig(array $parentThemeConfig, array $childThemeConfig): array
    {
        foreach ($childThemeConfig as $key => $value) {
            if (\is_array($value) && isset($parentThemeConfig[$key]) && \is_array($parentThemeConfig[$key])) {
                if (array_is_list($parentThemeConfig[$key]) || array_is_list($value)) {
                    $parentThemeConfig[$key] = array_merge($parentThemeConfig[$key], $value);

                    continue;
                }
                $parentThemeConfig[$key] = $this->deepMergeThemeConfig($parentThemeConfig[$key], $value);

                continue;
            }
            $parentThemeConfig[$key] = $value;
        }

        return $parentThemeConfig;
    }

    protected function addParametersAndResources(ContainerBuilder $container, array $themes): void
    {
        $container->setParameter('theme_companion.current_theme.root_dir', '{{current_theme_root_dir}}');
        $container->setParameter('theme_companion.current_theme.assets_path', '{{current_theme_assets_path}}');
        $container->setParameter('theme_companion.current_theme.assets_generated_path', '{{current_theme_assets_generated_path}}');
        foreach ($themes as $theme) {
            $parameterName = $theme['parameter_name'];
            $container->setParameter(\sprintf('theme_companion.%s.root_dir', $parameterName), $theme['path']);
            $container->setParameter(\sprintf('theme_companion.%s.assets_path', $parameterName), $theme['assets_path']);
            $container->setParameter(\sprintf('theme_companion.%s.assets_generated_path', $parameterName), $theme['assets_generated_path']);

            // This line is used to tell to SF watching if the composer.json have changed and clean the cache if yes
            $container->addResource(new FileResource(Path::join($theme['path'], 'composer.json')));
        }

        $container->setParameter('theme_companion.themes', $themes);
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function addAssetMapperPaths(ContainerBuilder $container, array $themes): void
    {
        $excludePatterns = [];
        $assetMapperPaths = [];

        foreach ($themes as $theme) {
            // If theme managers are defined but there is no asset mapper, we skip the theme
            if (true === isset($theme['assets_managers']) && false === \in_array('asset_mapper', array_keys($theme['assets_managers']), true)) {
                continue;
            }

            $assetPath = $theme['assets_path'];
            if (false === is_dir($assetPath)) {
                continue;
            }

            $prefix = $theme['prefix'];
            $assetMapperPaths[$assetPath] = $prefix;

            if (isset($theme['build_pipeline'])) {
                $filesystem = new Filesystem();
                $generatedPath = $theme['assets_generated_path'];
                $filesystem->mkdir($generatedPath);
                $assetMapperPaths[$generatedPath] = $prefix;
            }

            if (isset($theme['assets_managers']['asset_mapper'])) {
                $assetMapperPaths = array_merge($assetMapperPaths, ($theme['assets_managers']['asset_mapper']['paths'] ?? []));
                $excludePatterns = array_merge($excludePatterns, ($theme['assets_managers']['asset_mapper']['excluded_patterns'] ?? []));
            }
        }

        if (empty($assetMapperPaths)) {
            return;
        }

        $container->prependExtensionConfig('framework', ['asset_mapper' => [
            'excluded_patterns' => $excludePatterns,
            'paths' => $assetMapperPaths,
        ]]);
    }

    /**
     * Declare Master Theme directory to Sylius to be identified as an available the in channel configuration.
     */
    protected function addToSyliusThemeDirectoryPaths(ContainerBuilder $container, array $themes): void
    {
        $syliusThemeConfig = $container->getExtensionConfig('sylius_theme');
        $directories = array_column($themes, 'path');

        /** @var array $config */
        foreach ($syliusThemeConfig as $config) {
            if (true === isset($config['sources']['filesystem']['directories'])) {
                $directories = array_merge($directories, $config['sources']['filesystem']['directories']);
            }
        }

        if (empty($directories)) {
            return;
        }

        $container->loadFromExtension('sylius_theme', [
            'legacy_mode' => true,
            'sources' => ['filesystem' => ['directories' => $directories]],
        ]);
    }
}
