<ul id="asset_chooser">
    <? foreach($assets as $asset) { 
            preg_match("/\.([^\.]+)$/", $asset['WildAsset']['name'], $matches);
            $extension = $matches[1];    
    ?>
        <li id="asset_<?=$asset['WildAsset']['id']?>" class="ui-corner-all">
            <div style="height: 83px; clear: both">
                <? if($type == 'file') { ?>
                <img src="<?php echo $html->url("/img/mime/{$extension}.png"); ?>" alt="<?php echo hsc($file['WildAsset']['title']); ?>" />
                <? } else { ?>
                <img src="<?php echo $html->url("/wildflower/thumbnail/{$asset['WildAsset']['name']}/80/80"); ?>" alt="<?php echo hsc($file['WildAsset']['title']); ?>" />
                <? } ?>
            </div>
            <span><?=$asset['WildAsset']['title']?></span>
        </li>
    <? } ?>
</ul>
<div style="height: 2px; clear: both;"></div>
