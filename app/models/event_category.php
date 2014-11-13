<?php

class EventCategory extends AppModel
{
    public $name = 'EventCategory';

    public $hasMany = array('Event');

    public $actsAs = array('Tree', 'Translate' => array('title'), 'Containable');

    public $locale = 'eng';

    public $languages = array(
        'eng' => 'English',
        'por' => 'Portuguese',
        'spa' => 'Spanish',
    );

    /**
     * Return event category item data
     * @param  $id
     * @return array | false
     */
    public function getItem($id)
    {
        $this->locale = Configure::read('Config.language');
        $data = $this->read(
            array(
                'id', 'title'
            )
            , $id);
        $this->i18dataLoad(&$data, $this->languages, array('title'));

        return isset($data) ? $data : false;
    }


}

?>