<style>
a.nopaynmobilepay::after {
      display: block;
      content: "\f054";
      position: absolute;
      right: 15px;
      margin-top: -11px;
      top: 50%;
      font-family: "FontAwesome";
      font-size: 25px;
      height: 22px;
      width: 14px;
      color: #777; 
}
a.nopaynmobilepay {
      background: url({$base_dir}modules/nopaynmobilepay/logo_bestelling.png) 15px 12px no-repeat
}
</style>
<div class="row">
      <div class="col-xs-12">
            <p class="payment_module">
                  <a class="nopaynmobilepay" href="{$link->getModuleLink('nopaynmobilepay', 'payment')|escape:'html'}" title="{l s='Pay by MobilePay' d='Modules.Nopaynmobilepay.Payment'}">
                        <span>{l s='Pay by MobilePay' d='Modules.Nopaynmobilepay.Payment'}</span>
                  </a>
            </p>
      </div>
</div>