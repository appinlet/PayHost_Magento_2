<?php

/*
 * Copyright (c) 2024 Payfast (Pty) Ltd
 *
 * Author: App Inlet (Pty) Ltd
 *
 * Released under the GNU General Public License
 */

namespace PayGate\PayHost\Controller\Redirect;

use Exception;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Result\PageFactory;
use PayGate\PayHost\Controller\AbstractPaygate;
use PayGate\PayHost\Model\Config;

/**
 * Responsible for loading page content.
 *
 * This is a basic controller that only loads the corresponding layout file. It may duplicate other such
 * controllers, and thus it is considered tech debt. This code duplication will be resolved in future releases.
 */
class Index extends AbstractPaygate
{
    public const CARTURL = 'checkout/cart';
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;
    /**
     * Config method type
     *
     * @var string
     */
    protected $configMethod = Config::METHOD_CODE;

    /**
     * Execute
     */
    public function execute()
    {
        $pre = __METHOD__ . " : ";

        $page_object           = $this->pageFactory->create();
        $resultRedirectFactory = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);

        try {
            $this->_initCheckout();
            $secret = $this->getConfigData('encryption_key');
            $id     = $this->getConfigData('paygate_id');
        } catch (LocalizedException $e) {
            $this->logger->error($pre . $e->getMessage());
            $this->messageManager->addExceptionMessage($e, $e->getMessage());

            return $resultRedirectFactory->setPath(self::CARTURL);
        } catch (Exception $e) {
            $this->logger->error($pre . $e->getMessage());
            $this->messageManager->addExceptionMessage($e, __('We can\'t start Paygate Checkout.'));

            return $resultRedirectFactory->setPath(self::CARTURL);
        }

        $this->order = $this->checkoutSession->getLastRealOrder();

        $block = $page_object->getLayout()
                             ->getBlock('payhost_redirect')
                             ->setPaymentFormData($this->order ?? null);

        $formData = $block->getFormData();

        if ($secret == null || $id == null) {
            $errorMessage = "We can't start Paygate Checkout: Invalid Credentials";
            $this->logger->error($errorMessage);
            $this->messageManager->addErrorMessage($errorMessage);

            return $resultRedirectFactory->setPath(self::CARTURL);
        }

        if (!$formData || ($formData['ns2StatusName'] ?? '') === 'Error') {
            $errorMessage = "We can\'t start Paygate Checkout:\n" . $formData['ns2ResultDescription'] ?? '';
            $this->logger->error($errorMessage);
            $this->messageManager->addErrorMessage($errorMessage);

            return $resultRedirectFactory->setPath(self::CARTURL);
        }

        return $page_object;
    }
}
