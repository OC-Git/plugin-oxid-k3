[{if $basketitem && $basketitem->getPersParams() && method_exists($basketitem, 'ocHasK3Configuration') && $basketitem->ocHasK3Configuration()}]
    <br />
    [{foreach from=$basketitem->ocGetK3Configuration() item=aParam key=sVar}]
        [{if $sVar == 'variables'}]
            [{foreach from=$aParam item=variable}]
                [{$variable.label}] : [{$variable.value}]<br/>
            [{/foreach}]
        [{/if}]
    [{/foreach}]
[{else}]
    [{$smarty.block.parent}]
[{/if}]