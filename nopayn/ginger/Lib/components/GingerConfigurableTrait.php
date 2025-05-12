<?php

namespace Lib\components;

use Lib\interfaces\GingerCountryValidation;
use Lib\interfaces\GingerIPValidation;

trait GingerConfigurableTrait
{
    public $_postErrors;

    public function getContent()
    {

        if (\Tools::isSubmit('btnSubmit')) {

            $this->postValidation();

            if (isset($this->_postErrors))
            {
                foreach ($this->_postErrors as $error) $this->_html .= $this->displayError($error);
            }

            $this->postProcess();

        } else {
            $this->_html .= '<br />';
        }

        $this->_html .= $this->displayginger();
        $this->_html .= $this->renderForm();

        return $this->_html;

    }

    private function postProcess()
    {

        $templateForVariable =  'GINGER_'.strtoupper(str_replace('-','',$this->method_id));
        if (\Tools::isSubmit('btnSubmit'))
        {
            if ($this instanceof GingerIPValidation)
            {
                \Configuration::updateValue($templateForVariable.'_SHOW_FOR_IP', trim(\Tools::getValue($templateForVariable.'_SHOW_FOR_IP')));
            }

            if ($this instanceof GingerCountryValidation)
            {
                \Configuration::updateValue($templateForVariable.'_COUNTRY_ACCESS', trim(\Tools::getValue($templateForVariable.'_COUNTRY_ACCESS')));
            }

            if ($this->method_id == 'credit-card')
            {
                \Configuration::updateValue($templateForVariable.'_CAPTURE_MANUAL', trim(\Tools::getValue($templateForVariable.'_CAPTURE_MANUAL')));
            }

            if ($this->method_id == GingerPSPConfig::PSP_PREFIX)
            {
                \Configuration::updateValue('GINGER_API_KEY', trim(\Tools::getValue('GINGER_API_KEY')));
                \Configuration::updateValue('GINGER_BUNDLE_CA', \Tools::getValue('GINGER_BUNDLE_CA'));

                if (array_key_exists('afterpay',GingerPSPConfig::GINGER_PSP_LABELS)){
                    \Configuration::updateValue('GINGER_AFTERPAY_TEST_API_KEY', trim(\Tools::getValue('GINGER_AFTERPAY_TEST_API_KEY')));
                }
                if (array_key_exists('klarna-pay-later',GingerPSPConfig::GINGER_PSP_LABELS)){
                    \Configuration::updateValue('GINGER_KLARNAPAYLATER_TEST_API_KEY', trim(\Tools::getValue('GINGER_KLARNAPAYLATER_TEST_API_KEY')));
                }
            }
        }
        $this->_html .= $this->displayConfirmation($this->trans('Settings updated',[],'Modules.Nopayn.Admin'));
    }

    protected function displayginger()
    {
        return $this->display(__PS_BASE_URI__.'modules/' .$this->name.'/'.$this->name.'.php', 'infos.tpl');
    }

    protected function postValidation()
    {
        if (\Tools::isSubmit('btnSubmit'))
        {
            if (!\Configuration::get('GINGER_API_KEY') && $this->name != GingerPSPConfig::PSP_PREFIX)
            {
                $this->_postErrors[] = $this->trans('API key should be set.',[],'Modules.Nopayn.Admin');
            }
        }
    }

    public function renderForm()
    {
        $fields_form = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->trans('%label% Settings',['%label%'=>$this->trans(GingerPSPConfig::PSP_LABEL, [], 'Modules.Nopayn.Admin')],'Modules.Nopayn.Admin'),
                    'icon' => 'icon-envelope'
                ),
                'input' => ($this->method_id == GingerPSPConfig::PSP_PREFIX) ? $this->getLibraryFields() : $this->getPaymentMethodsFields(),
                'submit' => array(
                    'title' => $this->trans('Save', [], 'Modules.Nopayn.Admin'),
                )
            ),
        );

        $helper = new \HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $lang = new \Language((int) \Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $helper->allow_employee_form_lang = \Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? \Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
        $this->fields_form = array();
        $helper->id = (int) \Tools::getValue('id_carrier');
        $helper->identifier = $this->identifier;
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'btnSubmit';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false).'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = \Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = array(
            'fields_value' => ($this->method_id == GingerPSPConfig::PSP_PREFIX) ? $this->getLibraryFieldsValue() : $this->getPaymentMethodsFieldsValue(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id
        );

        return $helper->generateForm(array($fields_form));

    }

    public function getLibraryFields()
    {
        $fields = [
            [
                'type' => 'checkbox',
                'name' => 'GINGER',
                'desc' => $this->trans('Resolves issue when curl.cacert path is not set in PHP.ini', [], 'Modules.Nopayn.Admin'),
                'values' => array(
                    'query' => array(
                        array(
                            'id' => 'BUNDLE_CA',
                            'name' => $this->trans('Use cURL CA bundle', [], 'Modules.Nopayn.Admin'),
                            'val' => '1'
                        ),
                    ),
                    'id' => 'id',
                    'name' => 'name'
                )
            ],
            [
                'type' => 'text',
                'label' => $this->trans('API Key', [], 'Modules.Nopayn.Admin'),
                'name' => 'GINGER_API_KEY',
                'required' => true
            ],
        ];
        if (array_key_exists('afterpay', GingerPSPConfig::GINGER_PSP_LABELS)){
            $fields[] = [
                'type' => 'text',
                'label' => $this->trans('Afterpay Test API Key', [], 'Modules.Nopayn.Admin'),
                'name' => 'GINGER_AFTERPAY_TEST_API_KEY',
                'required' => false,
                'desc' => $this->trans('The Test API Key is Applicable only for Afterpay. Remove when not used.',[], 'Modules.Nopayn.Admin')
            ];
        }
        if (array_key_exists('klarna-pay-later', GingerPSPConfig::GINGER_PSP_LABELS)){
            $fields[] =             [
                'type' => 'text',
                'label' => $this->trans('Klarna Test API Key', [], 'Modules.Nopayn.Admin'),
                'name' => 'GINGER_KLARNAPAYLATER_TEST_API_KEY',
                'required' => false,
                'desc' => $this->trans('The Test API Key is Applicable only for Klarna. Remove when not used.',[], 'Modules.Nopayn.Admin')
            ];
        }
        return $fields;
    }

    public function getPaymentMethodsFields()
    {
        $countryAccessValidationVar = 'GINGER_'.strtoupper(str_replace('-','',$this->method_id)).'_COUNTRY_ACCESS';
        $ipValidationVar = 'GINGER_'.strtoupper(str_replace('-','',$this->method_id)).'_SHOW_FOR_IP';

        $methodLabel = $this->trans(GingerPSPConfig::GINGER_PSP_LABELS[$this->method_id], [], 'Modules.Nopayn.Admin');

        return [
            ($this instanceof GingerIPValidation) ? [
                'type' => 'text',
                'label' => $this->trans('IP address(es) for testing.',[], 'Modules.Nopayn.Admin'),
                'name' => $ipValidationVar,
                'required' => true,
                'desc' => $this->trans('You can specify specific IP addresses for which %method% is visible, for example if you want to test %method% you can type IP addresses as 128.0.0.1, 255.255.255.255. If you fill in nothing, then, %method% is visible to all IP addresses.',
                    ['%method%'=> $methodLabel],'Modules.Nopayn.Admin'
                ),
            ] : null,
            ($this instanceof GingerCountryValidation) ? [
                'type' => 'text',
                'label' => $this->trans('Countries available for %method%.',['%method%'=>$methodLabel], 'Modules.Nopayn.Admin'),
                'name' => $countryAccessValidationVar,
                'required' => true,
                'desc' => $this->trans('To allow %method% to be used for any other country just add its country code (in ISO 2 standard) to the "Countries available for %method%" field. Example: BE, NL, FR If field is empty then %method% will be available for all countries.',
                    ['%method%'=> $methodLabel],'Modules.Nopayn.Admin'
                ),
            ] : null,
            ($this->method_id == 'credit-card') ? [
                'type' => 'checkbox',
                'label' => $this->trans('Capture on complete', [], 'Modules.Nopayn.Admin'),
                'name' => 'GINGER',
                'values' => array(
                    'query' => array(
                        array(
                            'id' => 'CREDITCARD_CAPTURE_MANUAL',
                            'name' => $this->trans('Captures payment when an order is marked as complete', [], 'Modules.Nopayn.Admin'),
                            'val' => '1'
                        ),
                    ),
                    'id' => 'id',
                    'name' => 'name'
                )
            ] : null,
        ];
    }

    public function getLibraryFieldsValue()
    {
        $values = [
            'GINGER_API_KEY' => \Tools::getValue('GINGER_API_KEY', \Configuration::get('GINGER_API_KEY')),
            'GINGER_BUNDLE_CA' => \Tools::getValue('GINGER_BUNDLE_CA', \Configuration::get('GINGER_BUNDLE_CA')),
        ];

        if (array_key_exists('klarna-pay-later', GingerPSPConfig::GINGER_PSP_LABELS)) {
            $values['GINGER_KLARNAPAYLATER_TEST_API_KEY'] = \Tools::getValue('GINGER_KLARNAPAYLATER_TEST_API_KEY', \Configuration::get('GINGER_KLARNAPAYLATER_TEST_API_KEY'));
        }

        if (array_key_exists('afterpay', GingerPSPConfig::GINGER_PSP_LABELS)) {
            $values['GINGER_AFTERPAY_TEST_API_KEY'] = \Tools::getValue('GINGER_AFTERPAY_TEST_API_KEY', \Configuration::get('GINGER_AFTERPAY_TEST_API_KEY'));
        }

        return $values;
    }
    public function getPaymentMethodsFieldsValue()
    {
        $countryAccessValidationVar = 'GINGER_'.strtoupper(str_replace('-','',$this->method_id)).'_COUNTRY_ACCESS';
        $ipValidationVar = 'GINGER_'.strtoupper(str_replace('-','',$this->method_id)).'_SHOW_FOR_IP';
        $captureManualVar = 'GINGER_'.strtoupper(str_replace('-','',$this->method_id)).'_CAPTURE_MANUAL';

        return [
            $ipValidationVar => ($this instanceof GingerIPValidation) ? \Tools::getValue($ipValidationVar, \Configuration::get($ipValidationVar)) : null,
            $countryAccessValidationVar => ($this instanceof GingerCountryValidation) ? \Tools::getValue($countryAccessValidationVar, \Configuration::get($countryAccessValidationVar)) : null,
            $captureManualVar => ($this->method_id == 'credit-card' ) ? \Tools::getValue($captureManualVar, \Configuration::get($captureManualVar)) : null

        ];
    }

}