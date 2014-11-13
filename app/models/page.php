<?php
/**
 * Created by CNR.
 * User: nike
 * Date: 22.07.11
 * Time: 12:59
 */
 
class Page extends AppModel
{
    public $name = "Page";

    //public $locale = 'eng';


    public function getInfo($key, $uID = null)
    {
        $conditions['key'] = $key;
        if (!empty($uID)) $conditions['user_id'] = $uID;
        $data = $this->find('first', array(
          'conditions' => $conditions,
          'fields' => array(
              'meta_title', 'meta_keywords', 'meta_description', 'title', 'content'
          )
        ));
        $dataMeta = $dataContent = false;
        if ($data) {
            $dataMeta['meta_title'] = $data['Page']['meta_title'];
            $dataMeta['meta_keywords'] = $data['Page']['meta_keywords'];
            $dataMeta['meta_description'] = $data['Page']['meta_description'];
            $dataContent['title'] = $data['Page']['title'];
            $dataContent['content'] = $data['Page']['content'];
        }

        return array(
            "meta"     => $dataMeta,
            "pageInfo" => $dataContent,
        );
    }


    public function getAllActivePages()
    {
        $this->locale = "eng";
        $data = $this->find('all', array(
                                        'conditions' => array(
                                            'active' => 1,
                                        ),
                                        'fields' => array(
                                            'key', 'title'
                                        ),
                                        'order' => array(
                                            'type', 'key'
                                        ),
                                   ));
        $results = array();
        foreach ($data as $item) {
            $url = "/{$item['Page']['key']}.html";
            $results[$url] = $item['Page']['title'] . ' page';
        }
        return $results;
    }

    public function getAllUserPages($uID)
    {
        $data = $this->find('all', array(
            'conditions' => array(
                'active' => 1,
                'user_id' => $uID,
            ),
            'fields' => array(
                'key', 'title', 'id', 'content', 'created'
            ),
            'order' => array(
                'created DESC'
            ),
        ));

        $data = Set::extract('/Page/.', $data);

        if ($data) {
            foreach ($data as &$page)
                $page['content'] = strip_tags(substr($page['content'], 0, 170));
        }

        return $data;


    }
}
