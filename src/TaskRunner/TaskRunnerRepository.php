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

use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

readonly class TaskRunnerRepository implements TaskRunnerRepositoryInterface
{
    /**
     * @param TaskRunnerInterface[] $runners
     */
    public function __construct(
        #[AutowireIterator('monsieurbiz_theme_companion.task_runner', defaultIndexMethod: 'getIdentifier')]
        private iterable $runners
    ) {
    }

    public function getAll(): iterable
    {
        return $this->runners;
    }

    public function get(string $identifier): ?TaskRunnerInterface
    {
        return iterator_to_array($this->runners)[$identifier] ?? null;
    }
}
