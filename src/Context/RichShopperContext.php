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

namespace MonsieurBiz\SyliusThemeCompanionPlugin\Context;

use MonsieurBiz\SyliusThemeCompanionPlugin\Model\RichThemeInterface;
use Sylius\Bundle\ThemeBundle\Context\ThemeContextInterface;
use Sylius\Bundle\ThemeBundle\Model\ThemeInterface;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Core\Context\ShopperContext;
use Sylius\Component\Core\Context\ShopperContextInterface;
use Sylius\Component\Currency\Context\CurrencyContextInterface;
use Sylius\Component\Customer\Context\CustomerContextInterface;
use Sylius\Component\Locale\Context\LocaleContextInterface;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;

#[AsDecorator('sylius.context.shopper')]
final class RichShopperContext extends ShopperContext implements ShopperContextInterface
{
    public function __construct(
        ChannelContextInterface $channelContext,
        CurrencyContextInterface $currencyContext,
        LocaleContextInterface $localeContext,
        CustomerContextInterface $customerContext,
        private ThemeContextInterface $themeContext
    ) {
        parent::__construct($channelContext, $currencyContext, $localeContext, $customerContext);
    }

    public function getTheme(): RichThemeInterface
    {
        /** @var ThemeInterface&RichThemeInterface $theme */
        $theme = $this->themeContext->getTheme();
        $theme->setCurrentScope('twig');

        return $theme;
    }
}
