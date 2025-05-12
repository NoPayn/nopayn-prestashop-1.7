<?php
namespace Lib\components;

trait GingerInstallTrait
{
    public function install()
    {
        if (!\Module::isInstalled(GingerPSPConfig::PSP_PREFIX))
        {
            throw new \PrestaShopException('The '.GingerPSPConfig::PSP_PREFIX.' extension is not installed, please install the '.GingerPSPConfig::PSP_PREFIX.' extension first and then the current extension.');
        }

        if (!\Configuration::get('GINGER_API_KEY'))
        {
            throw new \PrestaShopException('The webshop API key is missing in the '.GingerPSPConfig::PSP_PREFIX.' extension. Please add the API Key in the '.GingerPSPConfig::PSP_PREFIX.' extension, save it & then re-install this extension.');
        }
        return parent::install();
    }
}
