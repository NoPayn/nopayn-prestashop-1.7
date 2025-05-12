<?php

use Lib\components\GingerConfigurableTrait;
use Lib\components\GingerPlugin;
use Lib\components\GingerInstallTrait;

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once(\_PS_MODULE_DIR_ . 'nopayn/ginger/vendor/autoload.php');

class nopayncreditcard extends GingerPlugin
{
    use GingerInstallTrait, GingerConfigurableTrait;

    public function __construct()
    {
        $this->name = 'nopayncreditcard';
	    $this->method_id = 'credit-card';
        parent::__construct();
    }
}
