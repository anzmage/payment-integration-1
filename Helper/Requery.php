<?php
/**
 * @author      anzmage<anzmage@anzmage.com>
 * @copyright   Copyright © 2019 Anzmage. All rights reserved.
 */

namespace Anzmage\Ipay88\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Framework\DB\TransactionFactory;
use Magento\Framework\Message\ManagerInterface;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Store\Model\ScopeInterface;

/**
 * Class Data
 *
 * @package Anzmage\Ipay88\Helper
 */

class Requery extends AbstractHelper
{

    const MID_XML_CONFIG = 'payment/ipay88/merchantcode';
    const MKEY_XML_CONFIG = 'payment/ipay88/merchantkey';
    const GATEWAY_XML_CONFIG = 'payment/ipay88/gateway_url';
    const PAYMENT_IDS_XML_CONFIG = 'payment/ipay88/payids';
    const PAYMENT_ID = 1;

    /**
     * @var CollectionFactory
     */

    protected $collectionFactory;

    /**
     * @var Context
     */

    protected $configData;

    /**
     * @var Curl
     */

    protected $curl;

    /**
     * @var OrderRepositoryInterface
     */

    protected $orderRepository;

    /**
     * @var InvoiceService
     */

    protected $invoiceService;

    /**
     * @var TransactionFactory
     */

    protected $transactionFactory;

    /**
     * @var ManagerInterface
     */

    protected $messageManager;

    /**
     * @var InvoiceSender
     */

    protected $invoiceSender;
    

    /**
     * Data Structor
     *
     * @param Context $context
     * @param CollectionFactory $collectionFactory
     * @param Curl $curl
     * @param OrderRepositoryInterface $orderRepository
     * @param InvoiceService $invoiceService
     * @param TransactionFactory $transactionFactory
     * @param ManagerInterface $messageManager
     * @param InvoiceSender $invoiceSender
     */

    public function __construct(
        Context $context,
        CollectionFactory $collectionFactory,
        Curl $curl,
        OrderRepositoryInterface $orderRepository,
        InvoiceService $invoiceService,
        TransactionFactory $transactionFactory,
        ManagerInterface $messageManager,
        InvoiceSender $invoiceSender
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->configData = $context->getScopeConfig();
        $this->curl = $curl;
        $this->orderRepository = $orderRepository;
        $this->invoiceService = $invoiceService;
        $this->transactionFactory = $transactionFactory;
        $this->messageManager = $messageManager;
        $this->invoiceSender = $invoiceSender;
        parent::__construct($context);
    }
    /**
     * Method getPaymentIds
     *
     * @return string
     */
    public function getPaymentIds()
    {
         return $this->scopeConfig->getValue(self::PAYMENT_IDS_XML_CONFIG, ScopeInterface::SCOPE_WEBSITE);
    }

    /**
     * Method getIpayConfig
     *
     * @return array
     */
    public function getIpayConfig()
    {
         return [
            'mid' => $this->scopeConfig->getValue(self::MID_XML_CONFIG, ScopeInterface::SCOPE_WEBSITE),
            'key' => $this->scopeConfig->getValue(self::MKEY_XML_CONFIG, ScopeInterface::SCOPE_WEBSITE),
            'gateway' => $this->scopeConfig->getValue(self::GATEWAY_XML_CONFIG, ScopeInterface::SCOPE_WEBSITE),
            'pay_id' => self::PAYMENT_ID
             
         ];
    }

    /**
     * Get Ipay Order
     *
     * @return mixed
     */

    public function getIpayOrder()
    {
        $collection = $this->collectionFactory->create();

        $collection->addFieldToFilter('status', [
            'in' => [
                'pending'
            ]
        ]);

        $collection->getSelect()
            ->join(
                [ 'payment' => 'sales_order_payment' ],
                'main_table.entity_id = payment.parent_id',
                [ 'method' ]
            );
        
        $collection->addFieldToFilter('method', [ 'eq' => 'ipay88' ]);
        $collection->addFieldToFilter(
            'created_at',
            [ 'lteq' => new \Zend_Db_Expr('ADDDATE(NOW(), INTERVAL -10 MINUTE)') ]
        );
        $collection->setOrder('created_at', 'DESC');

        return $collection;
    }

    /**
     * Check Status Ipay
     *
     * @param $incrementId
     * @param $amount
     * @return mixed
     */

    public function checkStatusIpay($incrementId, $amount)
    {

        $ipayConfig = $this->getIpayConfig();

        $urlCheck = $ipayConfig['gateway'].'/epayment/enquiry.asp?MerchantCode='.$ipayConfig['mid'].
                    '&RefNo='.$incrementId.'&Amount='.$amount;

        $request = $this->curl->get($urlCheck);
        return $this->curl->getBody();
    }

    
    /**
     * Generate Invoice
     *
     * @param \Magento\Sales\Model\Order $order
     * @return $this
     */

    public function generateInvoice($orderId)
    {
        try {
            $order = $this->orderRepository->get($orderId);
            if (!$order->getId()) {
                throw new \Magento\Framework\Exception\LocalizedException(__('The order no longer exists.'));
            }
            if (!$order->canInvoice()) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('The order does not allow an invoice to be created.')
                );
            }
    
            $invoice = $this->invoiceService->prepareInvoice($order);
            if (!$invoice) {
                throw new \Magento\Framework\Exception\LocalizedException(__('We can\'t save the invoice right now.'));
            }
            if (!$invoice->getTotalQty()) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('You can\'t create an invoice without products.')
                );
            }
            $invoice->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_OFFLINE);
            $invoice->register();
            $invoice->getOrder()->setCustomerNoteNotify(false);
            $invoice->getOrder()->setIsInProcess(true);
            //$order->addStatusHistoryComment('Ipay88 Requery : Automatically INVOICED', false);
            $transactionSave = $this->transactionFactory->create()->addObject($invoice)->addObject($invoice->getOrder());
            $transactionSave->save();
    
            // send invoice emails, If you want to stop mail disable below try/catch code
            try {
                $this->invoiceSender->send($invoice);
            } catch (\Exception $e) {
                $this->messageManager->addError(__('We can\'t send the invoice email right now.'));
            }
        } catch (\Exception $e) {
            $this->messageManager->addError($e->getMessage());
        }
    
        return $invoice;
    }

    /**
     * Function Log
     *
     * @param $msg
     * @param string $filename
     */

    public function klog($msg, $filename='ipay88_requery.log')
    {
        $filename = str_replace('.log', '-'.date('Y-m-d').'.log', $filename);

        if (is_array($msg)) {
            $msg = print_r($msg, true);
        }
        
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/'.$filename);
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        $logger->info($msg);
    }
    
    /**
     * Do Requery
     *
     * @return mixed
     */
    
    public function doRequery()
    {
        $orderData = $this->getIpayOrder();

        foreach ($orderData as $order) {
            $incId = $order->getIncrementId();
            $orderId = $order->getEntityId();
            $amount = round($order->getGrandTotal(), 2);


            $checkStatus = $this->checkStatusIpay($incId, $amount);
            $saveToLog = [
                'orderId'=>$orderId,
                'incId'=>$incId,
                'amount'=>$amount,
                'checkStatus'=>$checkStatus
            ];
            
            $saveToLog = json_encode($saveToLog);

            if ($checkStatus == '00') {
                $this->klog($saveToLog, 'ipay88_requery-invoiced.log');
                $this->generateInvoice($orderId);
            } else {
                if ($checkStatus != 'Limited by per day maximum number of requery') {
                    $this->klog($saveToLog);
                }
            
                /* do the cancel if payment fail */
                if (
                    ($checkStatus == 'Payment Fail') ||
                    ($checkStatus == 'Record not found')
                ) {
                    if ($order->canCancel()) {
                        try {
                            $order->addStatusHistoryComment('Ipay88 Status is '.$checkStatus.', Requery : Automatically CANCELED', false);
                            $order->cancel()->save();
                        } catch (\Exception $xx) {
                            $this->klog($saveToLog . ' ERROR : '.$xx->getMessage(), 'ipay88_requery-invoiced.log');
                        }
                    }
                }
            }
        }
    }
}
