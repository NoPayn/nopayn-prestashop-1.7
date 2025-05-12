<?php

namespace Lib\components;

use GingerPluginSdk\Collections\AdditionalAddresses;
use GingerPluginSdk\Collections\OrderLines;
use GingerPluginSdk\Collections\PhoneNumbers;
use GingerPluginSdk\Collections\Transactions;
use GingerPluginSdk\Entities\Address;
use GingerPluginSdk\Entities\Customer;
use GingerPluginSdk\Entities\Line;
use GingerPluginSdk\Entities\Order;
use GingerPluginSdk\Entities\PaymentMethodDetails;
use GingerPluginSdk\Entities\Transaction;
use GingerPluginSdk\Properties\Amount;
use GingerPluginSdk\Properties\Birthdate;
use GingerPluginSdk\Properties\Country;
use GingerPluginSdk\Properties\Currency;
use GingerPluginSdk\Entities\Client;
use GingerPluginSdk\Properties\EmailAddress;
use GingerPluginSdk\Properties\Locale;
use GingerPluginSdk\Properties\Percentage;
use GingerPluginSdk\Properties\RawCost;
use GingerPluginSdk\Properties\VatPercentage;
use Lib\interfaces\GingerTermsAndConditions;

class GingerOrderBuilder
{

    private $paymentMethod;
    private $cart;

    private $prestashopCustomer;
    private $prestashopAddress;
    private $prestashopCountry;

    private $locale;

    const PHYSICAL = 'physical';
    const SHIPPING_FEE = 'shipping_fee';


    public function __construct($paymentMethod, $cart, $locale = '')
    {
        $this->paymentMethod = $paymentMethod;
        $this->cart = $cart;

        $this->prestashopCustomer = new \Customer((int)$this->cart->id_customer);
        $this->prestashopAddress = new \Address((int)$this->cart->id_address_invoice);
        $this->prestashopCountry = new \Country((int)$this->prestashopAddress->id_country);

        $this->locale = $locale;

    }

    public function getBuiltOrder()
    {
        $order = [];

        $order['amount'] = $this->getAmountInCents();
        $order['currency'] = $this->getOrderCurrency();
        $order['description'] = $this->getOrderDescription();
        $order['merchant_order_id'] = $this->getMerchantOrderID();
        $order['return_url'] = $this->getReturnURL();
        $order['customer'] = $this->getCustomerInformation();
        $order['client'] = $this->getClient();
        $order['webhook_url'] = $this->getWebhookURL();
        $order['transactions'][] = $this->getOrderTransactions();
        $order['order_lines'] = $this->getOrderLines($this->cart);

        return $order;
    }

    public function getCustomerInformation(): array
    {
        return array_filter([
            'address_type' => $this->getAddressType(),
            'country' => $this->getCountry(),
            'email_address' => $this->getEmailAddress(),
            'first_name' => $this->getFirstName(),
            'last_name' => $this->getLastName(),
            'merchant_customer_id' => $this->getMerchantCustomerID(),
            'phone_numbers' => $this->getPhoneNumbers(),
            'address' => $this->getAddress(),
            'locale' => $this->getLocale(),
            'ip_address' => $this->getIPAddress(),
            'gender' => $this->getGender(),
            'birthdate' => $this->getBirthday(),
            'additional_addresses' => $this->getAdditionalAddress()
        ]);
    }


    public function getFirstName()
    {
        return $this->prestashopCustomer->firstname;
    }


    public function getLastName()
    {
        return $this->prestashopCustomer->lastname;
    }


    public function getMerchantCustomerID()
    {
        return (string) $this->cart->id_customer;
    }


    public function getEmailAddress()
    {
        return $this->prestashopCustomer->email;
    }

    public function getCountry()
    {
        return $this->prestashopCountry->iso_code;
    }

    public function getLocale()
    {
        return $this->locale;
    }

    public function getGender()
    {
        if ($this->prestashopCustomer->id_gender) {
            return $this->prestashopCustomer->id_gender == '1' ? 'male' : 'female';
        }
    }

    public function getBirthday()
    {
        if ($this->prestashopCustomer->birthday) {
            return $this->isBirthdateValid($this->prestashopCustomer->birthday) ? $this->prestashopCustomer->birthday : null;
        }
        return null;
    }

    public function getIPAddress()
    {
        return \Tools::getRemoteAddr();
    }

    public function getPhoneNumbers()
    {
        $phone_numbers = [];

        if($this->prestashopAddress->phone)
        {
            $phone_numbers[] = $this->prestashopAddress->phone;
        }
        if($this->prestashopAddress->phone_mobile)
        {
            $phone_numbers[] = $this->prestashopAddress->phone_mobile;
        }

        return $phone_numbers;
    }

    public function getAddressType()
    {
        return 'customer';
    }

    public function getPostCode()
    {
        return $this->prestashopAddress->postcode;
    }

    public function getCity()
    {
        return $this->prestashopAddress->city;
    }

    public function getFirstAddress()
    {
        return $this->prestashopAddress->address1;
    }

    public function getSecondAddress()
    {
        return $this->prestashopAddress->address2;
    }

    public function getAddress()
    {
        return implode("\n", array_filter(array(
            $this->getFirstAddress(),
            $this->getSecondAddress(),
            $this->getPostCode()." ".$this->getCity()
        )));
    }

    public function getAdditionalAddress()
    {
        return [
            array_filter(array(
                'address' => implode("\n", array_filter(array(
                    $this->getFirstAddress(),
                    $this->getSecondAddress()." ".$this->getCity()
                ))),
                'address_type' => 'billing',
                'country' => $this->getCountry(),
                'postal_code' => $this->getPostCode()
            ))];
    }

    /**
     * @return string
     */
    public function getReturnURL($orderId = null)
    {
        $options = [
            'id_cart' => $this->cart->id,
            'id_module' => $this->paymentMethod->id
        ];

        if (isset($orderId)) $options['order_id'] = $orderId;

        return \Context::getContext()->link->getModuleLink(
            $this->paymentMethod->name, 'validation', $options
        );
    }

    /**
     * @return string
     */
    public function getOrderDescription()
    {
        return sprintf($this->paymentMethod->l('Your order at') . " %s", \Configuration::get('PS_SHOP_NAME'));
    }

    /**
     * @return Currency
     */
    public function getOrderCurrency()
    {
        $currencyOrder = new \Currency($this->cart->id_currency);
        return $currencyOrder->iso_code;
    }

    /**
     * @return string
     */
    public function getWebhookUrl()
    {
        return \_PS_BASE_URL_ . \__PS_BASE_URI__ . 'modules/nopayn/ginger/webhook.php';
    }

    /**
     * @return string
     */
    public function getPluginVersion()
    {
        return $this->paymentMethod->version;
    }

    public function getUserAgent()
    {
        return $_SERVER['HTTP_USER_AGENT'];
    }

    public function getPluginName()
    {
        return GingerPSPConfig::PLUGIN_NAME;
    }

    public function getPlatformName()
    {
        return 'PrestaShop1.7';
    }

    public function getPlatformVersion()
    {
        return _PS_VERSION_;
    }

    public function getMerchantOrderID()
    {
        return $this->paymentMethod->currentOrder;
    }

    public function getClient()
    {
        return [
            'user_agent' => $this->getUserAgent(),
            'platform_name' => $this->getPlatformName(),
            'platform_version' => $this->getPlatformVersion(),
            'plugin_name' => $this->getPluginName(),
            'plugin_version' => $this->getPluginVersion()
        ];
    }

    /**
     * Function returns transaction array
     * @return array
     * @throws Exception
     */
    public function getOrderTransactions()
    {
        return array_filter([
            'payment_method' => $this->getPaymentMethod(),
            'payment_method_details' => $this->getPaymentMethodDetails(),
            'capture_mode' => $this->getCaptureMode()
        ]);
    }

    public function getPaymentMethodDetails(): array
    {
        $paymentMethodDetails = [];

        //uses for afterpay
        if ($this->paymentMethod instanceof GingerTermsAndConditions)
        {
            $termsAndConditionFlag = filter_input(INPUT_POST, GingerPSPConfig::PSP_PREFIX.'afterpay_terms_conditions');
            if ($termsAndConditionFlag)
            {
                $paymentMethodDetails = [
                    'verified_terms_of_service' => true,
                ];
            }
            return $paymentMethodDetails;
        }

        return $paymentMethodDetails;
    }

    public function getCaptureMode() {
        if (\Configuration::get('GINGER_CREDITCARD_CAPTURE_MANUAL')) {
            return 'manual';
        }
        return '';
    }


    /**
     * @return string
     */
    public function getPaymentMethod(): string
    {
        return $this->paymentMethod->method_id;
    }

    /**
     * @return Amount
     */
    public function getAmountInCents($amount = ''): int
    {
        return $amount ? round($amount * 100) : round($this->cart->getOrderTotal(true) * 100);
    }

    /**
     * checks is brithdate format valid
     * && is not 0000-00-00
     *
     * @param string $birthdate
     * @return boolean
     */
    public function isBirthdateValid($birthdate)
    {
        if (preg_match("/([0-9]{4})-([0-9]{2})-([0-9]{2})/", $birthdate, $matches)) {
            return (bool)($matches[2] != '00' && $matches[3] != '00');
        }
        return false;
    }

    /**
     * @param $cart
     * @return OrderLines
     */
    public function getOrderLines($cart)
    {
        $orderLines = [];

        foreach ($cart->getProducts() as $key => $product) {
            $orderLines[] = array_filter([
                'ean' => $this->getProductEAN($product),
                'url' => $this->paymentMethod->getProductURL($product),
                'name' => $product['name'],
                'type' => GingerOrderBuilder::PHYSICAL,
                'amount' => $this->getAmountInCents(\Tools::ps_round($product['price_wt'], 2)),
                'currency' => $this->getOrderCurrency(),
                'quantity' => (int)$product['cart_quantity'],
                'image_url' => $this->paymentMethod->getProductCoverImage($product),
                'vat_percentage' => ((int) $product['rate'] * 100),
                'merchant_order_line_id' => $product['unique_id']
            ], function ($var) {
                return !is_null($var);
            });
        }

        $shippingFee = $cart->getOrderTotal(true, \Cart::ONLY_SHIPPING);

        if ($shippingFee > 0) {
            $orderLines[] = $this->getShippingOrderLine($cart, $shippingFee);
        }

        return count($orderLines) > 0 ? $orderLines : null;
    }

    /**
     * @param $product
     * @return string|null
     */
    public function getProductEAN($product)
    {
        return (key_exists('ean13', $product) && strlen($product['ean13']) > 0) ? $product['ean13'] : null;
    }


    public function getShippingOrderLine($cart, $shippingFee)
    {
        return [
            'name' => $this->paymentMethod->l("Shipping Fee"),
            'type' => GingerOrderBuilder::SHIPPING_FEE,
            'amount' => $this->getAmountInCents($shippingFee),
            'currency' => $this->getOrderCurrency(),
            'vat_percentage' => $this->getAmountInCents($this->paymentMethod->getShippingTaxRate($cart)),
            'quantity' => 1,
            'merchant_order_line_id' => (string)(count($cart->getProducts()) + 1)
        ];
    }

    public function getOrderLinesForRefunds($order)
    {
        $orderLines = [];

        foreach ($order->getProducts() as $key => $product) {
            $orderLines[] = array_filter([
                'ean' => $this->getProductEAN($product),
                'url' => $this->paymentMethod->getProductURL($product),
                'name' => $product['product_name'],
                'type' => GingerOrderBuilder::PHYSICAL,
                'amount' => $this->getAmountInCents(\Tools::ps_round($product['product_price_wt'], 2)),
                'currency' => $this->getOrderCurrency(),
                'quantity' => (int) $product['product_quantity'],
                'image_url' => $this->paymentMethod->getProductCoverImage($product),
                'vat_percentage' => ((int) $product['tax_rate'])   * 100,
                'merchant_order_line_id' => $product['product_id']
            ], function ($var) {
                return !is_null($var);
            });
        }

        return $orderLines;
    }
}


