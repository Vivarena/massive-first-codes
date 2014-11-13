<?php

/**
 *
 * @property ContactSubmission        $ContactSubmission
 * @property ContactSubmissionComment $ContactSubmissionComment
 *
 */

class AdminContactFormSubmissionsController extends AdminAppController
{
    public $uses = array('Admin.ContactSubmission', 'Admin.ContactSubmissionComment');
    public $helpers = array("Javascript");

    public function beforeFilter()
    {
        parent::beforeFilter();
        $this->_setHoverFlag('contact_submition');
    }

    function index()
    {
         $this->layout = "default_1";
    }

    function ajax_get_list()
    {
	$this->autoRender = false;
        Configure::write('debug', 0);

        $list = $this->ContactSubmission->find('all', array('order' => 'ContactSubmission.created DESC', 'limit' => 20));

        echo json_encode(array('results' => $this->_prettyList($list)));
    }

    function ajax_search_list()
    {
		$this->autoRender = false;
        Configure::write('debug', 0);

        if(isset($this->data['Search']['query'])) {
            $list = $this->ContactSubmission->find('all', array(
                'conditions' => array('OR' => array(
                    'ContactSubmission.first_name    LIKE' => "%{$this->data['Search']['query']}%",
                    'ContactSubmission.last_name     LIKE' => "%{$this->data['Search']['query']}%",
                    'ContactSubmission.email         LIKE' => "%{$this->data['Search']['query']}%",
                    'ContactSubmission.comment       LIKE' => "%{$this->data['Search']['query']}%"
                )),
                'order' => 'ContactSubmission.created DESC', 'limit' => 20)
            );

            echo json_encode(array('results' => $this->_prettyList($list)));
        }
    }

    private function _prettyList($list)
    {
        foreach($list as $key => $item) {
            $list[$key] = $item['ContactSubmission'];
            $list[$key]['pretty_date'] = date('F d, Y, h:iA', strtotime($list[$key]['created']));
            $list[$key]['num_comments'] = $list[$key]['contact_submission_comment_count'];
        }

        return $list;
    }

    function view($id)
    {
        $this->layout = "default_1";
        $this->set('info', $this->ContactSubmission->read(null, $id));
        $this->ContactSubmission->saveField('num_views', ++$this->viewVars['info']['ContactSubmission']['num_views']);
    }

    function ajax_get_comments_list($submissionId)
    {
		$this->autoRender = false;
        Configure::write('debug', 0);

        $list = $this->ContactSubmissionComment->find(
            'all',
            array(
                'order' => 'ContactSubmissionComment.created ASC',
                'conditions' => array(
                    'ContactSubmissionComment.contact_submission_id' => $submissionId
                )
            )
        );

        foreach($list as $key => $item) {
            $list[$key] = $item['ContactSubmissionComment'];
            $list[$key]['pretty_date'] = date('F d, Y, h:iA', strtotime($list[$key]['created']));
			if(isset($item['User'])) {
            	$list[$key]['author_email'] = $item['User']['email'];
			}
        }

        echo json_encode(array('results' => $list));
    }

    function ajax_add_comment($submissionId)
    {
		$this->autoRender = false;
        //Configure::write('debug', 0);

        if(!empty($this->data['Comment'])) {
            $this->ContactSubmissionComment->save(
                array(
                    'user_id' => $this->Auth->user('id'),
                    'contact_submission_id' => $submissionId,
                    'comment' => $this->data['Comment']
                )
            );
        }
        
        $data = $this->ContactSubmission->read('contact_submission_comment_count', $submissionId);

        $this->ContactSubmission->id = $submissionId;

        $this->ContactSubmission->saveField('contact_submission_comment_count', ++$data['ContactSubmission']['contact_submission_comment_count']);

        echo 'OK';
    }

    public function ajax_delete() {
        if (   isset($this->data["ContactSubmission"]["id"])
               && !is_null($this->data["ContactSubmission"]["id"])
               && is_numeric($this->data["ContactSubmission"]["id"])) {
            //======================================================//
            //==================== Data Deleting ===================//
            //======================= FROM DB ======================//
            //======================================================//
            if ($this->ContactSubmission->delete($this->data["ContactSubmission"]["id"], true)) {
                $this->ContactSubmissionComment->deleteAll(
                    array(
                         "ContactSubmissionComment.contact_submission_id" => $this->data["ContactSubmission"]["id"]
                    )
                );
                exit(json_encode(array('status'=> true)));
            } else {
                exit(json_encode(array('status'=> false)));
            }
            //======================================================//
            //================== End Data Deleting =================//
            //======================= FROM DB ======================//
            //======================================================//
        }

    }
}