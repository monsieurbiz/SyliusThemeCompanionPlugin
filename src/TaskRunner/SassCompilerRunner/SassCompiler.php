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

namespace MonsieurBiz\SyliusThemeCompanionPlugin\TaskRunner\SassCompilerRunner;

use Symfonycasts\SassBundle\SassBuilder;

class SassCompiler extends SassBuilder
{
    private string $fileInput;

    private string $fileOutput;

    public function setFileInput(string $input): void
    {
        $this->fileInput = $input;
    }

    public function setFileOutput(string $output): void
    {
        $this->fileOutput = $output;
    }

    public function getScssCssTargets(): array
    {
        return [
            $this->fileInput . ':' . $this->fileOutput,
        ];
    }
}
