<?php


/**
 * Static content controller
 * @package       cake
 * @subpackage    cake.cake.libs.controller
 *
 * @property Banner $Banner
 * @property ContactSubmission $ContactSubmission
 * @property Event $Event
 * @property EventCategory $EventCategory
 * @property Page $Page
 * @property SiteMenu $SiteMenu
     * @property Menu $Menu
 * @property RequestHandlerComponent $RequestHandler
 * @property SessionComponent $Session
 * @property EmailComponent $Email
 *
 */
class PagesController extends AppController
{

    /**
     * Controller name
     *
     * @var string
     * @access public
     */
    var $name = 'Pages';

    /**
     * Default helper
     *
     * @var array
     * @access public
     */
    var $helpers = array('Html');

    /**
     * Components initializing
     *
     * @var array
     * @access public
     */
    var $components = array('Email', 'RequestHandler');

    var $uses = array('Page');

    /**
     * Displays a view
     *
     * @param mixed What page to display
     * @access public
     */
    function display() {

        //$this->layout = 'display_text';
        if ($this->Auth->user('id')) {
            $this->layout = 'community';
        } else $this->layout = 'default_static';

        $key = $this->params['key'];
        $data = $this->Page->getInfo($key);
        $this->_setMetaTags($data['meta']);
        if ($data['pageInfo'] == false) {
            $this->redirect("/");
        }


        $this->set('page', $data['pageInfo']);
    }


    public function index() {

        if ($this->Auth->user('id')) {
            $this->redirect('/community');
        }
        $this->set('currentYear', date('Y'));

        $this->loadModel('UserType');
        $this->set('user_types', $this->UserType->find('list'));

    }

    public function faq() {
        $this->layout = 'display_text';
        $this->set('page', array('title' => 'FAQS'));
    }


    public function contact() {
        //$this->autoRender = false;

        $this->layout = 'static';

        $this->set('first_captcha', uniqid());

        $this->set(
            array(
                'infoEmail' => SiteConfig::read("info_email"),
                'salesEmail' => SiteConfig::read("sales_email"),
                'phone' => SiteConfig::read("phone"),
                'address' => SiteConfig::read("address"),
                'google_key' => SiteConfig::read("google_key")
            )
        );

        if ($this->data) {
            $type = (isset($this->data['FeedBack'])) ? 'FeedBack' : 'Page';
            $fromPage = ($type == 'FeedBack') ? 'Feedback' : 'Contact Us';
            $this->data['ContactSubmission'] = $this->data[$type];
            $this->loadModel('ContactSubmission');
            $this->ContactSubmission->set($this->data);
            $out = array('status' => false);
            if (isset($_SESSION['KCaptcha']) && $_SESSION['KCaptcha'] != $this->data['ContactSubmission']['captcha'] && $type != 'FeedBack') {
                $this->ContactSubmission->invalidate("captcha");
                $this->ContactSubmission->validationErrors['captcha'] = 'Incorrect string';
                $out['errors'] = $this->ContactSubmission->validationErrors;
                $this->_setFlash('Incorrect captcha!', 'error');
            }

            if ($this->ContactSubmission->save()) {
                $this->SwiftMailer->to = SUPPORTEMAIL;
                $this->SwiftMailer->from = 'no_reply@vivarena.com';
                if (isset($this->data['ContactSubmission']['Send_cc']))
                    $this->SwiftMailer->cc = $this->data['ContactSubmission']['email'];
                $this->SwiftMailer->fromName = $this->data['ContactSubmission']['first_name'];
                unset($this->data['ContactSubmission']['captcha']);
                unset($this->data['ContactSubmission']['send_cc']);
                $pre_message = '';
                foreach ($this->data['ContactSubmission'] as $key => $text) {
                    $pre_message .= '<strong>' . Inflector::humanize($key) . ':</strong>&nbsp;' . $text . '<br />' . PHP_EOL;
                }
                $message = <<<EOF
<h2>This email has come from the page "$fromPage"</h2>
<br>
{$pre_message}
EOF;

                $out['status'] = true;
                $this->set('data', $message);

                try {
                    if ($this->SwiftMailer->send('default', $fromPage)) {
                        $this->_setFlash('Thank you for your request!', 'success');
                    } else {
                        $out['errors'] = array('mailer' => 'error while sending mail');
                        $this->_setFlash('Error', 'error');
                    }
                } catch (Exception $e) {
                    $out['errors'] = array('mailer' => $e->getMessage());
                    $this->_setFlash('Error', 'error');
                    $this->log($e->getMessage());
                    $this->log($e->getTraceAsString());
                }

                $this->data = null;
            } else {
                $this->_setFlash('Error while sending email', 'error');
            }
            if ($this->RequestHandler->isAjax()) {
                Configure::write('debug', 0);
                $this->autoRender = false;
                $this->layout = false;
                $this->Session->write('FlashMessage', '');
                exit(json_encode($out));
            } else {
                $this->redirect("/");
            }
        }
        $this->set('_tag_meta_title', "Contact Us");
    }
}
