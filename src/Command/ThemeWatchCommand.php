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
use MonsieurBiz\SyliusThemeCompanionPlugin\TaskRunner\WatchableTaskRunnerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'theme:watch',
    description: 'Watch themes assets',
)]
class ThemeWatchCommand extends AbstractThemeCommand
{
    private bool $shouldStop = false;

    private array $processes = [];

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function executeForTheme(SymfonyStyle $ioStyle, RichThemeInterface $theme): void
    {
        if (0 === \count($theme->getBuildPipeline())) {
            return;
        }

        $ioStyle->section(\sprintf('Run watched build pipeline for theme %s', $theme->getName()));
        foreach ($theme->getBuildPipeline() as $assetToBuild) {
            $assetBuilder = $this->assetBuilderRepository->get($assetToBuild->getAssetBuilder());
            if (null === $assetBuilder) {
                $ioStyle->warning(\sprintf('No asset builder found for "%s"', $assetToBuild->getAssetBuilder()));

                continue;
            }

            if (!$assetBuilder instanceof WatchableTaskRunnerInterface) {
                continue;
            }

            $input = $this->currentThemePathResolver->resolve($theme, $assetToBuild->getInput());
            $output = $this->currentThemePathResolver->resolve($theme, $assetToBuild->getOutput());
            $process = $assetBuilder->getWachProcess($input, $output, $assetToBuild->getConfig());

            if (false === $process->isRunning()) {
                $process->start();
            }

            $this->processes[$input] = $process;

            $ioStyle->info(\sprintf('Start watching change for "%s"', $input));
            sleep(1);
            $ioStyle->writeln($process->getIncrementalOutput());
        }
    }

    protected function beforeExecuteForTheme(SymfonyStyle $ioStyle): void
    {
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function afterExecuteForTheme(SymfonyStyle $ioStyle): void
    {
        $ioStyle->note('Press Ctrl-C to stop the watching process.');
        $ioStyle->writeln('Waiting for changes ...');

        if (\function_exists('pcntl_signal')) {
            pcntl_signal(\SIGINT, function () use ($ioStyle): void {
                if (true === $this->shouldStop) {
                    return;
                }

                foreach ($this->processes as $process) {
                    $process->stop();
                }

                $ioStyle->success('Watching themes is now stop. Well done 👍');
                $this->shouldStop = true;
            });
        }

        while (!$this->shouldStop) {
            foreach ($this->processes as $key => $process) {
                $output = $process->getIncrementalOutput();
                $error = $process->getIncrementalErrorOutput();

                if (!empty($output)) {
                    $ioStyle->writeln($output);
                    $ioStyle->writeln('Waiting for changes ...');
                }
                if (!empty($error)) {
                    $ioStyle->error($error);
                }

                if (!$process->isRunning()) {
                    $ioStyle->warning(\sprintf('Watching for "%s" is stopped', $key));
                    unset($this->processes[$key]);
                }
            }
            sleep(1);

            if (\function_exists('pcntl_signal_dispatch')) {
                pcntl_signal_dispatch();
            }
        }
    }

    protected function getTitle(): string
    {
        return 'Build themes assets';
    }
}
