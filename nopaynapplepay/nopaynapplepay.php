<?php

use Lib\components\GingerPlugin;
use Lib\components\GingerInstallTrait;
use Lib\interfaces\GingerCustomFieldsOnCheckout;

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once(\_PS_MODULE_DIR_ . 'nopayn/ginger/vendor/autoload.php');

class nopaynapplepay extends GingerPlugin implements GingerCustomFieldsOnCheckout
{
    use GingerInstallTrait;
    public function __construct()
    {
        $this->name = 'nopaynapplepay';
	    $this->method_id = 'apple-pay';
        parent::__construct();
    }
}
