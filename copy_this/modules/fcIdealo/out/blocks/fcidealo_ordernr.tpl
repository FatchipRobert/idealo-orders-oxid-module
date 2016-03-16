[{if $edit->oxorder__fcidealo_ordernr->value != ''}]
    <b>[{ oxmultilang ident="FCIDEALO_ORDERNR" }]: </b>[{ $edit->oxorder__fcidealo_ordernr->value }]<br><br>
[{/if}]
[{$smarty.block.parent}]