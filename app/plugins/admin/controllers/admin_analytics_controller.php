<?php
/**
 *
 * @author Mike S.
 * @created 17-05-2012
 *
 * @property User $User
 * @property UserInfo $UserInfo
 */
class AdminAnalyticsController extends AdminAppController
{
    public $name = 'AdminAnalytics';
    public $helpers = array('GoogleChart');


    public $uses = array('User', 'UserInfo', 'UserPrivateInfo');

    public function beforeFilter()
    {
        parent::beforeFilter();
        $this->_setHoverFlag('analytics');
        $this->_setLeftMenu('analytics');
    }


    public function index()
    {
        $limit = 100;

        $this->_setHoverFlag('index');

        $filterFields = array(
            'UserInfo.username' => 'User name',
            'User.email' => 'email',
            'User.lang' => 'Language',
            'UserInfo.sex' => 'Gender',
            'UserPrivateInfo.industry' => 'Industry',
            'UserPrivateInfo.company' => 'Company',
            'UserPrivateInfo.position' => 'Position',
            'UserPrivateInfo.annual_salary' => 'Annual salary',
            'UserPrivateInfo.birthday' => 'Birthday',
            'UserPrivateInfo.phone' => 'Phone',
            'UserPrivateInfo.address' => 'Address',
            'UserPrivateInfo.hobbies' => 'Hobbies',
            'UserFinanceInfo.net_worth' => 'Net worth',
            'UserFinanceInfo.risk_profile' => 'Risk profile',
        );

        $filterAlso = array(
            'friends' => 'Freinds',
            'interests' => 'Interests',
            'suggestions' => 'Suggestions',
            'polls' => 'Polls',

        );

        $this->set('filterAlso', $filterAlso);
        $emptyFilter = array_filter($this->data['Search']['column']);
        if (isset($this->data['Search']['column']) && !empty($emptyFilter)) {

            $fullName = array();
            $searchData = $this->data['Search']['column'];
            foreach($searchData as &$oneItem)
            {
                if ($oneItem == 'UserInfo.username') {
                    $oneItem = '';
                    $fullName = array('(CONCAT(UserInfo.first_name, " ", UserInfo.last_name))AS UserInfo__username');
                }
            }
            $searchData = array_merge($searchData, $fullName);
            $paginate = array(
                'conditions' => array(
                    'User.group_id' => 2
                ),
                'contain' => array('UserInfo', 'UserPrivateInfo', 'UserFinanceInfo'),
                'fields'  => $searchData,
                'order' => 'User.created',
                'limit' => $limit
            );
            $this->paginate = array_merge($this->paginate, $paginate);
            $items = $this->paginate('User');
            $this->set('items', $items);
        }

        $this->set('filterFields', $filterFields);


    }

    public function allCharts()
    {
        $this->_setHoverFlag('all_charts');
        $allInfo = $this->_getAllInfo();

        $userInfo = Set::extract('/UserInfo/.', $allInfo);
        $user = Set::extract('/User/.', $allInfo);
        $userPrivate = Set::extract('/UserPrivateInfo/.', $allInfo);

        $countUsers = $this->_statByGender($userInfo);
        $this->_statByLang($user);
        $this->_statByAge($userPrivate);

        $this->set('countUsers', $countUsers);
        $this->set('allInfo', $allInfo);

    }

    private function _getAllInfo($models = array('UserInfo', 'UserPrivateInfo'), $fields = array())
    {
        $allInfo = $this->User->find('all', array(
            'contain' => $models,
            'fields' => $fields,
            'conditions' => array('User.group_id' => 2)
        ));

        return $allInfo;
    }

    public function byGender()
    {

        $this->_statByGender($this->_getAllInfo(array('UserInfo'), array('UserInfo.sex')) );
    }

    public function byLang()
    {

        $this->_statByLang($this->_getAllInfo(array('User'), array('User.lang')) );
    }


    private function _statByAge($usersBday)
    {


        $usersBday = Set::extract('/birthday/.', $usersBday);
        $countAllItems = count($usersBday);

        $usersBday = array_filter($usersBday, function($arrVal){
            if (empty($arrVal)) return false;
            return true;
        });

        asort($usersBday);
        $onlyYears = array_map(function($y){
            $year = (int)date('Y', strtotime($y));
            return (string)floor($year / 10) * 10;//date('Y', strtotime($y));
        }, $usersBday);

        $countEachItems = array_count_values($onlyYears);
        $countNormalDate = count($countEachItems);

        $legend = array_keys($countEachItems);
        $legend = array_map(function($y){
            return 'In the '.$y.'s';
        }, $legend);
        $legend = array_values($legend);

        $countEachItems['Not specified'] = $countAllItems - $countNormalDate;

        $min = array(0);
        $max = array(max($countEachItems));


        //$tmp = array_flip($countEachItems);
        $dataMultiple = array($countEachItems);

        $this->set('minA', $min);
        $this->set('maxA', $max);
        $this->set('legendA', $legend);
        $this->set('dataA', $dataMultiple);



    }

    private function _statByLang($usersLang)
    {

        $usersLang = Set::extract('/lang/.', $usersLang);

        function emptyValL($arrVal)
        {
            if (empty($arrVal)) return false;
            return true;
        }
        $usersLangCount = count($usersLang);
        $usersLang = array_filter($usersLang, "emptyValL");
        $usersLang = array_count_values($usersLang);
        $uLang['English'] = (isset($usersLang['English'])) ? (int)$usersLang['English'] : 0;
        $uLang['Spanish'] = (isset($usersLang['Spanish'])) ? (int)$usersLang['Spanish'] : 0;
        $uLang['Portugese'] = (isset($usersLang['Portugese'])) ? (int)$usersLang['Portugese'] : 0;
        $uLang['Other'] = (isset($usersLang['Other'])) ? (int)$usersLang['Other'] : 0;

        $diff = $uLang['English'] + $uLang['Spanish'] + $uLang['Portugese'] + $uLang['Other'];

        $uLang['not_spec'] = $usersLangCount - $diff;


        $arr1 = array($uLang['English'] => $uLang['English'], $uLang['Spanish'] => $uLang['Spanish'], $uLang['Portugese'] => $uLang['Portugese'], $uLang['Other'] => $uLang['Other'] );
        $min = array(0);
        $max = array(max($arr1));

        $dataMultiple = array($arr1);

        $this->set('minL', $min);
        $this->set('maxL', $max);
        $this->set('legendL', array('English', 'Spanish', 'Portugese', 'Other'));
        $this->set('dataL', $dataMultiple);

    }

    private function _statByGender($usersGender)
    {


        $usersGender = Set::extract('/sex/.', $usersGender);

        function emptyValG($arrVal){
           if (empty($arrVal)) return false;
           return true;
        }

        $allGenders = count($usersGender);
        $usersGender = array_filter($usersGender, "emptyValG");
        $usersGender = array_count_values($usersGender);

        $uSex['M'] = (isset($usersGender['M'])) ? (int)$usersGender['M'] : 0;
        $uSex['F'] = (isset($usersGender['F'])) ? (int)$usersGender['F'] : 0;

        $fm = $uSex['M'] + $uSex['F'];
        $uSex['other'] = $allGenders - $fm;

        $arr1 = array($uSex['M'] => $uSex['M'], $uSex['F'] => $uSex['F'], $uSex['other'] => $uSex['other']);
        $min = array(0);
        $max = array(max($arr1));

        $dataMultiple = array($arr1);

        $this->set('minG', $min);
        $this->set('maxG', $max);
        $this->set('dataG', $dataMultiple);

        return $allGenders;

    }

}