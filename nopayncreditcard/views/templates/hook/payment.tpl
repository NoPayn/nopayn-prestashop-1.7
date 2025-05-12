<style>
a.nopayncreditcard::after {
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
a.nopayncreditcard {
      background: url({$base_dir}modules/nopayncreditcard/logo_bestelling.png) 15px 12px no-repeat
}
</style>
<div class="row">
      <div class="col-xs-12">
            <p class="payment_module">
                  <a class="nopayncreditcard" href="{$link->getModuleLink('nopayncreditcard', 'payment')|escape:'html'}" title="{l s='Pay by Credit/Debit card' d='Modules.Nopayncreditcard.Payment'}">
                        {l s='Pay by Credit/Debit card' d='Modules.Nopayncreditcard.Payment'}</span>
                  </a>
            </p>
      </div>
</div>