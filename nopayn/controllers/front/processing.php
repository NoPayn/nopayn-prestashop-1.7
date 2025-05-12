<?php

use Lib\components\GingerClientBuilder;

class nopaynProcessingModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();
        
        if (Tools::getValue('processing')) {
            $this->checkStatusAjax();
        }
        
        $this->context->smarty->assign(
            [
                'fallback_url' => $this->context->link->getModuleLink('nopayn', 'pending'),
                'template' => _PS_THEME_DIR_ . 'templates/page.tpl',
                'modules_dir' => _MODULE_DIR_,
                'checkout_url' => $this->context->link->getPagelink('order'),
                'validation_url' => $this->getValidationUrl()
            ]
        );
        $this->setTemplate('module:nopayn/views/templates/front/processing.tpl');
    }
    
    protected function getValidationUrl()
    {
        return $this->context->link->getModuleLink(
            'nopaynideal',
            'validation',
            [
                'id_cart' => \Tools::getValue('id_cart'),
                'id_module' => $this->module->id,
                'order_id' => \Tools::getValue('order_id')
            ]
        );
    }
    
    /**
      * @param string $orderId
      * @return null|string
      */
    public function checkOrderStatus()
    {
        $client = GingerClientBuilder::gingerBuildClient($this->module->method_id);
        $ginger_order = $client->getOrder(\Tools::getValue('order_id'));
        return $ginger_order->getStatus()->get();
    }

    /**
     * Method prepares Ajax response for processing page
     */
    public function checkStatusAjax()
    {
        $orderStatus = $this->checkOrderStatus();

        if ($orderStatus == 'processing') {
            $response = [
                'status' => $orderStatus,
                'redirect' => false
            ];
        } else {
            $response = [
                'status' => $orderStatus,
                'redirect' => true
            ];
        }

        die(json_encode($response));
    }
}

