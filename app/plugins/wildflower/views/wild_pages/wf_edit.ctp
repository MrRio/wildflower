<?php 
    $partialLayout->setLayoutVar('isFullEdit', true);
    $session->flash();
    
    echo 
    $form->create('WildPage', array('url' => $html->url(array('action' => 'wf_update', 'base' => false)), 'class' => 'editor-form'));
?>

<?

$template['content-block'] = <<<EOF

<div class="actions-handle action-attach">
    <div class="content-block group-block">
        <span class="alignment-attach">
            <img title="Align image right" alt="right" src="' + base + 'img/admin/image-right.png" class="align-right' + (section.align == 'right' ? ' selected' : '') + '" />
            <img title="Display image across the page" alt="center" src="' + base + 'img/admin/image-centre.png" class="align-center' + (section.align == 'center' ? ' selected' : '') + '" />
            <img title="Align image left" alt="left" src="' + base + 'img/admin/image-left.png" class="align-left' + (section.align == 'left' ? ' selected' : '') + '" />                
        </span>
        <img class="thumbnail" src="' + section.imageurl + '" />
        <input type="button" value="Change Picture" onclick="groupEditor.changeAsset(this)" />
        <input type="button" value="Remove Picture" onclick="groupEditor.removePicture(this)" />
        <input type="hidden" class="group-editor-image" value="' + section.image + '" />
        <input type="hidden" class="group-editor-image-align" value="' + section.align + '" />
        <textarea class="group-editor-text" id="tiny-mce-' + (tinyMCEid) + '">' + section.text + '</textarea>
    </div>
    <span class="row-actions">
        <a title="Move up" href="#" onclick="groupEditor.moveSection(this, true)" class="move-up">Move up</a>
        <a title="Move down" href="#" onclick="groupEditor.moveSection(this, false)" class="move-down">Move down</a>
        <a title="Delete this page" href="#" class="delete-section" onclick="groupEditor.deleteSection(this)">Delete</a>
    </span>
</div>

EOF;

$template['content-block'] = str_replace(array("\n", "\r"), array("", ""), $template['content-block']);


$template['file-block'] = <<<EOF

<div class="actions-handle action-attach">
    <div class="file-block group-block">
        <span>' + section.title + '</span>&nbsp;&nbsp;<input type="button" value="Change File" onclick="groupEditor.changeAsset(this)" />
        <input type="hidden" class="group-editor-file" value="' + section.file + '" />
    </div>
    <span class="row-actions">
        <a title="Move up" href="#" onclick="groupEditor.moveSection(this, true)" class="move-up">Move up</a>
        <a title="Move down" href="#" onclick="groupEditor.moveSection(this, false)" class="move-down">Move down</a>
        <a title="Delete this page" href="#" class="delete-section" onclick="groupEditor.deleteSection(this)">Delete</a>
    </span>
</div>

EOF;

$template['file-block'] = str_replace(array("\n", "\r"), array("", ""), $template['file-block']);

?>
<div id="dialog" title="Choose an image"></div>

<link type="text/css" href="<?=$html->url('/wildflower/css/jquery-ui-theme/ui.all.css')?>" rel="Stylesheet" />
<script type="text/javascript" src="<?=$html->url('/wildflower/js/jquery-ui-personalized-1.6rc6.min.js')?>"></script>
<script src="<?=$html->url('/wildflower/js/jquery.json-1.3.min.js')?>"></script>
<script type="text/javascript">

var base = '<?=$html->url("/")?>';
jQuery.fn.swap = function(b) {
    b = jQuery(b)[0];
    var a = this[0];

    var t = a.parentNode.insertBefore(document.createTextNode(''), a);
    b.parentNode.insertBefore(a, b);
    t.parentNode.insertBefore(b, t);
    t.parentNode.removeChild(t);

    return this;
};

var groupEditor = function (){
    
    // Private variables and functions
    var el;
    var id;
    var tinyMCEid = 0;
    
    return {
        currentGroupElement: null,
        // Public functions
        init: function(element, i) {
            el = element;
            id = 'group-editor-' + id;
            var content = $(el).val();
            $(el).css('display', 'none').attr('id', id);
            
            // Parse JSON - eval is safe here because content is trusted
            var parsedContent;
            try {
                parsedContent = eval('(' + content + ')');
            } catch(e) {
                parsedContent = [{
                    type: 'content',
                    image: '',
                    text: ''
                }];
            }
            
            for(var i = 0; i < parsedContent.length; i ++) {
                var section = parsedContent[i];
                    switch(section.type) {
                        // A 'content' block is defined as text with an image
                        case 'content':
                            this.appendContentBlock(section);
                            break;
                        
                        case 'file':
                            this.appendFileBlock(section);
                            break;
                    }
            }
            $('#edit-buttons').before('<div id="add-file" class="wf-form-button"><input type="button" onclick="groupEditor.appendFileBlock({type: \'file\', title: \'No file uploaded\', file: \'\' })" value="Add file" /></div>');
            
            $('#edit-buttons').before('<div id="add-content" class="wf-form-button"><input type="button" onclick="groupEditor.appendContentBlock({type:\'content\', image: \'\', text:\'\', align: \'right\'})" value="Add content block" /></div>');
        },
        
        appendContentBlock: function(section) {
            section.imageurl = base + 'img/admin/no-image-selected.gif';
            if(section.image != '') {
                section.imageurl = base + 'wildflower/thumbnail_by_id/' + section.image + '/50/50/1';
            }
            tinyMCEid ++;
            $(el).parent().append('<?=$template["content-block"]?>');
            tinyMCE.execCommand("mceAddControl", true, 'tiny-mce-' + tinyMCEid);            
            this.attachHover();
        },
        
        appendFileBlock: function(section) {
            $(el).parent().append('<?=$template["file-block"]?>');
            this.attachHover();
        },
        
        deleteSection: function(element) {
            if (confirm('Are you sure you want to remove this?')) {
                $(element).parent().parent().remove();
            }
            return false;
        },
        
        moveSection: function(element, up) {
            // Remove and reapply tinymce
            var removeMCE = function(){
                $('.group-editor-text').each(function(){
                    tinyMCE.execCommand("mceRemoveControl", true, $(this).attr('id'));
                });
            }
            
            var addMCE = function() {
                $('.group-editor-text').each(function(){
                    tinyMCE.execCommand("mceAddControl", true, $(this).attr('id'));
                });                
            }
            
            var element = $(element).parent().parent();
            if(up) {
                if (element.prev().hasClass('actions-handle')) {
                    removeMCE();
                    element.swap(element.prev());
                    addMCE();
                } else {
                    alert('Sorry, this section cannot be moved any further up.');
                }
            } else {
                if (element.next().hasClass('actions-handle')) {
                    removeMCE();
                    element.swap(element.next());   
                    addMCE();                 
                } else {
                    alert('Sorry, this section cannot be moved any further down.');
                }
            }
            return false;
        },
        
        changeAsset: function(element) {
            this.currentGroupElement = element;
            $('#dialog').dialog('open');
        },
        
        removePicture: function(element) {
            //base + 'img/admin/no-image-selected.gif'
        },
        
        attachHover: function() {
            
            // Alignment buttons
            
            $('.alignment-attach img').hover(function(){
                $(this).addClass('hover');
            }, function(){
                $(this).removeClass('hover');
            }).click(function(){
                $('img', $(this).parent()).removeClass('selected');
                $(this).addClass('selected');
            });
            $('.alignment-attach').removeClass('alignment-attach').addClass('alignment');
            
            // Edit actions
            
            var actionHandleEls = $('.action-attach');
            
            if (actionHandleEls.size() < 1) return;
            
            $('.action-attach').removeClass('action-attach');
            
            var itemActionsTimeout = null;
            
            var over = function() {
                if (itemActionsTimeout) {
                    // Cancel all to be closed and hide them
                    clearTimeout(itemActionsTimeout);
                    $('.row-actions:visible').hide();
                }
                
                $(this).find('.row-actions').show();
            }
            
            var out = function() {
                if (itemActionsTimeout) {
                    clearTimeout(itemActionsTimeout);
                }
        		
        		var el = this;
        		
                itemActionsTimeout = setTimeout(function() {
                    if ($.browser.msie) { // IE7 does not handle animations well, therefore use plain hide()
                        $(el).find('.row-actions').hide();
                    }
                    else {
                        $(el).find('.row-actions').fadeOut(500);
                    }
                }, 1000);
            }
              
            actionHandleEls.hover(over, out);
        },
        
        /**
         * 
         */
        serialize: function() {
            var serialized = new Array();
            
            $('.group-block').each(function() {
                if ($(this).hasClass('content-block')) {
                    serialized.push({
                        "type": "content",
                        "image": $('.group-editor-image', $(this)).val(),
                        "text": $('.group-editor-text', $(this)).val(),
                        "align": $('.alignment img.selected', $(this)).attr('alt'),
                    });
                } else {
                    serialized.push({
                        "type": "file",
                        "file": $('.group-editor-file', $(this)).val(),
                        "title": $('span', $(this)).html()
                    });                    
                }
            })                      
            $('.group_editor').val($.toJSON(serialized));
        }
    }
    
}()
/* TODO: Move some of this into group editor init */
$(document).ready(function(){
    $('.group_editor').each(function(i){
        //groupEditor();
        groupEditor.init(this, i);
    });

    // Dialog			
    
    var saveAssetChoice = function() {
        if ($('#asset_chooser li.selected').length != 0) {
            var asset_id = $('#asset_chooser li.selected').attr('id').replace(/asset_/, '');
            var element = $(groupEditor.currentGroupElement).parent();
            if (element.hasClass('content-block')) {
                $('img.thumbnail', element).attr('src', base + 'wildflower/thumbnail_by_id/' + asset_id + '/50/50/1');
                $('.group-editor-image', element).val(asset_id);
            } else if (element.hasClass('file-block')) {
                $('.group-editor-file', element).val(asset_id);
                $('span', element).html($('#asset_chooser li.selected span').html());
            }
        }
    }
    
    $('#dialog').dialog({
        open: function(event, ui) { 
            var element = $(groupEditor.currentGroupElement).parent();
            var type;
            if (element.hasClass('content-block')) {
                type = 'image';
                $('#dialog').dialog('option', 'title', 'Choose an image');
            } else {
                type = 'file';
                $('#dialog').dialog('option', 'title', 'Choose a file');
            }
            
            $('#dialog').html('<div class="loading">Loading...</div>');
            $('#dialog').load(base + '/wf/assets/choose/' + type, function(){
                // #asset_chooser li
                $('#asset_chooser li').click(function(){
                    $('#asset_chooser li').removeClass('selected');
                    $(this).addClass('selected');
                });
                $('#asset_chooser li').dblclick(function(){
                    $('#asset_chooser li').removeClass('selected');
                    $(this).addClass('selected');  
                    saveAssetChoice();  
                    $('#dialog').dialog('close');
                });
            });
        },
    	autoOpen: false,
        modal: true,
    	width: 660,
        height: 415,
    	buttons: {
    		"OK": function() { 
    			$(this).dialog("close"); 
                saveAssetChoice();
    		}, 
    		"Cancel": function() { 
    			$(this).dialog("close"); 
    		} 
    	}
    });
});


</script>
<style>
    .mceEditor {
        margin-top: 1em;
    }
    .content-block, .file-block {
        padding-bottom: 10px;
        border-bottom: 1px solid #ccc;
        margin-bottom: 10px;
    }
    .content-block textarea {
        width: 99%;
        height: 200px;
        margin-top: 10px;
    }
    .content-block img {
        vertical-align:middle;
    }
    .move-up, .move-down, .delete-section {
        width: 11px;
        height: 11px;
        display:block;
        text-indent: -1000em;
        overflow: hidden;
        float: left;
        background-repeat: no-repeat;
        background-position: 1px 1px;
        padding: 1px;
    }
    
    .move-up {
        background-image: url(/cms/img/admin/up.gif);        
    }
    
    .move-down {
        background-image: url(/cms/img/admin/down.gif);        
    }
    
    .delete-section {
        background-image: url(/cms/img/admin/delete.gif);        
    }
    .loading {
        font-size: 1.3em;
        text-align: center;
        padding-top: 30px;
    }
    
    #asset_chooser {
        list-style-type: none;
        margin: 0;
        padding: 0;
    }
    
    #asset_chooser li {
        display: block;
        float: left;
        width: 120px;
        border: 1px solid #ccc;
        padding: 10px;
        height: 130px;
        margin-right: 10px;
        margin-bottom: 10px;
        overflow: hidden;
        text-align: center;
    }
    #asset_chooser li:hover, #asset_chooser li.hover {
        background-color: #eee;
    }
    #asset_chooser li.selected {
        background-color: #ddd;
        border-color: #aaa;
    }
    
    .alignment img {
        border: 2px solid #fff;
    }
    .alignment img.hover {
        border: 2px solid #ccc;
    }    
    .alignment img.selected {
        border: 2px solid #999;
    }        
</style>

<div id="title-content">
    <?php
        echo
        $form->input('title', array(
            'between' => '<br />',
            'tabindex' => '1',
            'label' => __('Page title', true),
            'div' => array('class' => 'input title-input'))),
        $form->input('content', array(
            'type' => 'textarea',
            'tabindex' => '2',
            'class' => 'group_editor',
            'rows' => '25',
            'label' => __('Body', true),
            'div' => array('class' => 'input editor'))),
        '<div>',
        $form->hidden('id'),
        $form->hidden('draft'),
        '</div>';
    ?>
    
    <div id="edit-buttons">
        <?php echo $this->element('wf_edit_buttons'); ?>
    </div>
</div>

<div id="post-revisions">
    <h2 class="section">Older versions of this page</h2>
    <?php 
        if (!empty($revisions)) {
            echo 
            '<ul id="revisions" class="list revision-list">';

            $first = '<span class="current-revision">&mdash;current version</span>';
            foreach ($revisions as $version) {
                $attr = '';
                if (ListHelper::isOdd()) {
                    $attr = ' class="odd"';
                }
                echo 
                "<li$attr>",
                '<div class="list-item">',
                $html->link("Revision {$version['WildRevision']['revision_number']}",
                    array('action' => 'wf_edit', $version['WildRevision']['node_id'], $first ? null : $version['WildRevision']['revision_number']), null, null, false),
                "<small>$first, saved {$time->niceShort($version['WildRevision']['created'])} by {$version['WildUser']['name']}</small>",
                '</div>',
                '</li>';
                $first = '';
            }
            echo '</ul>';
        } else {
            echo "<p id=\"revisions\">No revisions yet.</p>";
        }
    ?>        
</div>

<?php 
    echo 
    
    // Options for create new JS
	$form->input('parent_id_options', array('type' => 'select', 'options' => $newParentPageOptions, 'empty' => '(none)', 'div' => array('class' => 'all-page-parents input select'), 'label' => __('Parent page', true), 'escape' => false)),
	
	$form->end();
?>

<?php $partialLayout->blockStart('sidebar'); ?>
    <li>
        <?php echo $this->element('../wild_pages/_sidebar_search'); ?>
    </li>
    <li>
        <?php echo $html->link(
            '<span>Write a new page</span>', 
            array('action' => 'wf_create'),
            array('class' => 'add', 'escape' => false)); ?>
    </li>
    <li>
        <ul class="sidebar-menu-alt edit-sections-menu">
            <li><?php echo $html->link('Options <small>like status, publish date, etc.</small>', array('action' => 'options', $this->data['WildPage']['id']), array('escape' => false)); ?></li>
            <li><?php echo $html->link('Browse older versions', '#Revisions', array('rel' => 'post-revisions')); ?></li>
        </ul>
    </li>
    <li class="sidebar-box post-info">
        <?php echo $this->element('../wild_pages/_page_info'); ?>
    </li>
<?php $partialLayout->blockEnd(); ?>
