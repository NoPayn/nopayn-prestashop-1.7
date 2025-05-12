<?php

use Lib\components\GingerPSPConfig;

include ('vendor/autoload.php');

include dirname(__FILE__).'/../../../config/config.inc.php';

global $kernel;

if(!$kernel)
{
    require_once _PS_ROOT_DIR_.'/app/AppKernel.php';
    $kernel = new \AppKernel('prod', false);
    $kernel->boot();
}

$input = json_decode(file_get_contents("php://input"), true);
$ginger_order_id = $input['order_id'];
echo("WEBHOOK: Starting for ginger_order_id: ".htmlentities($ginger_order_id) . "\n");

if (!in_array($input['event'], array("status_changed")))
{
    die("Only work to do if the status changed");
}

$row = Db::getInstance()->getRow(
    sprintf(
        'SELECT * FROM `%s` WHERE `%s` = \'%s\'',
        _DB_PREFIX_. GingerPSPConfig::PSP_PREFIX.'',
        'ginger_order_id',
        pSQL($ginger_order_id)
    )
);

if (!$row)
{
    die("WEBHOOK: Error - No row found for ginger_order_id: ".htmlentities($ginger_order_id));
}

echo "WEBHOOK: Payment method: " . $row['payment_method'] . "\n";

include dirname(__FILE__).'/../../'.$row['payment_method'].'/'.$row['payment_method'].'.php';
$gingerPaymentMethod = new $row['payment_method']();

$order_details = $gingerPaymentMethod->getGingerClient()->getOrder($ginger_order_id);

if ($order_details)
{

    echo "WEBHOOK: Found status: " . $order_details['status'] . "\n";

    if ($row['id_order'])
    {
        echo "WEBHOOK: id_order was not empty but: " . $row['id_order'] . "\n";

        if (!Context::getContext()->link)
        {
            Context::getContext()->link = new link();// work around a prestashop bug so email is sent
        }

        $order = new Order((int) $row['id_order']);
        switch ($order_details['status']) {
            case 'new':
            case 'processing':
                $order_status = (int) Configuration::get('PS_OS_PREPARATION');
                break;

            case 'completed':
                $transaction = current($order_details['transactions']);
                if (isset($transaction['transaction_type']) && $transaction['transaction_type'] == 'authorization') {
                    $order_status = (int) \Configuration::get('GINGER_AUTHORIZED');
                    break;
                }
                $order_status = (int) Configuration::get('PS_OS_PAYMENT');
                break;

            case 'error':
                $order_status = (int) Configuration::get('PS_OS_ERROR');
                break;
            case 'cancelled':
            case 'expired':
                $order_status = (int) Configuration::get('PS_OS_CANCELED');
                break;
        }
        if ($order->current_state != $order_status) {
            echo "WEBHOOK: updating status, old status was: " . $order->current_state . "\n";
            $new_history = new OrderHistory();
            $new_history->id_order = (int) $order->id;

            $context = Context::getContext();
            if (!isset($context->currency)) {
                $context->currency = new Currency((int) Configuration::get('PS_CURRENCY_DEFAULT'));
            }
            if (!isset($context->language)) {
                $context->language = new Language((int) Configuration::get('PS_LANG_DEFAULT'));
            }
            if (!isset($context->shop)) {
                $context->shop = new Shop((int) Configuration::get('PS_SHOP_DEFAULT'));
            }
            Shop::setContext(Shop::CONTEXT_SHOP, $context->shop->id);

            $new_history->changeIdOrderState( $order_status, $order, true);
            $new_history->add();
        }
    }
}
