[{if $module_var == 'sIdealoToken' || $module_var == 'sIdealoPaymentMap' || $module_var == 'sIdealoDeliveryMap' || $module_var == 'sIdealoConfigTest' || $module_var == 'sIdealoFirstStatus'}]
    [{if $module_var == 'sIdealoToken'}]
        <dl>
            <dt>
                <input type=text  class="txt" style="width: 250px;" name="confstrs[[{$module_var}]]" value="[{$confstrs.$module_var}]" [{ $readonly }]>
                [{oxinputhelp ident="HELP_SHOP_MODULE_`$module_var`"}]
            </dt>
            <dd>
                [{oxmultilang ident="SHOP_MODULE_`$module_var`"}]
                [{$oView->fcIdealoCheckToken()}]
            </dd>
            <div class="spacer"></div>
        </dl>
    [{elseif $module_var == 'sIdealoPaymentMap'}]
        [{foreach from=$oView->fcGetIdealoPaymentTypes() key=sKey item=sValue}]
            <dl>
                <dt>
                    <select class="select" name="confselects[[{$module_var}]][[{$sKey}]]" [{ $readonly }]>
                        [{foreach from=$oView->fcIdealoGetShopPaymentTypes() key=sPayId item=sPayTitle}]
                            <option value="[{$sPayId}]" [{if $confselects.$module_var.$sKey == $sPayId}]selected[{/if}]>[{ $sPayTitle }]</option>
                        [{/foreach}]
                    </select>
                </dt>
                <dd>
                    [{$sValue}]
                </dd>
            </dl>
        [{/foreach}]
    [{elseif $module_var == 'sIdealoDeliveryMap'}]
        [{foreach from=$oView->fcGetIdealoDeliveryTypes() key=sKey item=sValue}]
            <dl>
                <dt>
                    <select class="select" name="confselects[[{$module_var}]][[{$sKey}]][type]" [{ $readonly }]>
                        [{foreach from=$oView->fcIdealoGetShopDeliveryTypes() key=sDelId item=sDelTitle}]
                            <option value="[{$sDelId}]" [{if $confselects.$module_var.$sKey.type == $sDelId}]selected[{/if}]>[{ $sDelTitle }]</option>
                        [{/foreach}]
                    </select>
                    <select class="select" name="confselects[[{$module_var}]][[{$sKey}]][carrier]" [{ $readonly }] style="width: 90px;">
                        [{foreach from=$oView->fcGetIdealoDeliveryCarriers() key=sCarrierId item=sCarrier}]
                            <option value="[{$sCarrierId}]" [{if $confselects.$module_var.$sKey.carrier == $sCarrierId}]selected[{/if}]>[{ $sCarrier }]</option>
                        [{/foreach}]
                    </select>
                </dt>
                <dd>
                    [{$sValue}]
                </dd>
            </dl>
        [{/foreach}]
    [{elseif $module_var == 'sIdealoConfigTest'}]
        [{if $oView->fcIdealoIsConfigComplete()}]
        <dl>
            <dd><span style="color:green;">[{oxmultilang ident="FCIDEALO_CONFIG_COMPLETE"}]</span></dd>
        </dl>
        [{else}]
            [{if $oView->fcIdealoTokenMissing()}]
                <dl>
                    <dd><span style="color:red;">[{oxmultilang ident="FCIDEALO_CONFIG_TOKEN_MISSING"}]</span></dd>
                </dl>
            [{elseif !$oView->fcIdealoIsTokenCorrect()}]
                <dl>
                    <dd><span style="color:red;">[{oxmultilang ident="FCIDEALO_TOKEN_ERROR"}]</span></dd>
                </dl>     
            [{/if}]
            [{if $oView->fcIdealoEmailMissing()}]
                <dl>
                    <dd><span style="color:red;">[{oxmultilang ident="FCIDEALO_CONFIG_EMAIL_MISSING"}]</span></dd>
                </dl>
            [{/if}]
        [{/if}]
    [{elseif $module_var == 'sIdealoFirstStatus'}]
        <dl>
            <dt>
                <select class="select" name="confselects[[{$module_var}]]" [{ $readonly }]>
                    [{foreach from=$oView->fcIdealoGetAllOrderFolders() key=sFolder item=sColor}]
                        <option value="[{$sFolder}]" [{if $confselects.$module_var == $sFolder}]selected[{/if}]>[{ oxmultilang ident=$sFolder noerror=true }]</option>
                    [{/foreach}]
                </select>
            </dt>
            <dd>
                [{oxmultilang ident="SHOP_MODULE_`$module_var`"}]
            </dd>
        </dl>
    [{/if}]
[{else}]
    [{$smarty.block.parent}]
[{/if}]