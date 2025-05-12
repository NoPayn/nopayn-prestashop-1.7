<?php

use Lib\components\GingerPSPConfig;
use Lib\components\GingerConfigurableTrait;
use Lib\components\GingerPlugin;

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once(\_PS_MODULE_DIR_ . 'nopayn/ginger/vendor/autoload.php');

class nopayn extends GingerPlugin
{

    use GingerConfigurableTrait;
    public $extra_mail_vars;

    public function __construct()
    {
        $this->name = GingerPSPConfig::PSP_PREFIX;
        $this->method_id = GingerPSPConfig::PSP_PREFIX;
        parent::__construct();
    }

}
