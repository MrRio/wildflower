<?php
class WildAssetsController extends WildflowerAppController {
	
	public $helpers = array('Cache');
	public $components = array('RequestHandler', 'Wildflower.JlmPackager');
	public $paginate = array(
        'limit' => 12,
        'order' => array('created' => 'desc')
    );
	
    function wf_choose($type = 'file') {
        $image_types = array('image/jpeg', 'image/gif', 'image/png');
        switch($type) {
            case 'image':
                $assets = $this->WildAsset->findAll(array('mime' => $image_types));
                break;
                
            case 'file':
                $assets = $this->WildAsset->findAll(array('NOT' => array('mime' => $image_types)));
                break;
        }
        $this->set('type', $type);
        $this->set('assets', $assets);
    }
    
	function wf_create() {
	    $this->WildAsset->create($this->data);
	    
	    if (!$this->WildAsset->validates()) {
	        $this->feedFileManager();
	        return $this->render('wf_index');
	    }
	    
	    // Check if file with the same name does not already exist
	    $fileName = trim($this->data[$this->modelClass]['file']['name']);
        $uploadPath = Configure::read('Wildflower.uploadDirectory') . DS . $fileName;
        
        // Rename file if already exists
        $i = 1;
        while (file_exists($uploadPath)) {
            // Append a number to the end of the file,
            // if it alredy has one increase it
            $newFileName = explode('.', $fileName);
            $lastChar = mb_strlen($newFileName[0], Configure::read('App.encoding')) - 1;
            if (is_numeric($newFileName[0][$lastChar]) and $newFileName[0][$lastChar - 1] == '-') {
                $i = intval($newFileName[0][$lastChar]) + 1;
                $newFileName[0][$lastChar] = $i;
            } else {
                $newFileName[0] = $newFileName[0] . "-$i";
            }
            $newFileName = implode('.', $newFileName);
            $uploadPath = Configure::read('Wildflower.uploadDirectory') . DS . $newFileName;
            $fileName = $newFileName;
        }
   
        // Upload file
        $isUploaded = @move_uploaded_file($this->data[$this->modelClass]['file']['tmp_name'], $uploadPath);
        
        if (!$isUploaded) {
            $this->WildAsset->invalidate('file', 'File can`t be moved to the uploads directory. Check permissions.');
            $this->feedFileManager();
            return $this->render('wf_index');
        }
        
        // Make this file writable and readable
        chmod($uploadPath, 0777);
        
        $this->WildAsset->data[$this->modelClass]['name'] = $fileName;
        if (empty($this->WildAsset->data[$this->modelClass]['title'])) {
            $this->WildAsset->data[$this->modelClass]['title'] = str_replace(array('.jpg', '.jpeg', '.gif', '.png'), array('', '', '', ''), $fileName);
        }
        $this->WildAsset->data[$this->modelClass]['mime'] = $this->WildAsset->data[$this->modelClass]['file']['type'];
        
        $this->WildAsset->save();
        
        $this->redirect($this->referer(Configure::read('Wildflower.prefix') . '/assets'));
	}

	/**
	 * Files overview
	 *
	 */
	function wf_index() {
        $this->feedFileManager();
	}
	
	/**
	 * Delete an upload
	 *
	 * @param int $id
	 */
	 // @TODO make require a POST
	function wf_delete($id) {
	    $this->WildAsset->delete($id);
		$this->redirect(array('action' => 'index'));
	}
	
	/**
	 * Edit a file
	 *
	 * @param int $id
	 */
	function wf_edit($id) {
		$this->data = $this->WildAsset->findById($id);
		$this->pageTitle = $this->data[$this->modelClass]['title'];
	}
	
	/**
	 * Insert image dialog
	 *
	 * @param int $limit Number of images on one page
	 */
	function wf_insert_image($limit = 8) {
		$this->layout = '';
		$this->paginate['limit'] = intval($limit);
		$this->paginate['conditions'] = "{$this->modelClass}.mime LIKE 'image%'";
		$images = $this->paginate($this->modelClass);
		$this->set('images', $images);
	}
	
	function wf_browse_images() {
		$this->paginate['limit'] = 6;
		$this->paginate['conditions'] = "{$this->modelClass}.mime LIKE 'image%'";
		$images = $this->paginate($this->modelClass);
		$this->set('images', $images);
	}
	
	function wf_update() {
	    $this->WildAsset->create($this->data);
	    if (!$this->WildAsset->exists()) return $this->cakeError('object_not_found');
	    $this->WildAsset->saveField('title', $this->data[$this->modelClass]['title']);
	    $this->redirect(array('action' => 'edit', $this->WildAsset->id));
	}
	
	function beforeFilter() {
		parent::beforeFilter();
		
		// Upload limit information
        $postMaxSize = ini_get('post_max_size');
        $uploadMaxSize = ini_get('upload_max_filesize');
        $size = $postMaxSize;
        if ($uploadMaxSize < $postMaxSize) {
            $size = $uploadMaxSize;
        }
        $size = str_replace('M', 'MB', $size);
        $limits = "Maximum allowed file size: $size";
        $this->set('uploadLimits', $limits);
	}
    
    /**
     * Output parsed JLM javascript file
     *
     * The output is cached when not in debug mode.
     */
    function wf_jlm() {
        $javascripts = Cache::read('wf_jlm'); 
        if (empty($javascripts) or Configure::read('debug') > 0) {
            $javascripts = $this->JlmPackager->concate();
            Cache::write('wf_jlm', $javascripts);
        }
        
        $this->layout = false;
        $this->set(compact('javascripts'));
        $this->RequestHandler->respondAs('application/javascript');
        
        $cacheSettings = Cache::settings();
        $file = CACHE . $cacheSettings['prefix'] . 'wf_jlm';
        $this->JlmPackager->browserCacheHeaders(filemtime($file));
        
        Configure::write('debug', 0);
    }
    
    /**
     * Force the download of an asset.
     * @return 
     * @param $id Object
     */
    function asset_by_id($id) {
        $this->view = 'Media';
        $asset = $this->WildAsset->read(null, $id);
        
        $params = array(
            'id' => $asset['WildAsset']['name'],
            'name' => $asset['WildAsset']['title'],
            'download' => true,
            'path' => 'webroot' . DS . 'uploads' . DS
        );
        $this->set($params);
    }
    
    function thumbnail_by_id($id, $width = 120, $height = 120, $crop = 0) {
        $asset = $this->WildAsset->read(null, $id);
        $this->thumbnail($asset['WildAsset']['name'], $width, $height, $crop);
        exit;
    }
    
    /**
     * TODO: Send image caching headers
     * @return 
     * @param $id Object
     */
    function icon_by_id($id) {
        $asset = $this->WildAsset->read(null, $id);
        preg_match("/\.([^\.]+)$/", $asset['WildAsset']['name'], $matches);
        $extension = $matches[1];          
        $this->layout = null; 
        $path = APP . 'webroot' . DS . 'img' . DS . 'mime' . DS . 'smaller' . DS;
        header("Content-type: image/png");
        if(file_exists($path . $extension . '.png')) {
            echo file_get_contents($path . $extension . '.png');
            exit;
        } else {
            echo file_get_contents($path . 'file.png');
            exit;            
        }
    }    
    
    /**
     * Create a thumbnail from an image, cache it and output it
     *
     * @param $imageName File name from webroot/uploads/
     */
    function thumbnail($imageName, $width = 120, $height = 120, $crop = 0) {
        preg_match("/\.([^\.]+)$/", $imageName, $matches);
        $extension = $matches[1];    
        $image_types = array('jpg', 'jpeg', 'png', 'bmp', 'gif');
        if(!in_array($extension, $image_types)) {
        $this->layout = null; 
            $path = APP . 'webroot' . DS . 'img' . DS . 'mime' . DS . 'smaller' . DS;
            header("Content-type: image/png");
            if(file_exists($path . $extension . '.png')) {
                echo file_get_contents($path . $extension . '.png');
                exit;
            } else {
                echo file_get_contents($path . 'file.png');
                exit;            
            }            
        }
        $this->autoRender = false;
        header("Expires: ".gmdate("D, d M Y H:i:s", mktime(date("H"), date("i"), date("s"), date("m"), date("d"), date("Y") + 3))." GMT");
        
        $imageName = str_replace(array('..', '/'), '', $imageName); // Don't allow escaping to upper directories

        $width = intval($width);
        if ($width > 2560) {
        	$width = 2560;
        }

        $height = intval($height);
        if ($height > 1600) {
        	$height = 1600;
        }

        $cachedFileName = join('_', array($imageName, $width, $height, $crop)) . '.jpg';
        $cacheDir = Configure::read('Wildflower.thumbnailsCache');
        $cachedFilePath = $cacheDir . DS . $cachedFileName;

        $refreshCache = false;
        $cacheFileExists = file_exists($cachedFilePath);
        if ($cacheFileExists) {
        	$cacheTimestamp = filemtime($cachedFilePath);
        	$cachetime = 60 * 60 * 24 * 14; // 14 days
        	$border = $cacheTimestamp + $cachetime;
        	$now = time();
        	if ($now > $border) {
        		$refreshCache = true;
        	}
        }

        if ($cacheFileExists && !$refreshCache) {
        	return $this->_renderJpeg($cachedFilePath);
        } else {
        	// Create cache and render it
        	$sourceFile = Configure::read('Wildflower.uploadDirectory') . DS . $imageName;
        	if (!file_exists($sourceFile)) {
        		return trigger_error("Thumbnail generator: Source file $sourceFile does not exists.");
        	}

        	App::import('Vendor', 'phpThumb', array('file' => 'phpthumb.class.php'));

        	$phpThumb = new phpThumb();

        	$phpThumb->setSourceFilename($sourceFile);
        	$phpThumb->setParameter('config_output_format', 'jpeg');

        	$phpThumb->setParameter('w', intval($width));
        	$phpThumb->setParameter('h', intval($height));
        	$phpThumb->setParameter('zc', intval($crop));

        	if ($phpThumb->GenerateThumbnail()) {
        		$phpThumb->RenderToFile($cachedFilePath);
        		return $this->_renderJpeg($cachedFilePath);
        	} else {
        		return trigger_error("Thumbnail generator: Can't GenerateThumbnail.");
        	}
        }
    }
    
    function _renderJpeg($cachedFilePath) {
        $this->JlmPackager->browserCacheHeaders(filemtime($cachedFilePath), 'image/jpeg');
        
        $fileSize = filesize($cachedFilePath);
        header("Content-Length: $fileSize");
        
        $cache = fopen($cachedFilePath, 'r');
        fpassthru($cache);
        fclose($cache);
    }
	
	private function feedFileManager() {
	    $this->pageTitle = 'Files';
	    $files = $this->paginate($this->modelClass);
        $this->set(compact('files'));
	}
    
}
