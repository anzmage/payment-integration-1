<?php


namespace Anzmage\Ipay88\Block\Payment;

/**
 * Class Index
 *
 * @package Anzmage\Ipay88\Block\Test
 */
class Index extends \Magento\Framework\View\Element\Template
{

    /**
     * @var \Anzmage\Ipay88\Helper\Requery
     */

    protected $ipayHelper;

     /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * Constructor
     *
     * @param \Magento\Framework\View\Element\Template\Context  $context
     * @param array $data
     */
    public function __construct(
        \Magento\Checkout\Model\Session $checkoutSession,
        \Anzmage\Ipay88\Helper\Requery $ipayHelper,
        \Magento\Framework\View\Element\Template\Context $context,
        array $data = []
    ) {
        $this->ipayHelper = $ipayHelper;
        $this->checkoutSession = $checkoutSession;
        parent::__construct($context, $data);
    }

    /**
     * Method getIpayConfig
     *
     * @return array
     */
    public function getIpayConfig()
    {
        return $this->ipayHelper->getIpayConfig();
    }
    
    /**
     * Method getSignature
     *
     * @param string $str
     *
     * @return string
     */
    public function getSignature($str)
    {
        return base64_encode($this->hex2bin(sha1($str)));
    }
    
    /**
     * Method getLastOrder
     *
     * @return \Magento\Sales\Model\OrderFactory
     */
    public function getLastOrder()
    {
        return $this->checkoutSession->getLastRealOrder();
    }


    /**
     * Method hex2bin
     *
     */
    private function hex2bin($hexSource)
    {
        $bin = '';
        for ($i=0;$i<strlen($hexSource);$i=$i+2)
        {
            $bin .= chr(hexdec(substr($hexSource,$i,2)));
        }
        return $bin;
    }

    /**
     * Method Log
     *
     */

    public function klog($log)
    {
        return $this->ipayHelper->klog($log,'ipay88_request.log');
    }

    
}

