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

namespace MonsieurBiz\SyliusThemeCompanionPlugin\PackageManager;

use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

readonly class PackageManagerRepository implements PackageManagerRepositoryInterface
{
    /**
     * @param PackageManagerInterface[] $managers
     */
    public function __construct(
        #[AutowireIterator('monsieurbiz_theme_companion.package_manager', defaultIndexMethod: 'getIdentifier')]
        private iterable $managers
    ) {
    }

    /**
     * @return PackageManagerInterface[]
     */
    public function getAll(): iterable
    {
        return $this->managers;
    }

    public function get(string $identifier): ?PackageManagerInterface
    {
        return iterator_to_array($this->managers)[$identifier] ?? null;
    }
}
