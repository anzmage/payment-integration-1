<?php

namespace Anzmage\Ipay88\Controller\Redirect;

use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Sales\Model\Order;

/**
 * Class ReturnCustomer
 *
 * @package Anzmage\Ipay88\Controller\Redirect
 */
class ReturnCustomer extends \Magento\Framework\App\Action\Action implements CsrfAwareActionInterface
{

    protected $resultPageFactory;

    protected $requeryHelper;

    protected $order;

    protected $checkoutSession;

    protected $customerSession;

    


    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Action\Context  $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Anzmage\Ipay88\Helper\Requery $requeryHelper,
        \Magento\Sales\Api\Data\OrderInterface $order,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Customer\Model\Session $customerSession
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->customerSession = $customerSession;
        $this->resultPageFactory = $resultPageFactory;
        $this->requeryHelper = $requeryHelper;
        $this->order = $order;
        parent::__construct($context);
    }

    /** 
     * @inheritDoc
     */
    public function createCsrfValidationException(
        RequestInterface $request 
    ): ?InvalidRequestException {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

    /**
     * Execute view action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $post = $this->getRequest()->getPostValue();

        $this->requeryHelper->klog($post,'ipay88_incoming.log');
        $orderData = $this->order->loadByIncrementId($post['RefNo']);

        if($custId = $orderData->getCustomerId())
        {
            $this->customerSession->loginById($custId);
            $this->checkoutSession->loadCustomerQuote();
        }

        if($orderData->getId())
        {
            $this->checkoutSession->setLastOrderId($orderData->getId())->setLastRealOrderId($orderData->getIncrementId());
            $payment = $orderData->getPayment();
            $payment->setAdditionalInformation($post)->save();
        }
        

        if($post['Status'] == 1){
            $orderId = $orderData->getId();	
            if(!$orderId){
                $orderData->setStatus(Order::STATE_CANCELED);
                $orderData->setState(Order::STATE_CANCELED);
                $orderData->save();
                $this->_redirect('checkout/onepage/failure');
                return;
            }
            
            $this->requeryHelper->generateInvoice($orderId);
            $this->_redirect('checkout/onepage/success');

        }else{
            $orderData->setStatus(Order::STATE_CANCELED);
            $orderData->setState(Order::STATE_CANCELED);
            $orderData->save();            
            $this->_redirect('checkout/onepage/failure');
        }

        return;
    }
}

