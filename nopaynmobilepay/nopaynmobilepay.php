<?php

use Lib\components\GingerPlugin;
use Lib\components\GingerInstallTrait;

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once(\_PS_MODULE_DIR_ . 'nopayn/ginger/vendor/autoload.php');

class nopaynmobilepay extends GingerPlugin
{
    use GingerInstallTrait;

    public function __construct()
    {
        $this->name = 'nopaynmobilepay';
	    $this->method_id = 'mobilepay';
        parent::__construct();
    }
}
