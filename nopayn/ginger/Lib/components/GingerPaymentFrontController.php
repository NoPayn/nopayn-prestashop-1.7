<?php


namespace Lib\components;


class GingerPaymentFrontController extends \ModuleFrontController
{

    public $ssl = true;
    public $display_column_left = false;


    public function initContent()
    {
        parent::initContent();

        $errorMessage = $this->module->execPayment(
            $this->context->cart, $this->_getWebshopLocale()
        );

        if ($errorMessage) {
            $this->context->smarty->assign('checkout_url',
                $this->context->link->getPagelink('order')
            );

            $this->context->smarty->assign('error_message', $errorMessage);
            $this->context->smarty->assign('template', _PS_THEME_DIR_ . 'templates/page.tpl');
            $this->context->smarty->assign('shop_name', \Configuration::get('PS_SHOP_NAME'));
            $this->setTemplate('module:'.GingerPSPConfig::PSP_PREFIX.'/views/templates/front/error.tpl');
        }

    }

    /**
     * @return string
     */
    protected function _getWebshopLocale()
    {
        if ($this->context->language) {
            // Current language
            $language = $this->context->language->iso_code;
        } else {
            // Default locale language
            $language = \Configuration::get('PS_LOCALE_LANGUAGE');
        }
        return strtolower($language).'_'.strtoupper(\Configuration::get('PS_LOCALE_COUNTRY'));
    }

}
