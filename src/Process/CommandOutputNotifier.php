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

namespace MonsieurBiz\SyliusThemeCompanionPlugin\Process;

use Symfony\Component\Console\Style\SymfonyStyle;

readonly class CommandOutputNotifier implements ProcessOutputHandlerInterface
{
    public function __construct(private SymfonyStyle $output)
    {
    }

    public function handle(string $output): void
    {
        $this->output->writeln($output);
    }
}
