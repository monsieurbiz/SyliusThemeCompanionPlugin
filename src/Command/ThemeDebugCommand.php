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
use MonsieurBiz\SyliusThemeCompanionPlugin\Resolver\CurrentThemePathResolverInterface;
use Sylius\Bundle\ThemeBundle\Repository\ThemeRepositoryInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(
    name: 'debug:theme',
    description: 'Debug Sylius themes',
)]
class ThemeDebugCommand extends Command
{
    public function __construct(
        private readonly ThemeRepositoryInterface $themeRepository,
        #[Autowire(param: 'kernel.project_dir')]
        private readonly string $projectDir,
        protected readonly CurrentThemePathResolverInterface $currentThemePathResolver,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('theme', InputArgument::OPTIONAL, 'Theme to debug');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $ioStyle = new SymfonyStyle($input, $output);

        /** @var ?string $themeName */
        $themeName = $input->getArgument('theme');
        if (null === $themeName) {
            return $this->debugAllThemes($ioStyle);
        }

        return $this->debugTheme((string) $themeName, $ioStyle);
    }

    private function debugAllThemes(SymfonyStyle $ioStyle): int
    {
        $ioStyle->title('Themes list');
        $rows = [];
        $themes = $this->themeRepository->findAll();
        /** @var RichThemeInterface $theme */
        foreach ($themes as $theme) {
            $rows[] = [$theme->getName(), $theme->getPrefix()];
        }

        $ioStyle->table(['name', 'prefix'], $rows);

        return Command::SUCCESS;
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    private function debugTheme(string $themeName, SymfonyStyle $ioStyle): int
    {
        /** @var ?RichThemeInterface $theme */
        $theme = $this->themeRepository->findOneByName($themeName);
        if (null === $theme) {
            $ioStyle->error(\sprintf('Theme "%s" not found.', $themeName));

            return Command::FAILURE;
        }
        $ioStyle->title(\sprintf('Theme "%s" details', $themeName));

        $ioStyle->section('General Information');
        $ioStyle->listing([
            \sprintf('%s <info>%s</info>', 'Name:', $theme->getName()),
            \sprintf('%s <info>%s</info>', 'Prefix:', $theme->getPrefix()),
        ]);

        if (0 < \count($theme->getParents())) {
            $ioStyle->section('Parents');
            $rows = [];
            /** @var RichThemeInterface $parent */
            foreach ($theme->getParents() as $parent) {
                $rows[] = [$parent->getName(), $parent->getPrefix()];
            }
            $ioStyle->table(['name', 'prefix'], $rows);
        }

        if (0 < \count($theme->getAllVars())) {
            $ioStyle->section('Vars');
            $rows = [];
            foreach ($theme->getAllVars() as $scope => $vars) {
                foreach ($vars as $key => $value) {
                    $rows[] = [$scope, $key, $value];
                }
            }
            $ioStyle->table(['scope', 'name', 'value'], $rows);
        }

        if (0 < \count($theme->getDependencies())) {
            $ioStyle->section('Dependencies');
            $rows = [];
            foreach ($theme->getDependencies() as $dependency) {
                $rows[] = [
                    $dependency->getName(),
                    $dependency->getPackageManager(),
                    str_replace($this->projectDir, '', $this->currentThemePathResolver->resolve($theme, $dependency->getPackageFile())),
                ];
            }
            $ioStyle->table(['name', 'package manager', 'package file'], $rows);
        }

        if (0 < \count($theme->getBuildPipeline())) {
            $ioStyle->section('Build Pipeline Steps');
            $rows = [];
            foreach ($theme->getBuildPipeline() as $name => $assetToBuild) {
                $rows[] = [
                    $name,
                    $assetToBuild->getAssetBuilder(),
                    str_replace($this->projectDir, '', $this->currentThemePathResolver->resolve($theme, $assetToBuild->getInput())),
                    str_replace($this->projectDir, '', $this->currentThemePathResolver->resolve($theme, $assetToBuild->getOutput())),
                ];
            }
            $ioStyle->table(['name', 'asset builder', 'input', 'output'], $rows);
        }

        return Command::SUCCESS;
    }
}
