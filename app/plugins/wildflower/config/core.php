<?php
/**
 * Wildflower plugin core configuration
 *
 * This file is automatically loaded by WF's bootstrap.php.
 * 
 * @package wildflower
 */

/** Constant used in CmsHelper */
define('CHILD_PAGES_PLEASE', 'CHILD_PAGES_PLEASE');

/** Wildflower cache path */
define('WILDFLOWER_CACHE', WILDFLOWER_PLUGIN . DS . 'tmp' . DS . 'cache');

/** Wildflower config. Access like Configure::read('Wildflower.settingName'); */
Configure::write(array('Wildflower' => array(
    'cookie' => array(
        'name' => 'WildflowerUser',
        'expire' => 2592000,
    ),
    'gzipOutput' => false,
    'uploadsDirectoryName' => 'uploads',
    'uploadDirectory' => APP . WEBROOT_DIR .  DS . 'uploads', // @TODO rename the key
    'prefix' => 'wf',
    'rootPageCache' => CACHE . 'wf_root_pages',
    'previewCache' => CACHE . 'wf_previews' . DS,
    'thumbnailsCache' => WILDFLOWER_CACHE . DS . 'wf_thumbnails',
    'postsParent' => 'p',
    'blogIndex' => 'blog',
    // Disabling the root page cache may be useful in debugging 
    // (the cache file won't be created, page routes load from the database)
    'disableRootPageCache' => false,
)));
