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
use MonsieurBiz\SyliusThemeCompanionPlugin\PackageManager\PackageManagerRepositoryInterface;
use MonsieurBiz\SyliusThemeCompanionPlugin\Resolver\CurrentThemePathResolverInterface;
use MonsieurBiz\SyliusThemeCompanionPlugin\TaskRunner\TaskRunnerRepositoryInterface;
use Sylius\Bundle\ThemeBundle\Repository\ThemeRepositoryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

abstract class AbstractThemeCommand extends Command
{
    public function __construct(
        protected readonly ThemeRepositoryInterface $themeRepository,
        protected readonly TaskRunnerRepositoryInterface $assetBuilderRepository,
        protected readonly PackageManagerRepositoryInterface $packageManagerRepository,
        #[Autowire(param: 'kernel.project_dir')]
        protected string $projectDir,
        protected CurrentThemePathResolverInterface $currentThemePathResolver,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('all', 'a', InputOption::VALUE_NONE, 'Work with all themes');
        $this->addArgument('themes', InputArgument::IS_ARRAY, 'Themes to work with');
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $ioStyle = new SymfonyStyle($input, $output);
        $ioStyle->title($this->getDefaultDescription() ?? '');

        if (false === $input->getOption('all') && 0 === \count((array) $input->getArgument('themes'))) {
            $ioStyle->error('You must provide at least one theme or use --all option.');

            return Command::FAILURE;
        }

        /** @var RichThemeInterface[] $themes */
        $themes = $input->getOption('all')
            ? $this->themeRepository->findAll()
            : array_map(
                fn (string $theme): ?RichThemeInterface => $this->themeRepository->findOneByName($theme), /** @phpstan-ignore-line  */
                (array) $input->getArgument('themes')
            );

        if (empty($themes)) {
            $ioStyle->error('No themes found.');

            return Command::FAILURE;
        }

        $this->beforeExecuteForTheme($ioStyle);
        foreach ($themes as $theme) {
            $this->executeForTheme($ioStyle, $theme);
        }
        $this->afterExecuteForTheme($ioStyle);

        return Command::SUCCESS;
    }

    abstract protected function executeForTheme(SymfonyStyle $ioStyle, RichThemeInterface $theme): void;

    abstract protected function getTitle(): string;

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function beforeExecuteForTheme(SymfonyStyle $ioStyle): void
    {
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function afterExecuteForTheme(SymfonyStyle $ioStyle): void
    {
    }
}
