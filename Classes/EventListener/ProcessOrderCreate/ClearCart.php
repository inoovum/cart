<?php
declare(strict_types=1);
namespace Extcode\Cart\EventListener\ProcessOrderCreate;

/*
 * This file is part of the package extcode/cart.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

use Extcode\Cart\Event\ProcessOrderCreateEvent;
use Extcode\Cart\Service\SessionHandler;
use Extcode\Cart\Utility\CartUtility;
use Extcode\Cart\Utility\ParserUtility;

class ClearCart
{
    /**
     * @var CartUtility
     */
    protected $cartUtility;

    /**
     * @var ParserUtility
     */
    protected $parserUtility;

    /**
     * @var SessionHandler
     */
    protected $sessionHandler;

    public function __construct(
        CartUtility $cartUtility,
        ParserUtility $parserUtility,
        SessionHandler $sessionHandler
    ) {
        $this->cartUtility = $cartUtility;
        $this->parserUtility = $parserUtility;
        $this->sessionHandler = $sessionHandler;
    }

    public function __invoke(ProcessOrderCreateEvent $event): void
    {
        $cart = $event->getCart();
        $settings = $event->getSettings();

        $paymentId = $cart->getPayment()->getId();
        $paymentSettings = $this->parserUtility->getTypePluginSettings($settings, $cart, 'payments');

        if (intval($paymentSettings['options'][$paymentId]['preventClearCart']) != 1) {
            $cart = $this->cartUtility->getNewCart($settings);
        }

        $this->sessionHandler->write($cart, $settings['settings']['cart']['pid']);
    }
}
