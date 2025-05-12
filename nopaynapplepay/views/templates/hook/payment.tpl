<div class="row">
      <div class="col-xs-12">
            <div class="payment_module">
                  <div class='nopaynapplepay'>
                        <form id="nopaynapplepay_form" name="nopaynapplepay_form" action="{$link->getModuleLink('nopaynapplepay', 'payment')|escape:'html'}" method="post">
                              <p id="ginger_notification"></p>
                        </form>
                  </div>
            </div>
      </div>
</div>
<script type="text/javascript">

      var applepay_isnt_available = "{l s='Apple Pay is not available for your device' d='Modules.Nopaynapplepay.Payment' js=1}";

      if(!window.ApplePaySession)
      {
            document.getElementById('ginger_notification').textContent = applepay_isnt_available
      }

</script>
