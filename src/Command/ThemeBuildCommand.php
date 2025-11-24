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
    name: 'theme:build',
    description: 'Build themes assets',
)]
class ThemeBuildCommand extends AbstractThemeCommand
{
    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function executeForTheme(SymfonyStyle $ioStyle, RichThemeInterface $theme): void
    {
        if (0 === \count($theme->getBuildPipeline())) {
            return;
        }

        $ioStyle->section(\sprintf('Run build pipeline for theme %s', $theme->getName()));
        foreach ($theme->getBuildPipeline() as $type => $assetToBuild) {
            $assetBuilder = $this->assetBuilderRepository->get($assetToBuild->getAssetBuilder());
            if (null === $assetBuilder) {
                $ioStyle->warning(\sprintf('No asset builder found for "%s"', $assetToBuild->getAssetBuilder()));

                continue;
            }

            if ($assetBuilder instanceof ProcessOutputHandlerAware) {
                $assetBuilder->setProcessOutputHandler(new CommandOutputNotifier($ioStyle));
            }

            $input = $this->currentThemePathResolver->resolve($theme, $assetToBuild->getInput());
            $output = $this->currentThemePathResolver->resolve($theme, $assetToBuild->getOutput());
            $result = $assetBuilder->process($input, $output, $assetToBuild->getConfig());

            if (true === $result) {
                $input = str_replace($this->projectDir, '', $input);
                $output = str_replace($this->projectDir, '', $output);
                $ioStyle->info(\sprintf('[%s] Asset "%s" processed to "%s" with "%s"', $type, $input, $output, $assetBuilder::getIdentifier()));
            }
        }
    }

    protected function getTitle(): string
    {
        return 'Build themes assets';
    }
}
