<?php
class WildPost extends WildflowerAppModel {
    
	public $actsAs = array(
	   'Containable',
	   'Wildflower.Slug' => array('separator' => '-', 'overwrite' => false, 'label' => 'title'),
	   'Wildflower.Versionable' => array('title', 'content', 'description_meta_tag', 'keywords_meta_tag')
	);
	
	public $belongsTo = array(
	   'WildUser' => array(
	       'className' => 'Wildflower.WildUser'
	   )
	);
	
	public $hasAndBelongsToMany = array(
	   'WildCategory' => array(
	       'className' => 'Wildflower.WildCategory',
	       'joinTable' => 'categories_posts',
	       'foreignKey' => 'post_id',
	       'associationForeignKey' => 'category_id'
	   )
	);
	
	public $hasMany = array(
	   'WildComment' => array(
	       'className' => 'Wildflower.WildComment',
	       'conditions' => 'WildComment.spam = 0',
	       'order' => 'WildComment.created ASC'
	   )
	);
	
	public static $statusOptions = array(
       '0' => 'Published',
       '1' => 'Draft'
    );
    
    /**
     * Get URL to a post, suitable for $html->url() and likes
     *
     * @param string $uuid
     * @return string
     */
    static function getUrl($uuid) {
        $url = '/' . Configure::read('Wildflower.postsParent') . '/' . $uuid;
        return $url;
    }

    /**
     * Mark a post as a draft
     *
     * @param int $id
     */
    function draft($id) {
        $id = intval($id);
        return $this->query("UPDATE {$this->useTable} SET draft = 1 WHERE id = $id");
    }
    
    /**
     * 
     * @deprecated This logic better suits into controller
     */
    function getCategoryScope($slug) {
    	// Find all post IDs from this cat
    	$this->Category->Behaviors->attach('Containable');
    	$this->Category->contain("{$this->name}.id");
    	$category = $this->Category->findBySlug($slug);

    	// Retrive these posts with associations
    	$ids = array();
    	foreach ($category['Post'] as $post) {
    		$ids[] = $post['id'];
    	}
    	
    	$in = implode(', ', $ids);
    	$scope = "{$this->name}.id IN ($in)";
    	//$this->Behaviors->attach('Containable');
    	//$this->contain(array('User' => array('id', 'name'), 'Comment' => array('id'), 'Category' => array('id', 'title', 'slug')));
    	
    	return $scope;
    }

    function getStatusOptions() {
        return self::$statusOptions;
    }

    /**
     * Publish a post (unmark draft status)
     *
     * @param int $id
     */
    function publish($id) {
        $id = intval($id);
        return $this->query("UPDATE {$this->useTable} SET draft = 0 WHERE id = $id");
    }
    
    function regenerateUuids() {
        $posts = $this->find('all', array(
            'conditions' => "uuid IS NULL OR uuid = ''",
            'fields' => array('id'),
            'recursive' => -1,
        ));
        
        foreach ($posts as $post) {
            $this->create($post);
            $this->saveField('uuid', sha1(String::uuid()));
        }
    }
    
	/**
     * Search title and content fields
     *
     * @param string $query
     * @return array
     */
    function search($query) {
    	$fields = array('id', 'title', 'slug');
    	$titleResults = $this->findAll("{$this->name}.title LIKE '%$query%'", $fields, null, null, 1);
    	$contentResults = array();
    	if (empty($titleResults)) {
    		$titleResults = array();
			$contentResults = $this->findAll("MATCH ({$this->name}.content) AGAINST ('$query')", $fields, null, null, 1);
    	} else {
    		$alredyFoundIds = join(', ', Set::extract($titleResults, '{n}.WildPost.id'));
    	    $notInQueryPart = '';
            if (!empty($alredyFoundIds)) {
                $notInQueryPart = " AND {$this->name}.id NOT IN ($alredyFoundIds)";
            }
    		$contentResults = $this->findAll("MATCH ({$this->name}.content) AGAINST ('$query')$notInQueryPart", $fields, null, null, 1);
    	}
    	
    	if (!is_array(($contentResults))) {
    		$contentResults = array();
    	}
    	
    	$results = array_merge($titleResults, $contentResults);
    	return $results;
    }
    
}
