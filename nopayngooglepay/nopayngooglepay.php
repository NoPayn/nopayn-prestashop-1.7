<?php

use Lib\components\GingerInstallTrait;
use Lib\components\GingerPlugin;

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once(\_PS_MODULE_DIR_ . 'nopayn/ginger/vendor/autoload.php');

class nopayngooglepay extends GingerPlugin
{
    use GingerInstallTrait;

    public function __construct()
    {
        $this->name = 'nopayngooglepay';
	    $this->method_id = 'google-pay';
        parent::__construct();
    }
}
