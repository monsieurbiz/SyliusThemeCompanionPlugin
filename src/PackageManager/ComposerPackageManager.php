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
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Process\Process;

#[AutoconfigureTag('monsieurbiz_theme_companion.package_manager')]
class ComposerPackageManager implements PackageManagerInterface
{
    use ProcessOutputAwareTrait;

    public const string IDENTIFIER = 'composer';

    public function __construct(
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
            throw new InvalidArgumentException(\sprintf('The composer file "%s" does not exist.', $dependenciesFile));
        }

        /** @var array<string, mixed> $packageFile */
        $packageFile = json_decode(file_get_contents($dependenciesFile) ?: '', true) ?? [];
        if (!isset($packageFile['require'])) {
            throw new InvalidArgumentException(\sprintf('The composer file "%s" must contain a "require" key.', $dependenciesFile));
        }

        $composerBinary = $config['composer_binary'] ?? ['composer'];
        $installed = [];
        foreach ($packageFile['require'] as $package => $constraint) {
            $process = new Process([
                ...$composerBinary,
                'require',
                \sprintf('%s:%s', $package, $constraint),
                '--working-dir',
                $this->projectDir,
            ], \dirname($dependenciesFile));
            $process->setTimeout(null);
            $process->start();

            foreach ($process as $data) {
                $this->getProcessOutputHandler()?->handle($data);
            }

            if (!$process->isSuccessful()) {
                continue;
            }

            $installed[] = $package;
        }

        return $installed;
    }
}
