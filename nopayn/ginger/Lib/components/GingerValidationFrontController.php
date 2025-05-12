<?php


namespace Lib\components;


class GingerValidationFrontController extends \ModuleFrontController
{

    protected $gingerClient;
    private $errorReason;

    public function postProcess()
    {
        if (\Tools::getValue('processing')) {
            $this->checkStatusAjax();
        }

        try {
            switch ($this->getOrderStatus()) {
                case 'completed':
                case 'accepted':
                    $this->processCompletedStatus();
                    break;
                case 'cancelled':
                    $this->processCancelledStatus();
                    break;
                case 'processing':
                    $this->processProcessingStatus();
                    break;
                case 'new':
                case 'expired':
                case 'error':
                    $this->processErrorStatus();
                    break;
                default:
                    die("Should not happen");
            }
        } catch (\Exception $e) {
            $this->handlePostProcessException($e->getMessage());
            return;
        }
    }

    private function handlePostProcessException($errorMessage)
    {
        $this->context->smarty->assign(
            [
                'template' => _PS_THEME_DIR_ . 'templates/page.tpl',
                'checkout_url' => $this->context->link->getPagelink('order'),
                'error_message' => $errorMessage
            ]
        );
        $this->setTemplate('module:'.GingerPSPConfig::PSP_PREFIX.'/views/templates/front/error.tpl');
    }

    private function processCompletedStatus()
    {
        $this->validateOrder();
        $this->doRedirectToConfirmationPage();
    }

    /**
     * Method validates Presta order
     *
     * @param int $cart_id
     */
    private function validateOrder()
    {
        $cart_id = (int) \Tools::getValue('id_cart');
        $order = \Order::getIdByCartId((int)($cart_id));

        if (isset($cart_id) && empty($order)) { // order has not been created yet (by webhook)
            $cart = $this->context->cart;
            $customer = new \Customer($cart->id_customer);
            $total = (float) $cart->getOrderTotal(true, \Cart::BOTH);
            $currency = $this->context->currency;
            $this->module->validateOrder(
                $cart_id,
                \Configuration::get('PS_OS_PAYMENT'),
                $total,
                $this->module->displayName,
                null,
                [],
                (int) $currency->id,
                false,
                $customer->secure_key
            );
            $order = \Order::getIdByCartId((int)($cart_id));
            if (isset($order) && is_numeric($order)) {
                $this->module->updateOrderId($cart_id, $order);
            }
        }
    }

    private function doRedirectToConfirmationPage()
    {
        $cart_id = (int) \Tools::getValue('id_cart');
        \Tools::redirect(
            __PS_BASE_URI__ . 'index.php?controller=order-confirmation&id_cart=' . $cart_id
            . '&id_module=' . $this->module->id . '&id_order=' . \Order::getIdByCartId(intval($cart_id))
            . '&key=' . $this->context->customer->secure_key
        );
    }


    private function processCancelledStatus()
    {
        $this->context->smarty->assign(
            [
                'checkout_url' => $this->context->link->getPagelink('order'),
                'template' => _PS_THEME_DIR_ . 'templates/page.tpl',
                'error_message' => $this->errorReason
            ]
        );
        $this->setTemplate('module:'.GingerPSPConfig::PSP_PREFIX.'/views/templates/front/cancelled.tpl');
    }

    private function processErrorStatus()
    {
        $this->context->smarty->assign(
            [
                'template' => _PS_THEME_DIR_ . 'templates/page.tpl',
                'checkout_url' => $this->context->link->getPagelink('order'),
                'shop_name' => \Configuration::get('PS_SHOP_NAME'),
                'error_message' => $this->errorReason
            ]
        );
        $this->setTemplate('module:'.GingerPSPConfig::PSP_PREFIX.'/views/templates/front/error.tpl');
    }

    /**
     * @param string $orderId
     * @return null|string
     */
    public function getOrderStatus()
    {
        $this->gingerClient = GingerClientBuilder::gingerBuildClient($this->module->method_id);
        $ginger_order =  $this->gingerClient->getOrder(\Tools::getValue('order_id'));
        $this->errorReason = current($ginger_order['transactions'])['customer_message'] ?? '';
        return $ginger_order['status'];
    }

    private function processProcessingStatus()
    {

        if ($this->module->method_id == 'bank-transfer')//exception, because only bank-transfer has processing status after successful payment process
        {
            $this->processCompletedStatus();
        }
        if (\Tools::getValue('id_cart'))
        {
            \Tools::redirect($this->context->link->getModuleLink(
                GingerPSPConfig::PSP_PREFIX,
                'processing',
                [
                    'order_id' => \Tools::getValue('order_id'),
                    'id_cart' => \Tools::getValue('id_cart'),
                ])
            );
        }

    }

}
