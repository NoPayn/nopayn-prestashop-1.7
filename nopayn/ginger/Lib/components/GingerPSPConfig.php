<?php
namespace Lib\components;

class GingerPSPConfig
{

    const GINGER_PSP_LABELS = [
        'nopayn' => 'Library',
        'apple-pay' => 'Apple Pay',
        'credit-card' => 'Credit/Debit card',
        'google-pay' => 'Google Pay',
        'swish' => 'Swish',
        'mobilepay' => 'MobilePay',
    ];

    const PLUGIN_NAME = 'nopayn-prestashop-1.7';
    const PSP_LABEL = 'NoPayn';
    const PSP_PREFIX = 'nopayn';
    const GINGER_PSP_ENDPOINT = 'https://api.nopayn.co.uk';
}

