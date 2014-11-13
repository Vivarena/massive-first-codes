<?php
class ThumbsAppController extends AppController
{
    function beforeFilter()
    {
        //parent::beforeFilter();

        if($this->Auth) {
            $this->Auth->allow('*');
        }
    }
}