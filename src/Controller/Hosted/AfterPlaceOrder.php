<?php
/**
 * HiPay fullservice Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Apache 2.0 Licence
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * @copyright      Copyright (c) 2016 - HiPay
 * @license        http://www.apache.org/licenses/LICENSE-2.0 Apache 2.0 Licence
 *
 */
namespace HiPay\FullserviceMagento\Controller\Hosted;


/**
 * @deprecated
 *
 * @package HiPay\FullserviceMagento
 * @author Kassim Belghait <kassim@sirateck.com>
 * @copyright Copyright (c) 2016 - HiPay
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache 2.0 Licence
 * @link https://github.com/hipay/hipay-fullservice-sdk-magento2
 */
class AfterPlaceOrder extends \HiPay\FullserviceMagento\Controller\Fullservice
{	


    /**
     * Submit the order
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute()
    {
    	ini_set('display_errors', 1);
    	error_reporting(E_ALL | E_STRICT);
    	
        try {
        	
        	
           //Retieve last order increment id
           $order = $this->_getCheckoutSession()->getLastRealOrder();
			
           if(!$order->getId()){
            	throw new \Magento\Framework\Exception\LocalizedException(
            			__('We can\'t place the order.')
            			);
            }
           
            //Create gateway manage with order data
            $gateway = $this->_gatewayManagerFactory->create($order);
        	
            //Call fullservice api to get hosted page url
            $hppModel = $gateway->requestHostedPaymentPage();
			
            //Redirect to hosted page
            $this->getResponse()->setRedirect($hppModel->getForwardUrl());
            return;


        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addExceptionMessage(
                $e,
                $e->getMessage()
            );

        } catch (\Exception $e) {
        	$this->logger->addDebug($e->getMessage());
        	$this->messageManager->addErrorMessage($e->getMessage());
            $this->messageManager->addExceptionMessage(
                $e,
                __('We can\'t place the order.')
            );
          
        }
        $this->_redirect('checkout/cart');
    }


 
}
