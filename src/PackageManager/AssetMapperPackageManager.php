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

namespace MonsieurBiz\SyliusThemeCompanionPlugin\PackageManager;

use InvalidArgumentException;
use MonsieurBiz\SyliusThemeCompanionPlugin\Process\ProcessOutputAwareTrait;
use Symfony\Component\AssetMapper\ImportMap\ImportMapManager;
use Symfony\Component\AssetMapper\ImportMap\PackageRequireOptions;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AutoconfigureTag('monsieurbiz_theme_companion.package_manager')]
class AssetMapperPackageManager implements PackageManagerInterface
{
    use ProcessOutputAwareTrait;

    public const string IDENTIFIER = 'asset_mapper';

    public function __construct(
        private readonly ImportMapManager $importMapManager,
        #[Autowire(param: 'kernel.project_dir')]
        private readonly string $projectDir,
    ) {
    }

    public static function getIdentifier(): string
    {
        return self::IDENTIFIER;
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function install(string $dependenciesFile, ?array $config = null): array
    {
        if (!file_exists($dependenciesFile)) {
            throw new InvalidArgumentException(\sprintf('The JS package file "%s" does not exist.', $dependenciesFile));
        }

        /** @var array<string, mixed> $packageFile */
        $packageFile = json_decode(file_get_contents($dependenciesFile) ?: '', true) ?? [];
        if (!isset($packageFile['dependencies'])) {
            throw new InvalidArgumentException('The JS package file must contain a "dependencies" key.');
        }

        $packages = $installed = [];
        foreach ($packageFile['dependencies'] as $package => $constraint) {
            $this->getProcessOutputHandler()?->handle('Installing package: ' . $package);
            $path = null;
            if (str_starts_with($constraint, 'file:')) {
                /** @var string $path */
                $path = str_replace('file:', '', $constraint);
                $constraint = null;
            }
            $packages[] = new PackageRequireOptions(
                $package,
                $constraint,
                path: $path,
            );
        }

        if (isset($packageFile['name'], $packageFile['main'])) {
            $mainFile = \dirname($dependenciesFile) . '/' . $packageFile['main'];
            if (!file_exists($mainFile)) {
                throw new InvalidArgumentException(\sprintf('The main file "%s" does not exist.', $mainFile));
            }
            $this->getProcessOutputHandler()?->handle('Installing main package file: ' . $mainFile);
            $packages[] = new PackageRequireOptions(
                $packageFile['name'],
                path: '.' . str_replace($this->projectDir, '', $mainFile),
                entrypoint: true,
            );
        }

        $newPackages = $this->importMapManager->require($packages);
        foreach ($newPackages as $newPackage) {
            $installed[] = $newPackage->importName;
        }

        return $installed;
    }
}
