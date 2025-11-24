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

namespace MonsieurBiz\SyliusThemeCompanionPlugin\TaskRunner;

use Exception;
use MonsieurBiz\SyliusThemeCompanionPlugin\Process\ProcessOutputAwareTrait;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\Filesystem\Filesystem;

#[AutoconfigureTag('monsieurbiz_theme_companion.task_runner')]
class CopyFileRunner implements TaskRunnerInterface
{
    use ProcessOutputAwareTrait;

    public const string IDENTIFIER = 'copy';

    public static function getIdentifier(): string
    {
        return self::IDENTIFIER;
    }

    public function process(string $input, string $output, ?array $config = null): bool
    {
        $filesystem = new Filesystem();

        try {
            $filesystem->mirror($input, $output, options: $config ?? []);
        } catch (Exception $exception) {
            $this->getProcessOutputHandler()?->handle($exception->getMessage());

            return false;
        }

        return true;
    }
}
