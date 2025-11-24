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

namespace MonsieurBiz\SyliusThemeCompanionPlugin\DependencyInjection\Finder;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Finder\Exception\DirectoryNotFoundException;
use Symfony\Component\Finder\Finder;

readonly class LocalThemeFinder extends AbstractThemeFinder implements ThemeFinderInterface
{
    public function __construct(
        #[Autowire(param: 'kernel.project_dir')]
        private string $projectDir
    ) {
    }

    public function getThemes(): array
    {
        $themes = [];
        foreach ($this->getLocalThemesComposerFile() as $file) {
            /** @var array $composerJson */
            $composerJson = json_decode(file_get_contents($file->getRealPath()) ?: '', true);
            if (true !== ($composerJson['extra']['sylius-theme'][self::NEED_COMPANION_FLAG] ?? false)) {
                continue;
            }

            $themes[$composerJson['name']] = $this->buildThemeData(
                $composerJson,
                Path::getDirectory($file->getPathname())
            );
        }

        return $themes;
    }

    private function getLocalThemesComposerFile(): iterable
    {
        $finder = new Finder();

        try {
            return $finder
                ->files()
                ->in(Path::join($this->projectDir, '/themes/*/'))
                ->name('composer.json')
                ->getIterator()
            ;
        } catch (DirectoryNotFoundException) {
            return [];
        }
    }
}
