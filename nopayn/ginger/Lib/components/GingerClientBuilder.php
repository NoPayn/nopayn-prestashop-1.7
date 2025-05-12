<?php


namespace Lib\components;
use Ginger\Ginger;


class GingerClientBuilder
{
    public static function gingerBuildClient($paymentMethod = '')
    {
        $apiKey = $paymentMethod ? self::getTestAPIKey($paymentMethod) : \Configuration::get('GINGER_API_KEY');

        return Ginger::createClient(
            GingerPSPConfig::GINGER_PSP_ENDPOINT,
            $apiKey,
            (\Configuration::get('GINGER_BUNDLE_CA')) ?
                [
                    CURLOPT_CAINFO => self::gingerGetCaCertPath()
                ] : []
        );
    }

    /**
     * Function get test-api-key from gateway settings
     * @param $paymentMethod - gateway's id
     * @return mixed
     */
    public static function getTestAPIKey($paymentMethod)
    {
        if (\Configuration::get('GINGER_'.strtoupper($paymentMethod).'_TEST_API_KEY'))
        {
            return \Configuration::get('GINGER_'.strtoupper($paymentMethod).'_TEST_API_KEY');
        }

        return \Configuration::get('GINGER_API_KEY');
    }

    /**
     * Get CA certificate path
     *
     * @return bool|string
     */
    public static function gingerGetCaCertPath()
    {
        return realpath(_PS_MODULE_DIR_ . GingerPSPConfig::PSP_PREFIX.'/ginger/assets/cacert.pem');
    }


}