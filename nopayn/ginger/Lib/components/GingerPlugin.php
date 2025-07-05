<?php
namespace Lib\components;

use Address;
use Db;
use Exception;
use Lib\interfaces\GingerCapturable;
use Lib\interfaces\GingerCountryValidation;
use Lib\interfaces\GingerCustomFieldsOnCheckout;
use Lib\interfaces\GingerIdentificationPay;
use Lib\interfaces\GingerIPValidation;
use Model\Ginger;
use Model\GingerGateway;
use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once(\_PS_MODULE_DIR_ . 'nopayn/ginger/vendor/autoload.php');

class GingerPlugin extends \PaymentModule
{

    protected $orderBuilder;
    protected $gingerClient;
    public $method_id;
    protected $extra_mail_vars;
    protected $_html = '';

    protected $capturableMethods = ['klarnadirectdebit','afterpay','klarnapaylater'];

    public function __construct()
    {
        $this->label = $this->trans(GingerPSPConfig::PSP_LABEL, [], 'Modules.Nopayn.Admin');
        $this->method_name = $this->trans(GingerPSPConfig::GINGER_PSP_LABELS[$this->method_id], [], 'Modules.Nopayn.Admin');

        $this->displayName = $this->trans('%label% %method%', ['%label%' => $this->label, '%method%'=>$this->method_name], 'Modules.Nopayn.Admin');
        $this->description = $this->trans('Accept payments for your products using %method%', ['%method%'=>$this->method_name], 'Modules.Nopayn.Admin');
        $this->tab = 'payments_gateways';
        $this->version = "1.0.0";
        $this->author = 'Ginger Payments';
        $this->controllers = array('payment', 'validation');
        $this->is_eu_compatible = 1;
        $this->currencies = true;
        $this->currencies_mode = 'checkbox';
        $this->bootstrap = true;
        parent::__construct();
		
        try {
            $this->gingerClient = GingerClientBuilder::gingerBuildClient($this->method_id);
        }catch (\Exception $exception) {
            $this->warning = $exception->getMessage();
        }

        $this->confirmUninstall = $this->trans('Are you sure about removing these details?',[], 'Modules.Nopayn.Admin');



        if (!count(\Currency::checkPaymentCurrencies($this->id)))
        {
            $this->warning = $this->trans('No currency has been set for this module.',[], 'Modules.Nopayn.Admin');
        }
    }
    public function getGingerClient()
    {
        return $this->gingerClient;
    }

    public function install()
    {
        if (!parent::install()) return false;

        if ($this->name == GingerPSPConfig::PSP_PREFIX)
        {
            if (!$this->createTables()) return false; //Create table in db

            if (!$this->createOrderState()) return false;

            /**
             * Hook for partial refund
             * TODO: PLUG-856: the hook doesn't work on prestashop version 1.7.6.8, but works on version 1.7.7.6 (tested in docker)
             */
            if (!$this->registerHook('OrderSlip')) return false;

            return true;
        }

        if (!$this->registerHook('paymentOptions') || !$this->registerHook('paymentReturn'))
        {
            return false;
        }

        if($this instanceof GingerCustomFieldsOnCheckout)
        {
            if (!$this->registerHook('header')) return false;
        }

        if (!$this->registerHook('actionOrderStatusUpdate')) return false;

        if($this instanceof GingerCountryValidation)
        {
            \Configuration::updateValue('GINGER_'.strtoupper(str_replace('-','',$this->method_id)).'_COUNTRY_ACCESS', trim('NL, BE'));
        }

        return true;
    }

    public function uninstall()
    {

        if (!parent::uninstall())
        {
            return false;
        }

        if ($this->name == GingerPSPConfig::PSP_PREFIX)
        {
            if (!\Configuration::deleteByName('GINGER_API_KEY')) return false;

            return true;

        }

        $templateForVariable =  'GINGER_'.strtoupper(str_replace('-','',$this->method_id));

        if($this instanceof GingerIPValidation)
        {
            \Configuration::deleteByName($templateForVariable.'_SHOW_FOR_IP');
        }

        if($this instanceof GingerCountryValidation)
        {
            \Configuration::deleteByName($templateForVariable.'_COUNTRY_ACCESS');
        }

        return true;
    }


    /**
     * @param \Cart $cart
     * @return bool
     */
    protected function checkCurrency(\Cart $cart)
    {
        $currencyOrder = new \Currency($cart->id_currency);
        $currenciesModule = $this->getCurrency($cart->id_currency);

        if (is_array($currenciesModule))
        {
            foreach ($currenciesModule as $currencyModule)
            {
                if ($currencyOrder->id == $currencyModule['id_currency'])
                {
                    return $this->validateCurrency($currencyOrder->iso_code);
                }
            }
        }

        return false;
    }

    /**
     * update order with presta order id
     *
     * @param $GingerOrderId
     * @param $PSOrderId
     * @param $amount
     */
    public function updateGingerOrder($GingerOrderId, $PSOrderId, $amount)
    {
        $orderData = [
            'amount' => $amount,
            'currency' => $this->orderBuilder->getOrderCurrency(),
            'merchant_order_id' => (string) $PSOrderId
        ];
        $this->gingerClient->updateOrder($GingerOrderId, $orderData);
    }

    public function hookPaymentOptions($params)
    {

        if (!$this->active)
        {
            return;
        }

        if (!$this->checkCurrency($params['cart']))
        {
            return;
        }


        if($this instanceof GingerIPValidation)
        {
            if (!$this->validateIP()) return;
        }



        $this->context->smarty->assign(
            array_filter([
                'this_path' => $this->_path,
                'this_path_bw' => $this->_path,
                'this_path_ssl' => \Tools::getShopDomainSsl(true, true).__PS_BASE_URI__.'modules/'.$this->name.'/',
            ])
        );

        $paymentOption = new PaymentOption;
        $paymentOption->setCallToActionText($this->trans('Pay by %method%',['%method%'=>$this->method_name], 'Modules.Nopayn.Admin'));
        $paymentOption->setLogo(\Media::getMediaPath(__PS_BASE_URI__.'modules/' .$this->name. '/'.$this->name.'.svg'));
        $paymentOption->setAction($this->context->link->getModuleLink($this->name, 'payment'));
        $paymentOption->setModuleName($this->name);

        if($this instanceof GingerCountryValidation)
        {
            if (!$this->validateCountry($params['cart']->id_address_invoice))
            {
                return;
            }

            $userCountry = $this->getUserCountryFromAddressId($params['cart']->id_address_invoice);
            $this->context->smarty->assign(
                'terms_and_condition_url',
                (strtoupper($userCountry) === static::BE_ISO_CODE) ? static::TERMS_CONDITION_URL_BE : static::TERMS_CONDITION_URL_NL
            );
        }

        if($this instanceof GingerCustomFieldsOnCheckout)
        {
            $paymentOption->setForm($this->context->smarty->fetch('module:' . $this->name . '/views/templates/hook/payment.tpl'));
        }

        return [$paymentOption];

    }

    public function execPayment($cart, $locale = '')
    {
        try {
            $this->orderBuilder = new GingerOrderBuilder($this, $cart, $locale);
            $gingerOrder = $this->gingerClient->createOrder($this->orderBuilder->getBuiltOrder());
        } catch (Exception $exception) {
            return \Tools::displayError($exception->getMessage());
        }

        if ($gingerOrder['status'] == 'error')
        {
            return \Tools::displayError(current($gingerOrder['transactions'])['customer_message']);
        }

        if (!$gingerOrder['id'])
        {
            return \Tools::displayError("Error: Response did not include id!");
        }


        if ($this instanceof GingerIdentificationPay)
        {
            $bankReference = current($gingerOrder['transactions']) ? current($gingerOrder['transactions'])['payment_method_details']['reference'] : null;

            $this->saveGingerOrderId($gingerOrder, $cart->id, $this->context->customer->secure_key, $this->name, $this->currentOrder, $bankReference);
            $this->sendPrivateMessage($bankReference);
            \Tools::redirect($this->orderBuilder->getReturnURL($gingerOrder['id']));

        }

        $this->saveGingerOrderId($gingerOrder, $cart->id, $this->context->customer->secure_key, $this->name);

        $this->validateOrder(
            $cart->id,
            \Configuration::get('PS_OS_PREPARATION'),
            $gingerOrder['amount'] / 100,
            GingerPSPConfig::GINGER_PSP_LABELS[current($gingerOrder['transactions'])['payment_method']],
            null,
            array("transaction_id" => current($gingerOrder['transactions'])['id']),
            null,
            false,
            $this->context->customer->secure_key
        );
        $id_order = $this->currentOrder;

        Db::getInstance()->update(GingerPSPConfig::PSP_PREFIX, array("id_order" => $id_order),
            '`ginger_order_id` = "'.Db::getInstance()->escape($gingerOrder['id']).'"');

        $this->updateGingerOrder($gingerOrder['id'],$id_order,$gingerOrder['amount']);

        $pay_url = array_key_exists(0, $gingerOrder['transactions'])
            ? current($gingerOrder['transactions'])['payment_url']
            : null;

        if (!$pay_url)
        {
            return \Tools::displayError("Error: Response did not include payment url!");
        }

        \Tools::redirect($pay_url);
    }

    /**
     *
     * @param type $response
     * @param int $cartId
     * @param type $customerSecureKey
     * @param string $type
     */
    protected function saveGingerOrderId($response, $cartId, $customerSecureKey, $type, $currentOrder = null, $reference = null)
    {
        $ginger = new Ginger();
        $ginger->setGingerOrderId($response['id'])
            ->setIdCart($cartId)
            ->setKey($customerSecureKey)
            ->setPaymentMethod($type)
            ->setIdOrder($currentOrder)
            ->setReference($reference);
        (new GingerGateway(\Db::getInstance()))
            ->save($ginger);
    }

    /**
     * fetch order from db
     *
     * @param int $cartId
     * @return array
     */
    protected function getOrderFromDB($cartId)
    {
        return (new GingerGateway(\Db::getInstance()))->getByCartId($cartId);
    }

    /**
     * update order id
     *
     * @param type $cartId
     * @param type $orderId
     * @return type
     */
    public function updateOrderId($cartId, $orderId)
    {
        return (new GingerGateway(\Db::getInstance()))->update($cartId, $orderId);
    }

    public function hookPaymentReturn($params)
    {
        if (!$this->active)
        {
            return;
        }

        $this->orderBuilder = new GingerOrderBuilder($this, $params['order']);
        $ginger = $this->getOrderFromDB($params['order']->id_cart);

        if($this instanceof GingerIdentificationPay)
        {
            $gingerOrder = $this->gingerClient->getOrder($ginger->getGingerOrderId());

            $gingerOrderIBAN = current($gingerOrder['transactions'])['payment_method_details']['creditor_iban'];
            $gingerOrderBIC = current($gingerOrder['transactions'])['payment_method_details']['creditor_bic'];
            $gingerOrderHolderName = current($gingerOrder['transactions'])['payment_method_details']['creditor_account_holder_name'];
            $gingerOrderHolderCity = current($gingerOrder['transactions'])['payment_method_details']['creditor_account_holder_city'];

            $this->context->smarty->assign(array(
                'total_to_pay' => \Tools::displayPrice($params['order']->getOrdersTotalPaid(), new \Currency($params['order']->id_currency), false),
                'gingerbanktransferIBAN' => $gingerOrderIBAN,
                'gingerbanktransferAddress' => $gingerOrderHolderCity,
                'gingerbanktransferOwner' => $gingerOrderHolderName,
                'gingerbanktransferBIC' => $gingerOrderBIC,
                'status' => 'ok',
                'reference' => $ginger->getReference(),
                'shop_name' => strval(\Configuration::get('PS_SHOP_NAME'))
            ));
        }

        return $this->fetch('module:'.$this->name.'/views/templates/hook/payment_return.tpl');
    }

    public function hookHeader()
    {
        $this->context->controller->addCss($this->_path . 'views/css/'.$this->method_id.'_form.css');
        $this->context->controller->addJS($this->_path . 'views/js/'.$this->method_id.'_form.js');
    }


    public function validateIP()
    {
        $ipFromConfiguration = \Configuration::get('GINGER_'.strtoupper(str_replace('-','',$this->method_id)).'_SHOW_FOR_IP'); //ex. klarna-pay-later GINGER_KLARNAPAYLATER_SHOW_FOR_IP
        if (strlen($ipFromConfiguration))
        {
            $ipWhiteList = array_map('trim', explode(",", $ipFromConfiguration));

            if (!in_array(filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP), $ipWhiteList))
            {
                return false;
            }
        }
        return true;
    }


    public function hookActionOrderStatusUpdate($params)
    {
        $newStatus = (int) $params['newOrderStatus']->id;
        $orderId = (int) $params['id_order'];

        $ginger = (new GingerGateway(\Db::getInstance()))->getByOrderId($orderId);
        $gingerOrderID = $ginger->getGingerOrderId();

        if ($this instanceof GingerCapturable && $newStatus === (int) \Configuration::get('PS_OS_SHIPPING')){
            try {
                $gingerOrder = $this->gingerClient->getOrder($gingerOrderID);

                if (!current($gingerOrder['transactions'])['is_capturable']) return true; //order is not able to be captured
                if (in_array('has-captures',$gingerOrder['flags'])) return true; //order is already captured

                $transactionID = current($gingerOrder['transactions']) ? current($gingerOrder['transactions'])['id'] : null;
                $this->gingerClient->captureOrderTransaction($gingerOrderID,$transactionID);
            } catch (Exception $exception) {
                \Tools::displayError($exception->getMessage());
                return false;
            }
            return true;
        }

        if ($newStatus === (int) \Configuration::get('PS_OS_PAYMENT'))
        {
            if (!\Configuration::get('GINGER_CREDITCARD_CAPTURE_MANUAL')) {
                return true;
            }
            try {
                $gingerOrder = $this->gingerClient->getOrder($gingerOrderID);

                if (!current($gingerOrder['transactions'])['is_capturable']) return true; //order is not able to be captured
                if (!empty($gingerOrder['flags']) && (in_array('has-captures',$gingerOrder['flags']))) return true;

                $transactionID = current($gingerOrder['transactions']) ? current($gingerOrder['transactions'])['id'] : null;
                $this->gingerClient->captureOrderTransaction($gingerOrderID,$transactionID);
            }catch (Exception $exception) {
                \Tools::displayError($exception->getMessage());
                return false;
            }

        }elseif ($newStatus === (int) \Configuration::get('PS_OS_CANCELED')){
            if (!\Configuration::get('GINGER_CREDITCARD_CAPTURE_MANUAL')) {
                return true;
            }
            try {
                $gingerOrder = $this->gingerClient->getOrder($gingerOrderID);

                if (!$gingerOrder['status'] == 'completed') return true;
                $transaction = current($gingerOrder['transactions']);
                if (!isset($transaction['transaction_type']) || $transaction['transaction_type'] !== 'authorization') return true;
                if (!empty($gingerOrder['flags']) && (in_array('has-captures', $gingerOrder['flags']) || in_array('has-voids', $gingerOrder['flags']))) return true;

                $transactionID = current($gingerOrder['transactions']) ? current($gingerOrder['transactions'])['id'] : null;
                $this->gingerClient->send('POST', sprintf('/orders/%s/transactions/%s/voids/amount', $gingerOrder['id'], $transactionID),
                    ['amount' => $gingerOrder['amount'], 'description' => sprintf(
                        "Void %s of the full %s on order %s ",
                        $gingerOrder['amount'], $gingerOrder['amount'], $gingerOrder['merchant_order_id']
                    )]);
            }catch (Exception $exception) {
                \Tools::displayError($exception->getMessage());
                return false;
            }
        }
        return true;
    }

    public function getUserCountryFromAddressID($addressID)
    {
        $prestaShopAddress = new \Address((int) $addressID);
        $country = new \Country(intval($prestaShopAddress->id_country));
        return strtoupper($country->iso_code);
    }

    public function validateCountry($addressID)
    {
        $userCountry = $this->getUserCountryFromAddressID($addressID);

        $countriesFromConfiguration = \Configuration::get('GINGER_'.strtoupper(str_replace('-','',$this->method_id)).'_COUNTRY_ACCESS');
        if (!$countriesFromConfiguration)
        {
            return true;
        }

        $countryList = array_map('trim', (explode(",", $countriesFromConfiguration)));
        if (!in_array($userCountry, $countryList))
        {
            return false;
        }

        if (!in_array($userCountry, $this->allowedLocales))
        {
            return false;
        }

        return true;
    }

    /**
     * Refund function
     */
    public function productRefund($orderId,$partialRefund, $paymentMethod, $cartId, $moduleName, $orderDetails = null)
    {
        $query = \Db::getInstance()->getRow("SELECT ginger_order_id FROM `" . _DB_PREFIX_ . GingerPSPConfig::PSP_PREFIX."` WHERE `id_cart` = " . $cartId);
        if (!$query || !isset($query['ginger_order_id'])) return false;

        $gingerOrderID = $query['ginger_order_id'];
        $gingerOrder = $this->gingerClient->getOrder($gingerOrderID);
        if (!empty($gingerOrder['flags']) && in_array('has-refunds', $gingerOrder['flags'])) return true; //order is already refunded

        if ($gingerOrder['status'] != 'completed')
        {
            throw new \Exception($paymentMethod . ': ' . $this->trans('Only completed orders can be refunded.',[],'Modules.Nopayn.Admin'));
        }

        $order = new \Order((int) $orderId);

        $this->orderBuilder = new GingerOrderBuilder($this,$order);

        $refund_data = [
            'amount' => $this->orderBuilder->getAmountInCents((float) str_replace(',', '.', $partialRefund)),
            'description' => 'Order refund: ' . $orderId
        ];

        $paymentMethodName =  str_replace(GingerPSPConfig::PSP_PREFIX,'',$moduleName);

        if (in_array($paymentMethodName, $this->capturableMethods))
        {
            if(!in_array('has-captures',$gingerOrder['flags']))
            {
                throw new \Exception($paymentMethod . ': ' . $this->trans('Refunds only possible when captured.',[],'Modules.Nopayn.Admin'));
            }

            $refund_data['order_lines'] = $this->orderBuilder->getOrderLinesForRefunds($order);

        }
        $gingerRefundOrder = $this->gingerClient->refundOrder($gingerOrder['id'], $refund_data);
        $newHistory = new \OrderHistory();
        $newHistory->id_order = (int)$order->id;
        $newHistory->changeIdOrderState(\Configuration::get('PS_OS_REFUND'), $order, true);
        if (!$newHistory->add()) return false;

        if (in_array($gingerRefundOrder['status'], ['error', 'cancelled', 'expired']))
        {
            if (isset(current($gingerRefundOrder['transactions'])['customer_message']))
            {
                throw new \Exception($paymentMethod . ': ' . current($gingerRefundOrder['transactions'])['customer_message']);
            }

            throw new \Exception($paymentMethod . ': ' . $this->trans('Refund order is not completed.',[],'Modules.Nopayn.Admin'));
        }
    }

    /**
     * @param $bankReference
     */
    public function sendPrivateMessage($bankReference)
    {
        $new_message = new \Message();
        $new_message->message = $this->trans('%label% %method% Reference: ',['%label'=> $this->label, '%method%' => $this->method_name],'Modules.Nopayn.Admin') . $bankReference;
        $new_message->id_order = $this->currentOrder;
        $new_message->private = 1;
        $new_message->add();
    }

    public function createTables()
    {
		$db = \Db::getInstance();

        $dropQuery = 'DROP TABLE IF EXISTS `'._DB_PREFIX_.GingerPSPConfig::PSP_PREFIX.'`';
        $db->execute($dropQuery);
		$createQuery = '
    CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.GingerPSPConfig::PSP_PREFIX.'` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `id_cart` int(11) DEFAULT NULL,
        `id_order` int(11) DEFAULT NULL,
        `key` varchar(64) NOT NULL,
        `ginger_order_id` varchar(36) NOT NULL,
        `payment_method` text,
        `reference` varchar(32) DEFAULT NULL,
        PRIMARY KEY (`id`),
        KEY `id_order` (`id_cart`),
        KEY `ginger_order_id` (`ginger_order_id`)
    ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8 AUTO_INCREMENT=1';
        return $db->execute($createQuery);

    }

    public function createOrderState()
    {
        if (!\Configuration::get('GINGER_AUTHORIZED'))
        {
            $orderState = new \OrderState();
            $orderState->send_email = false;
            $orderState->color = '#3498D8';
            $orderState->hidden = false;
            $orderState->delivery = false;
            $orderState->logable = false;
            $orderState->invoice = false;
            $orderState->paid = false;


            $translations = [
                'en' => 'Authorized',
                'de' => 'Autorisiert',
                'fr' => 'Autorisé',
                'es' => 'Autorizado',
                'it' => 'Autorizzato',
                'nl' => 'Geautoriseerd',
                'pt' => 'Autorizado',
                'sv' => 'Auktoriserad',
                'da' => 'Autoriseret',
                'no' => 'Autorisert',
                'fi' => 'Valtuutettu',
                'pl' => 'Autoryzowany',
                'cs' => 'Autorizované stránky',
                'sk' => 'Autorizovaná stránka',
                'hu' => 'Engedélyezett',
                'ro' => 'Autorizat',
                'el' => 'Εξουσιοδοτημένο',
                'bg' => 'Оторизиран',
                'lv' => 'Autorizēts',
                'lt' => 'Įgaliotas',
                'et' => 'Lubatud',
            ];

            $languages = \Language::getLanguages(false);
            foreach ($languages as $lang) {
                $iso = $lang['iso_code'];
                $orderState->name[$lang['id_lang']] = $translations[$iso] ?? 'Authorized';
            }

            if (!$orderState->add()) return false;

            \Configuration::updateValue('GINGER_AUTHORIZED', (int)$orderState->id);
        }
        return true;
    }


    /**
     * Hook for partial refund
     */
    public function hookOrderSlip($params)
    {
        $order = new \Order($params['order']->id);
        $partialRefund = current($params['productList'])['total_refunded_tax_incl'];
        $this->productRefund(
            $params['order']->id,
            $partialRefund,
            $params['order']->payment,
            $params['order']->id_cart,
            $params['order']->module
        );
    }

    private function validateCurrency($selectedCurrency)
    {
        try {
            $gingerCurrencies = $this->getAllowedCurrencyList();
        }catch (\Exception $exception){
            $gingerCurrencies['payment_methods'][$this->method_id]['currencies'] = ['EUR'];
        }

        if (!isset($gingerCurrencies['payment_methods'][$this->method_id]['currencies']))
        {
            return false;
        }

        $supportedCurrencies = $gingerCurrencies['payment_methods'][$this->method_id]['currencies'];
        return true ? in_array($selectedCurrency,$supportedCurrencies) : false;
    }

    public function getAllowedCurrencyList()
    {
        if (file_exists(__DIR__."/../ginger_currency_list.json"))
        {
            $currencyList = json_decode(file_get_contents(__DIR__."/../ginger_currency_list.json"),true);
            if ($currencyList['expired_time'] > time()) return $currencyList['currency_list'];
        }

        $allowed_currencies = $this->cacheCurrencyList();

        return $allowed_currencies;
    }


    public function cacheCurrencyList()
    {
        $allowed_currencies = $this->gingerClient->getCurrencyList();
        $currencyListWithExpiredTime = [
            'currency_list' => $allowed_currencies,
            'expired_time' => time() + (60*6)
        ];
        file_put_contents(__DIR__."/../ginger_currency_list.json", json_encode($currencyListWithExpiredTime));

        return $allowed_currencies;
    }

    /**
     * @param $cart
     * @return mixed
     * Uses for Order Lines, but can't be placed in OrderBuilder cause $this->context is protected field in each payment class
     */
    public function getShippingTaxRate($cart)
    {
        $carrier = new \Carrier((int) $cart->id_carrier, (int) $this->context->cart->id_lang);

        return $carrier->getTaxesRate(
            new Address((int) $this->context->cart->{\Configuration::get('PS_TAX_ADDRESS_TYPE')})
        );
    }

    /**
     * @param $product
     * @return mixed
     * Uses for Order Lines, but can't be placed in OrderBuilder cause $this->context is protected field in each payment class
     */
    public function getProductCoverImage($product)
    {
        $productCover = \Product::getCover($product['id_product']);

        if ($productCover)
        {
            $link_rewrite = $product['link_rewrite'] ?? \Db::getInstance()->getValue('SELECT link_rewrite FROM '._DB_PREFIX_.'product_lang WHERE id_product = '.(int) $product['id_product']);
            return $this->context->link->getImageLink($link_rewrite, $productCover['id_image']);
        }
    }

    /**
     * @param $product
     * @return string|null
     * Uses for Order Lines, but can't be placed in OrderBuilder cause $this->context is protected field in each payment class
     */
    public function getProductURL($product)
    {
        $productURL = $this->context->link->getProductLink($product);
        return strlen($productURL) > 0 ? $productURL : null;
    }
}


