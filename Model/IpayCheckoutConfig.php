<?php

namespace Anzmage\Ipay88\Model;

use Magento\Checkout\Model\ConfigProviderInterface;

class IpayCheckoutConfig implements ConfigProviderInterface
{
    /**
     * @var \Anzmage\Ipay88\Helper\Requery
     */
    protected $ipayHelper;

    /**
     * Constructor
     *
     * @param Anzmage\Ipay88\Helper\Requery $ipayHelper
     */
    public function __construct(
        \Anzmage\Ipay88\Helper\Requery $ipayHelper
    ) {
        $this->ipayHelper = $ipayHelper;
    }

    /**
     * Method getConfig
     *
     * @return void
     */
    public function getConfig()
    {
        $paymentIds = $this->ipayHelper->getPaymentIds();
        $paymentIds = explode(',',$paymentIds); 
        $listPayments = [];
        foreach ($paymentIds as $payId) {
          $payment = explode('|',$payId);
          $listPayments[] = [
            'title'=>@$payment[0],
            'ipay_id'=>@$payment[1],
            'payment_code'=>@$payment[2]
          ];
        }

        return ['ipay_channel'=>$listPayments];
    }
}
