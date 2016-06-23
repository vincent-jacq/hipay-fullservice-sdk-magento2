<?php
/*
 * HiPay fullservice SDK
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the MIT License
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/mit-license.php
 *
 * @copyright      Copyright (c) 2016 - HiPay
 * @license        http://opensource.org/licenses/mit-license.php MIT License
 *
 */
namespace HiPay\FullserviceMagento\Model;

use Magento\Payment\Model\Method\AbstractMethod;
use HiPay\FullserviceMagento\Model\Gateway\Factory as ManagerFactory;
use Magento\Payment\Model\InfoInterface;
use HiPay\Fullservice\Enum\Transaction\TransactionState;
use Magento\Framework\Exception\LocalizedException;


/**
 *
 * @author kassim
 *        
 */
abstract class FullserviceMethod extends AbstractMethod {
	
	/**
	 * Payment Method feature
	 *
	 * @var bool
	 */
	protected $_isGateway = true;
	
	/**
	 * Payment Method feature
	 *
	 * @var bool
	 */
	protected $_canAuthorize = true;
	
	/**
	 * Payment Method feature
	 *
	 * @var bool
	 */
	protected $_canCapture = true;
	
	/**
	 * Payment Method feature
	 *
	 * @var bool
	 */
	protected $_canCapturePartial = true;
	
	/**
	 * Payment Method feature
	 *
	 * @var bool
	 */
	protected $_canCaptureOnce = false;
	
	/**
	 * Payment Method feature
	 *
	 * @var bool
	 */
	protected $_canRefund = true;
	
	/**
	 * Payment Method feature
	 *
	 * @var bool
	 */
	protected $_canRefundInvoicePartial = true;
	
	/**
	 * Payment Method feature
	 *
	 * @var bool
	 */
	protected $_isInitializeNeeded = false;
	
	/**
	 * Payment Method feature
	 *
	 * @var bool
	 */
	protected $_canReviewPayment = true;
	
	/**
	 * Payment Method feature
	 *
	 * @var bool
	 */
	protected $_canUseInternal = false;

	
	/**
	 * Fields that should be replaced in debug with '***'
	 *
	 * @var array
	 */
	protected $_debugReplacePrivateDataKeys = [];
	
	/**
	 * 
	 * @var ManagerFactory $_gatewayManagerFactory
	 */
	protected $_gatewayManagerFactory;
	
	/**
	 * Url Builder
	 *
	 * @var \Magento\Framework\Url
	 */
	protected $urlBuilder;
	
	/**
	 * @var string[] keys to import in payment additionnal informations
	 */
	protected $_additionalInformationKeys = ['card_token','create_oneclick','eci','cc_type'];
	
	/**
	 * 
	 * @var \HiPay\FullserviceMagento\Model\Email\Sender\FraudAcceptSender $fraudAcceptSender
	 */
	protected $fraudAcceptSender;
	
	/**
	 *
	 * @var \HiPay\FullserviceMagento\Model\Email\Sender\FraudDenySender $fraudDenySender
	 */
	protected $fraudDenySender;
	
	/**
	 *
	 * @var \HiPay\FullserviceMagento\Model\Config $_hipayConfig
	 */
	protected $_hipayConfig;
	
	const SLEEP_TIME = 5;
	
	/**
	 * 
	 * @param \Magento\Framework\Model\Context $context
	 * @param \Magento\Framework\Registry $registry
	 * @param \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory
	 * @param \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory
	 * @param \Magento\Payment\Helper\Data $paymentData
	 * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
	 * @param \Magento\Payment\Model\Method\Logger $logger
	 * @param ManagerFactory $gatewayManagerFactory
	 * @param \Magento\Framework\Url $urlBuilder
	 * @param \HiPay\FullserviceMagento\Model\Email\Sender\FraudDenySender $fraudDenySender
	 * @param \HiPay\FullserviceMagento\Model\Email\Sender\FraudAcceptSender $fraudAcceptSender
	 * @param \HiPay\FullserviceMagento\Model\Config\Factory $configFactory
	 * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource
	 * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
	 * @param array $data
	 */
	public function __construct(
			\Magento\Framework\Model\Context $context,
	        \Magento\Framework\Registry $registry,
	        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
	        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
	        \Magento\Payment\Helper\Data $paymentData,
	        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
	        \Magento\Payment\Model\Method\Logger $logger,
			ManagerFactory $gatewayManagerFactory,
			\Magento\Framework\Url $urlBuilder,
			\HiPay\FullserviceMagento\Model\Email\Sender\FraudDenySender $fraudDenySender,
			\HiPay\FullserviceMagento\Model\Email\Sender\FraudAcceptSender $fraudAcceptSender,
			\HiPay\FullserviceMagento\Model\Config\Factory $configFactory,
	        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
	        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
			array $data = []){
	
				parent::__construct($context, $registry, $extensionFactory, $customAttributeFactory, $paymentData, $scopeConfig, $logger,$resource,$resourceCollection,$data);
				
				$this->_gatewayManagerFactory = $gatewayManagerFactory;
				$this->_debugReplacePrivateDataKeys = array('token','cardtoken','card_number','cvc');
				$this->urlBuilder = $urlBuilder;
				$this->fraudAcceptSender = $fraudAcceptSender;
				$this->fraudDenySender = $fraudDenySender;
				
				$this->_hipayConfig = $configFactory->create(['params'=>['methodCode'=>$this->getCode()]]);
	}
	
	/**
	 * Assign data to info model instance
	 *
	 * @param array|\Magento\Framework\DataObject $data
	 * @return $this
	 * @throws \Magento\Framework\Exception\LocalizedException
	 * @api
	 */
	public function assignData(\Magento\Framework\DataObject $data)
	{
		if (!$data instanceof \Magento\Framework\DataObject) {
			$data = new \Magento\Framework\DataObject($data);
		}

		$this->getInfoInstance()->addData($data->getData());
		
		$this->_assignAdditionalInformation($data);
		
		return $this;
	}
	
	/**
	 * Wait for notification
	 */
	protected function sleep(){
		sleep(self::SLEEP_TIME);
	}
	
	protected function _assignAdditionalInformation(\Magento\Framework\DataObject $data){
		
		$info = $this->getInfoInstance();
		foreach ($this->getAddtionalInformationKeys() as $key) {	
			if(!is_null($data->getData($key))){			
				$info->setAdditionalInformation($key,$data->getData($key));
			}
		}
		
		return $this;
	}
	
	protected function getAddtionalInformationKeys(){
		return $this->_additionalInformationKeys;
	}
	
	/**
	 * Check method for processing with base currency
	 *
	 * @param string $currencyCode
	 * @return bool
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public function canUseForCurrency($currencyCode)
	{
		if($this->getConfigData('allowed_currencies') != ""){
			return in_array($currencyCode,explode(",",$this->getConfigData('allowed_currencies')));
		}
		return true;
	}
	
	 /**
     * Is active
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isActive($storeId = null)
    {
        return (bool)(int)$this->getConfigData('active', $storeId) && $this->_hipayConfig->hasCredentials();
    }
    
   
	
	/**
	 * Mapper from HiPay-specific payment actions to Magento payment actions
	 *
	 * @return string|null
	 */
	public function getConfigPaymentAction()
	{
		$action = $this->getConfigData('payment_action');
		switch ($action) {
			case \HiPay\FullserviceMagento\Model\System\Config\Source\PaymentActions::PAYMENT_ACTION_AUTH:
				return \Magento\Payment\Model\Method\AbstractMethod::ACTION_AUTHORIZE;
			case \HiPay\FullserviceMagento\Model\System\Config\Source\PaymentActions::PAYMENT_ACTION_SALE:
				return \Magento\Payment\Model\Method\AbstractMethod::ACTION_AUTHORIZE_CAPTURE;
		}
		return $action;
	}
	
	public function place(\Magento\Payment\Model\InfoInterface $payment){
	
		try {
	
			$response = $this->getGatewayManager($payment->getOrder())->requestNewOrder();
				
			$successUrl =  $this->urlBuilder->getUrl('checkout/onepage/success',['_secure'=>true]);
			$pendingUrl = $this->urlBuilder->getUrl('checkout/cart',['_secure'=>true]);;
			$forwardUrl = $response->getForwardUrl();;
			$failUrl = $this->urlBuilder->getUrl('checkout/onepage/failure',['_secure'=>true]);
			$redirectUrl = $successUrl;
			switch($response->getState()){
				case TransactionState::COMPLETED:
					//redirectUrl is success by default
					break;
				case TransactionState::PENDING:
					$redirectUrl = $pendingUrl;
					break;
				case TransactionState::FORWARDING:
					$redirectUrl = $forwardUrl;
					break;
				case TransactionState::DECLINED:
					$redirectUrl = $failUrl;
					break;
				case TransactionState::ERROR:
					throw new LocalizedException(__('There was an error request new transaction: %1.', $response->getReason()));
				default:
					$redirectUrl = $failUrl;
			}
				
			//always in pending, because only notification can change order/transaction statues
			$payment->getOrder()->setState(\Magento\Sales\Model\Order::STATE_NEW);
			$payment->getOrder()->setStatus($this->getConfigData('order_status'));
			$payment->setAdditionalInformation('redirectUrl',$redirectUrl);
	
		} catch (\Exception $e) {
	
			$this->_logger->critical($e);
			throw new LocalizedException(__('There was an error request new transaction: %1.', $e->getMessage()));
		}
		return $this;
	}
	
	
	/**
	 * Capture payment method
	 *
	 * @param \Magento\Framework\DataObject|InfoInterface $payment
	 * @param float $amount
	 * @return $this
	 * @throws \Magento\Framework\Exception\LocalizedException
	 * @api
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount)
	{
		parent::capture($payment, $amount);
		try {
			/** @var \Magento\Sales\Model\Order\Payment $payment */
			if ($payment->getLastTransId()) {  //Is not the first transaction
				// As we already have a transaction reference, we can request a capture operation.
				$this->getGatewayManager($payment->getOrder())->requestOperationCapture($amount);
				//wait for notification to set correct data to order
				$this->sleep();
	
			} else { //Ok, it's the first transaction, so we request a new order
				$this->place($payment);
	
			}
	
		} catch (LocalizedException $e) {
			throw $e;
		}
		catch (\Exception $e) {
			$this->_logger->critical($e);
			throw new LocalizedException(__('There was an error capturing the transaction: %1.', $e->getMessage()));
		}
	
	
		return $this;
	}
	
	
	/**
	 * Refund specified amount for payment
	 *
	 * @param \Magento\Framework\DataObject|InfoInterface $payment
	 * @param float $amount
	 * @return $this
	 * @throws \Magento\Framework\Exception\LocalizedException
	 * @api
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount){
		parent::refund($payment, $amount);
		$this->getGatewayManager($payment->getOrder())->requestOperationRefund($amount);
		//wait for notification to set correct data to order
		$this->sleep();
		return $this;
	}
	
	/**
	 * Attempt to accept a payment that us under review
	 *
	 * @param InfoInterface $payment
	 * @return false
	 * @throws \Magento\Framework\Exception\LocalizedException
	 * @api
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public function acceptPayment(InfoInterface $payment){
		parent::acceptPayment($payment);
		$this->getGatewayManager($payment->getOrder())->requestOperationAcceptChallenge();
		$this->fraudAcceptSender->send($payment->getOrder());
		//wait for notification to set correct data to order
		$this->sleep();
		return false;
	}
	
	
	/**
	 * Attempt to deny a payment that us under review
	 *
	 * @param InfoInterface $payment
	 * @return false
	 * @throws \Magento\Framework\Exception\LocalizedException
	 * @api
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public function denyPayment(InfoInterface $payment){
		parent::denyPayment($payment);
		$this->getGatewayManager($payment->getOrder())->requestOperationDenyChallenge();
		$this->fraudDenySender->send($payment->getOrder());
		//wait for notification to set correct data to order
		$this->sleep();
		return false;
	}
	
	/**
	 * 
	 * @param \Magento\Sales\Model\Order $order
	 * @return \HiPay\FullserviceMagento\Model\Gateway\Manager
	 */
	public function getGatewayManager($order){
		return $this->_gatewayManagerFactory->create($order);
	}
	
	
}
