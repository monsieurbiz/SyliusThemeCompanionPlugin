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

namespace MonsieurBiz\SyliusThemeCompanionPlugin\Resolver;

use MonsieurBiz\SyliusThemeCompanionPlugin\Model\RichThemeInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

readonly class CurrentThemePathResolver implements CurrentThemePathResolverInterface
{
    protected const array PATH_TO_REPLACE = [
        '{{current_theme_root_dir}}' => 'theme_companion.%s.root_dir',
        '{{current_theme_assets_path}}' => 'theme_companion.%s.assets_path',
        '{{current_theme_assets_generated_path}}' => 'theme_companion.%s.assets_generated_path',
    ];

    public function __construct(
        protected ParameterBagInterface $parameterBag
    ) {
    }

    public function resolve(RichThemeInterface $theme, string $path): string
    {
        foreach (self::PATH_TO_REPLACE as $search => $parameter) {
            if (false === str_contains($path, $search)) {
                continue;
            }

            /** @var ?string $value */
            $value = $this->parameterBag->get(\sprintf($parameter, $theme->getParameterName()));
            if (null === $value) {
                continue;
            }
            $path = str_replace($search, $value, $path);
        }

        return $path;
    }
}
