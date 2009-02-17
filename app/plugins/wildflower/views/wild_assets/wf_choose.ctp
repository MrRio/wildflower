<ul id="asset_chooser">
    <? foreach($assets as $asset) { ?>
        <li id="asset_<?=$asset['WildAsset']['id']?>" class="ui-corner-top ui-corner-bottom">
            <div style="height: 83px; clear: both">
                <img src="<?php echo $html->url("/wildflower/thumbnail/{$asset['WildAsset']['name']}/80/80"); ?>" alt="<?php echo hsc($file['WildAsset']['title']); ?>" />
            </div>
            <?=$asset['WildAsset']['title']?>
        </li>
    <? } ?>
</ul>
<div style="height: 2px; clear: both;"></div>
