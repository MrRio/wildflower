<?php 
App::import('Model', 'Wildflower.WildComment');

class WildCommentTestCase extends CakeTestCase {
    // I spend 2 hours figuring out the plugin.wildflower.model convention.
    // Without writing it like this your fixtures won't load.
    public $fixtures = array(
        'plugin.wildflower.wild_comment', 
        'plugin.wildflower.wild_user',
        'plugin.wildflower.wild_category',
        'plugin.wildflower.categories_post',
    );
    private $WildComment;
    
    function startTest() {
        $this->WildComment = ClassRegistry::init('Wildflower.WildComment');
    }
    
    function endTest() {
        unset($this->WildComment);
    }

    function testValidSave() {
        $data = array(
            'post_id' => 2,
            'name' => '<strong>VALID</strong> <script>alert("hello");</script> new comment',
            'email' => 'bananova_repuplika@hotmail.com',
            'url' => 'www.banany-su-zdrave.sk',
            'content' => 'Some english text.'
            );
        
        $this->WildComment->spamCheck = false;
        $result = $this->WildComment->save($data);
        unset($result[$this->WildComment->name]['created'], $result[$this->WildComment->name]['updated']);
        $expected = array(
            'post_id' => 2,
            'name' => 'VALID alert("hello"); new comment',
            'email' => 'bananova_repuplika@hotmail.com',
            'url' => 'http://www.banany-su-zdrave.sk',
        'content' => 'Some english text.',
            'spam' => 0
            );
        $this->assertEqual($expected, $result[$this->WildComment->name]);
    }

    function testInvalidSave() {
        $this->WildComment->spamCheck = false;

        $data = array(
            'post_id' => 2,
            'name' => 'inVALID new comment',
            'email' => 'this is not an email address',
            'url' => 'www.banany-su-zdrave.sk',
            'content' => 'Some english text.'
            );

        $result = $this->WildComment->save($data);
        $this->assertFalse($result);

        $data = array(
            'post_id' => 2,
            'name' => '',
            'email' => 'a@address.com',
            'url' => 'www.banany-su-zdrave.sk',
            'content' => 'Some english text.'
            );
        $result = $this->WildComment->save($data);
        $this->assertFalse($result);

        $data = array(
            'post_id' => 2,
            'name' => 'asdas asd as',
            'email' => 'a@address.com',
            'url' => 'www.banany-su-zdrave.sk',
            'content' => '   '
            );
        $result = $this->WildComment->save($data);
        $this->assertFalse($result);

        $data = array(
            'post_id' => 20,
            'name' => '<script></script>',
            'email' => 'a@address.com',
            'url' => 'www.banany-su-zdrave.sk',
            'content' => ' sa '
            );
        $result = $this->WildComment->save($data);
        $this->assertFalse($result);
    }

    function testCommentDelete() {
    }

}
