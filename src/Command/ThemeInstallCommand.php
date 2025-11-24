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

namespace MonsieurBiz\SyliusThemeCompanionPlugin\Command;

use MonsieurBiz\SyliusThemeCompanionPlugin\Model\RichThemeInterface;
use MonsieurBiz\SyliusThemeCompanionPlugin\Process\CommandOutputNotifier;
use MonsieurBiz\SyliusThemeCompanionPlugin\Process\ProcessOutputHandlerAware;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'theme:install',
    description: 'Install themes dependencies',
)]
class ThemeInstallCommand extends AbstractThemeCommand
{
    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function executeForTheme(SymfonyStyle $ioStyle, RichThemeInterface $theme): void
    {
        if (0 === \count($theme->getDependencies())) {
            return;
        }

        $ioStyle->section(\sprintf('Installing dependencies for theme %s', $theme->getName()));
        foreach ($theme->getDependencies() as $type => $dependency) {
            $packageManager = $this->packageManagerRepository->get($dependency->getPackageManager());
            if ($packageManager instanceof ProcessOutputHandlerAware) {
                $packageManager->setProcessOutputHandler(new CommandOutputNotifier($ioStyle));
            }

            if (null === $packageManager) {
                $ioStyle->warning(\sprintf('No package manager found for "%s"', $dependency->getPackageManager()));

                continue;
            }

            $packages = $packageManager->install(
                $this->currentThemePathResolver->resolve($theme, $dependency->getPackageFile()),
                $dependency->getConfig()
            );
            foreach ($packages as $package) {
                $ioStyle->info(\sprintf('[%s] Package "%s" installed with "%s"', $type, $package, $packageManager::getIdentifier()));
            }
        }
    }

    protected function getTitle(): string
    {
        return 'Install themes assets';
    }
}
