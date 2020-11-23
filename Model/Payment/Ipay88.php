<?php


namespace Anzmage\Ipay88\Model\Payment;

/**
 * Class Ipay88
 *
 * @package Anzmage\Ipay88\Model\Payment
 */
class Ipay88 extends \Magento\Payment\Model\Method\AbstractMethod
{

    protected $_code = "ipay88";
    protected $_isOffline = true;

    public function isAvailable(
        \Magento\Quote\Api\Data\CartInterface $quote = null
    ) {
        return parent::isAvailable($quote);
    }
}

