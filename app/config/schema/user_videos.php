<?php 
/* user_videos schema generated on: 2013-03-19 13:22:16 : 1363713736*/
class user_videosSchema extends CakeSchema {
	var $name = 'user_videos';

	function before($event = array()) {
		return true;
	}

	function after($event = array()) {
	}

    var $user_videos = array(
//        'id' => array('type' => 'integer', 'null' => false, 'default' => NULL, 'key' => 'primary'),
        //'title' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 200, 'collate' => 'latin1_swedish_ci', 'charset' => 'latin1'),
        //'description' => array('type' => 'text', 'null' => true, 'default' => NULL, 'collate' => 'latin1_swedish_ci', 'charset' => 'latin1'),
        'url_video' => array('type' => 'text', 'null' => true, 'default' => NULL, 'collate' => 'latin1_swedish_ci', 'charset' => 'latin1'),
        //'user_id' => array('type' => 'integer', 'null' => true, 'default' => NULL),
        //'created' => array('type' => 'datetime', 'null' => true, 'default' => NULL),
        //'indexes' => array('PRIMARY' => array('column' => 'id', 'unique' => 1)),
        //'tableParameters' => array()
    );
}
?>