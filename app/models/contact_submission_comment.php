<?php
class ContactSubmissionComment extends AppModel
{
	var $belongsTo = array(
		'ContactSubmission' => array(
			'counterCache' => true
		),
        'User'
	);
}
?>