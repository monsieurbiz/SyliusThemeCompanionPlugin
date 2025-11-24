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

namespace MonsieurBiz\SyliusThemeCompanionPlugin\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class SwitchThemeController extends AbstractController
{
    public function __construct(
        private readonly ChannelContextInterface $channelContext,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        /** @var string $theme */
        $theme = '__none__' === $request->get('theme') ? '' : $request->get('theme');
        /** @var ChannelInterface $currentChannel */
        $currentChannel = $this->channelContext->getChannel();
        $currentChannel->setThemeName($theme);
        $this->entityManager->persist($currentChannel);
        $this->entityManager->flush();

        $referer = $request->headers->get('referer');
        if (null === $referer) {
            throw $this->createNotFoundException();
        }

        return new RedirectResponse($referer);
    }
}
