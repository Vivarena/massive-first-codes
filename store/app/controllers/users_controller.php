<?php
class UsersController extends AppController
{
	public $name = 'Users';

	function login()
	{
     //  exit( $this->Auth->password('admin'));
        $this->layout="login";
	}


	function logout()
	{
        $this->Auth->logout();
        $this->redirect('/');
	}
}