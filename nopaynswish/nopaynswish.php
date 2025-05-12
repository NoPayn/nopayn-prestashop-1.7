<?php

use Lib\components\GingerPlugin;
use Lib\components\GingerInstallTrait;

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once(\_PS_MODULE_DIR_ . 'nopayn/ginger/vendor/autoload.php');

class nopaynswish extends GingerPlugin
{
    use GingerInstallTrait;

    public function __construct()
    {
        $this->name = 'nopaynswish';
	    $this->method_id = 'swish';
        parent::__construct();
    }
}
