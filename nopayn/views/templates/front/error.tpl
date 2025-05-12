{extends file=$template}

{block name='content'}

    <section id="content" class="page-content page-not-found">
        <h1>{l s='Your order at %s' sprintf=[$shop.name] d='Modules.Nopayn.Error'}</h1>
        
        <h3>{l s='There was an error processing your order' d='Modules.Nopayn.Error'}</h3>
        {if isset($error_message)}
            <p><strong>{$error_message}</strong></p>
        {/if}
        <a href="{$checkout_url}" title="{l s='Please click here to try again.' d='Modules.Nopayn.Error'}" class="button-exclusive btn btn-default">
            <i class="icon-chevron-left"></i>
            {l s='Go back to the checkout page' d='Modules.Nopayn.Error'}
        </a>

        <p>&nbsp;</p>

    </section>
{/block}