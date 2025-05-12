<h1>{l s='Your order at %s' sprintf=[$shop.name] d='Modules.Nopayn.Errors-messages'}</h1>

<h1>{l s='Unexpected payment error' d='Modules.Nopayn.Errors-messages'}</h1>

<div class="error">
    <p><b>{l s='Unfortunately there was a problem processing your payment.' d='Modules.Nopayn.Errors-messages'}</b></p>
    <p><a href="{$checkout_url}">{l s='Please click here to try again.' d='Modules.Nopayn.Errors-messages'}</a></p>
</div>
