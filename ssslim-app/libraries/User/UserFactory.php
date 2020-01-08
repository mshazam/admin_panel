<?php
/**
 * Created by IntelliJ IDEA.
 * User: kRs
 * Date: 12/07/2016
 * Time: 13.18
 */
namespace Ssslim\Libraries\User;

require_once "UserExceptions.php";

use Ssslim\Core\Libraries\Logger;
use Ssslim\Libraries\Cache\CacheFactory;
use Ssslim\Libraries\Token;

class UserFactory
{
    /** @var  \Ssslim\Libraries\DeviceData */
    public $deviceData = null; // this is meant to be assigned in AppCore constructor so we must check that it's not null before using it

    private $logger;
    private $db;
    private $cacheFactory;
    private $notificationsManager;
    private $token;

    private $myUser;


    const USER_LEVEL_ANONYMOUS=-2;
    const USER_LEVEL_BANNED=-1;
    const USER_LEVEL_INACTIVE=0;
    const USER_LEVEL_ACTIVE=1;
    const USER_LEVEL_CUSTOMER=2;
    const USER_LEVEL_SALESREP=3;
    const USER_LEVEL_ORGANIZER=4;
    const USER_LEVEL_ADMIN=5;
    const USER_LEVEL_SUPERADMIN=6;

    const ERR_USER_NOT_FOUND=0;
    const ERR_INVALID_STATE=1;

    const USER_FILTER_REQUESTS_NOTIFICATIONS_ENABLED = 1;
    private $getUsersAndFilters = [];

    public function __construct(Logger $logger, \DB $db, CacheFactory $cacheFactory, Token $token)
    {
        $this->logger = $logger;
        $this->db = $db;
        $this->cacheFactory = $cacheFactory;
        $this->token = $token;

        // initialize internal instance with anonymous user data
        $this->myUser = new User();
    }

    public function getHumanError($err){
        switch($err){
            case UserFactory::ERR_USER_NOT_FOUND:
                return 'Sorry, the specified user was not found.';
            case UserFactory::ERR_INVALID_STATE:
                return "Sorry, the specified user's state is incompatible with the required action. Please refresh the page and try again.";
        }
    }


    public function doLogin($email, $pass){
        $u=$this->fromLogin($email, $pass);

        if($u!=null){
          /*  if($u->active!=1){
                throw new UserInactiveException();
            }else {*/
           //     $this->token->setPayloadVar('user_id', $u->user_id);
          //  }
            $this->setMyUser($u);
            $this->logger->log("USER SUCCESSFULLY LOGGED IN, P: ".($this->deviceData ? $this->deviceData->getPlatform() : '-')." UID: " .$u->getUserId() ." EMAIL: '" . $u->getEmail() . "'", Logger::INFO, "devices_registrations.log", Logger::HTTP|Logger::DEF);

        }else{
            throw new UserNotFoundException();
        }
        return $u;
    }

    /**
     * TODO is this currently a possible weakness to dnd attacks?
     * @param $email
     * @param $pass
     * @return null|User
     */
    private function fromLogin($email, $pass){
        $qry="SELECT user_id FROM users WHERE password=".$this->db->escape(sha1($pass))." AND email=".$this->db->escape($email);
        $e = $this->db->query($qry)->row();

        $u=null;
        if(!empty($e)) {
            $u=$this->fromUserId($e->user_id);
        }

        return $u;
    }
    public function fromCookie(){
        if(!isset($_COOKIE['authToken'])) {
            return null;
        } else {
            $tokenPayload = $this->token->getPayloadFromToken($_COOKIE['authToken']);
            return $this->loginFromToken($tokenPayload);
        }
    }


    public function setCookie(){
        setcookie('authToken', $this->token->generateToken(), time() + (86400 * 30), "/"); // 86400 = 1 day
    }
    public function clearCookie(){
        setcookie('authToken', '', time() - 3600, "/");
    }

    public function fromUserId($user_id){
        $user_id=intval($user_id);

        $c = $this->cacheFactory->get("users/id/".$user_id);
        if (!$c->isValid()) {
            $qry="SELECT * FROM users WHERE user_id=".$this->db->escape($user_id);
            $e = $this->db->query($qry)->row();

            $u=null;
            if(!empty($e)) {
                $u = new User($e);
            }
            $c->userData=$u;

            if($c->userData==null) {
                $this->logger->logLine("Cache created - userData is NULL $user_id ", Logger::INFO, "auth_debug.log", Logger::HTTP | Logger::DEF | Logger::URL);
            }
            if($c->userData==false) {
                $this->logger->logLine("Cache created - userData is false $user_id ", Logger::INFO, "auth_debug.log", Logger::HTTP | Logger::DEF | Logger::URL);
            }

///*            if(isset($u->user_id)){
//                $this->logger->logLine("Cache created - user id is set: ".$u->getUserId(),Logger::INFO,"auth_debug.log",Logger::HTTP|Logger::DEF|Logger::URL);
//            }else{*/
                $this->logger->logLine("Cache created - user id is NOT set, was fetching for user $user_id ",Logger::INFO,"auth_debug.log",Logger::HTTP|Logger::DEF|Logger::URL);
//            }


            $c->commit(24);
        }

        return $c->userData;
    }
    public function fromAlphaId($vendorAlphaId)
    {
        $qry="SELECT * FROM users WHERE alpha_id=".$this->db->escape($vendorAlphaId);
        $e = $this->db->query($qry)->row();

        return $e ? new User($e) : null;
    }

    public function isLoggedIn(){
        return $this->myUser->getActive() >= 1 && $this->myUser->getUserId() != 0;
    }

    public function getMyId()
    {
        return  $this->myUser->getUserId();
    }

    public function getMyUser(){
        return $this->myUser;
    }

    /**
     * @param $u User
     */
    public function setMyUser($u){
        $this->myUser=$u;
        $this->token->setPayloadVar('user_id', $u->getUserId());
    }

    public function loginFromToken($tokenPayload){

        if(empty($tokenPayload->user_id) || intval($tokenPayload->user_id)<=0){
            return null;
        }else{
            $user_id=$tokenPayload->user_id;
        }

        $user=$this->fromUserId($user_id);

       /* $c = $this->cacheFactory->get("users/id/$user_id");
        if (!$c->isValid()) {
            $e = $this->db->query("SELECT * FROM users WHERE user_id=$user_id")->row();
            if(!empty($e)) {
                $c->userData = new User($e);
            }else{
                $c->userData=null;
            }
            $c->commit(24);
        }*/
        if($user!=null /*&& isset($user->user_id) user_id should always be set and not null as $user is an instance of User */) {
            $this->setMyUser($user);
        }else{
            return null;
        }
        return $user;
    }

    public function saveUserPassword($pass, $user_id){
        $user_id=intval($user_id);
        if($user_id<=0){
            return false;
        }
        $pass=sha1($pass);
        $this->db->query("UPDATE users SET pass=".$this->db->escape($pass)." WHERE user_id=$user_id");
        return($this->db->affected_rows()==1);
    }

    public function saveUser(User $u, $pass=false){
        if($u->getUserId() > 0){
            $user_id=intval($u->getUserId());

            //This is an update, not including password (specific function for that)
            //Check if account actually exists, return false otherwise
            $r=$this->db->query("SELECT user_id FROM users WHERE user_id=".$this->db->escape($u->getUserId()))->row();
            if(!empty($r->user_id)){
                if($r->user_id!=$user_id){
                    return false;
                }
            }

            //Email is either available or equal to the old one, we can proceed with the update!
            $this->db->query(
                "UPDATE users SET
                       email=".$this->db->escape($u->getEmail())
                    .",first_name=".$this->db->escape($u->getFirstName())
                    .",last_name=".$this->db->escape($u->getLastName())
                    .",company_name=".$this->db->escape($u->getCompanyName())
                    .",phone=".$this->db->escape($u->getPhone())
                    .",active=".$this->db->escape($u->getActive())
                    .",country=".$this->db->escape($u->getCountry())
                    .",vendor_id=".$this->db->escape($u->getVendorId())
                    .",alpha_id=".$this->db->escape($u->getAlphaId())
                ." WHERE user_id=$user_id");
        }else{
            //This is an insert and must have a password
            $this->db->query(
                "INSERT IGNORE INTO users (active, email, phone, first_name, last_name, company_name, country, alpha_id, vendor_id, password) VALUES ("
                .$this->db->escape($u->getActive()).",".
                $this->db->escape($u->getEmail()).",".
                $this->db->escape($u->getPhone()).",".
                $this->db->escape($u->getFirstName()).",".
                $this->db->escape($u->getLastName()).",".
                $this->db->escape($u->getCompanyName()).",".
                $this->db->escape($u->getCountry()).",".
                $this->db->escape($u->getAlphaId()).",".
                $this->db->escape($u->getVendorId()).","
                .$this->db->escape(sha1($pass))
                .")");
            if($this->db->affected_rows()==0){
                throw new \Exception("Email already taken");
            }
            $u->setUserId($this->db->insert_id());

//            $this->logger->log("NEW USER REGISTERED, P: ".($this->deviceData ? $this->deviceData->getPlatform() : '-')." UID: $u->user_id EMAIL: '$u->email'", Logger::INFO, "devices_registrations.log", Logger::HTTP|Logger::DEF);
        }

        $this->cacheFactory->invalidateCacheFile("users/id/" . $u->getUserId());
        $this->cacheFactory->invalidateCacheFile("contents/users/list");
       // $this->logger->debug($this->fromUserId($u->user_id));exit();
        return true;
    }


    public function deleteUser($user_id){
        $this->db->query("DELETE FROM users WHERE user_id=$user_id");

        $this->cacheFactory->invalidateCacheFile("users/id/$user_id");
        $this->cacheFactory->invalidateCacheFile("contents/users/list");

        if($this->db->affected_rows()<1){
            return UserFactory::ERR_USER_NOT_FOUND;
        }

        return 0;
    }
    public function activateUser($user_id){
        $u=$this->db->query("SELECT active FROM users WHERE user_id=$user_id")->row();
        if($u==null){
            return UserFactory::ERR_USER_NOT_FOUND;
        }

        if($u->active!=UserFactory::USER_LEVEL_BANNED &&$u->active!=UserFactory::USER_LEVEL_INACTIVE){
            return UserFactory::ERR_INVALID_STATE;
        }

        $this->db->query("UPDATE users SET active=".UserFactory::USER_LEVEL_ACTIVE." WHERE user_id=$user_id");

        $this->cacheFactory->invalidateCacheFile("users/id/$user_id");
        $this->cacheFactory->invalidateCacheFile("contents/users/list");

        $data=new \stdClass();
        $data->notification_id=0;
        $data->recipients=$user_id;
        $data->notification_type=NotificationsManager::NOTIFICATION_TYPE_ACCESS_GRANTED;
        $data->title="Access granted";
        $data->text="Your access to Tetra Pak @ GFM16 mobile app has been granted!";
        $data->date_trigger=gmdate('Y-m-d H:i:s');

        $s = new Notification($data);
        $s=$this->notificationsManager->saveNotification($s);

        return 0;
    }
    public function banUser($user_id){
        $u=$this->db->query("SELECT active FROM users WHERE user_id=$user_id")->row();
        if($u==null){
            return UserFactory::ERR_USER_NOT_FOUND;
        }

        if($u->active!=UserFactory::USER_LEVEL_ACTIVE && $u->active!=UserFactory::USER_LEVEL_INACTIVE){
            return UserFactory::ERR_INVALID_STATE;
        }

        $this->db->query("UPDATE users SET active=".UserFactory::USER_LEVEL_BANNED." WHERE user_id=$user_id");

        $this->cacheFactory->invalidateCacheFile("users/id/$user_id");
        $this->cacheFactory->invalidateCacheFile("contents/users/list");

        $data=new \stdClass();
        $data->notification_id=0;
        $data->recipients=$user_id;
        $data->notification_type=NotificationsManager::NOTIFICATION_TYPE_ACCESS_DENIED;
        $data->title="Access denied";
        $data->text="Sorry, your access to Tetra Pak @ GFM16 mobile app has been denied at this time.";
        $data->date_trigger=gmdate('Y-m-d H:i:s');

        $s = new Notification($data);
        $s=$this->notificationsManager->saveNotification($s);


        return 0;
    }

    public function addUsersFilter($filterType, $filterValue = null, $type = "AND")
    {
        $f = new \stdClass();
        $f->type = $filterType;
        $f->value = $filterValue;

        $this->getUsersAndFilters[] = $f;
        return $this;
    }

    public function fromRawObject($data)
    {
        return new User($data);
    }

    function getUsers(){
        $c = $this->cacheFactory->get("contents/users/list");

        $qWhere = " WHERE 1";

        foreach ($this->getUsersAndFilters as $f) {

            switch ($f->type) {
                case self::USER_FILTER_REQUESTS_NOTIFICATIONS_ENABLED:
                    $qWhere .= " AND users.notifications_requests_submission = '" . $f->value . "'";
                    break;

                default:
                    throw new \Exception("UNKNOWN FILTER");
                    break;
            }
        }

        if (!$this->getUsersAndFilters && !$c->isValid()) {
            $l=$this->db->query("SELECT * FROM users $qWhere ORDER BY user_id DESC")->result();
            $c->userData = $l;
            if (!$this->getUsersAndFilters) $c->commit(24);
        }

        $toReturn=array();
        foreach($c->userData as $u){
            $toReturn[]=new User($u);
        }

        return $toReturn;
    }

    /*    public function _generateTokenString($len = 128) {
            //No token - generate a new one
            $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
            $newToken = '';

            for ($i = 0; $i < $len; $i++) {
                $newToken .= $characters[mt_rand(0, strlen($characters) - 1)];
            }

            print $newToken;
        }*/
}

class User implements \JsonSerializable {
    private $user_id = 0;
    private $active=UserFactory::USER_LEVEL_ANONYMOUS;
    private $first_name = 'Anonymous';
    private $last_name = '';
    private $company_name;
    private $phone;
    private $email;
    private $country;
    private $vendor_id = null;
    private $alpha_id = null;

    /**
     * @return int
     */
    public function getUserId()
    {
        return $this->user_id;
    }

    /**
     * @return string
     */
    public function getFirstName()
    {
        return $this->first_name;
    }

    /**
     * @return string
     */
    public function getLastName()
    {
        return $this->last_name;
    }

    /**
     * @return mixed
     */
    public function getCompanyName()
    {
        return $this->company_name;
    }

    /**
     * @return mixed
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * @return mixed
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @return mixed
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * @return null
     */
    public function getVendorId()
    {
        return $this->vendor_id;
    }

    /**
     * @return int
     */
    public function getActive()
    {
        return $this->active;
    }

    /**
     * @return null
     */
    public function getAlphaId()
    {
        return $this->alpha_id;
    }



    /**
     * @param int $active
     * @return User
     */
    public function setActive($active)
    {
        $this->active = $active;
        return $this;
    }

    /**
     * @param int $user_id
     * @return User
     */
    public function setUserId($user_id)
    {
        $this->user_id = $user_id;
        return $this;
    }

    public function __construct($data = null)
    {
        if ($data) $this->setData($data);
    }

    public function setData($data)
    {
        if(isset($data->user_id)) {
            $this->user_id = $data->user_id;
        }
        if(isset($data->active)) {
            $this->active = $data->active;
        }

        if (isset($data->first_name)) $this->first_name=$data->first_name;
        if (isset($data->last_name)) $this->last_name=$data->last_name;
        if (isset($data->company_name)) $this->company_name=$data->company_name;
        if (isset($data->phone)) $this->phone=$data->phone;
        if (isset($data->email)) $this->email=$data->email;
        if (isset($data->vendor_id)) $this->vendor_id = $data->vendor_id;
        if (isset($data->alpha_id)) $this->alpha_id = $data->alpha_id;
//        $this->country=$data->country;

    }

    public function getHumanStatus(){
        return User::getHumanStatusDesc($this->active);
    }

    public static function getHumanStatusDesc($status){
        switch($status){
            case UserFactory::USER_LEVEL_BANNED: return 'Banned'; break;
            case UserFactory::USER_LEVEL_ANONYMOUS: return 'Anonymous'; break;
            case UserFactory::USER_LEVEL_INACTIVE: return 'Inactive'; break;
            case UserFactory::USER_LEVEL_ACTIVE: return 'Active'; break;
            case UserFactory::USER_LEVEL_CUSTOMER: return 'Customer'; break;
            case UserFactory::USER_LEVEL_SALESREP: return 'Sales representative'; break;
            case UserFactory::USER_LEVEL_ADMIN: return 'Administrator'; break;
            case UserFactory::USER_LEVEL_SUPERADMIN: return 'Super-admin'; break;
        }
    }

    public static function getHumanAdminStates(){
        return array(
//            UserFactory::USER_LEVEL_INACTIVE=> 'Inactive',
//            UserFactory::USER_LEVEL_ACTIVE=> 'Active',
            UserFactory::USER_LEVEL_ADMIN=>'Administrator',
//            UserFactory::USER_LEVEL_BANNED => 'Banned',
//            UserFactory::USER_LEVEL_CUSTOMER => 'Customer',
//            UserFactory::USER_LEVEL_SALESREP => 'Sales representative',
//            UserFactory::USER_LEVEL_SUPERADMIN => 'Super-admin',
        );
    }


    /**
     * Specify data which should be serialized to JSON
     * @link https://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize()
    {
        // TODO: Implement jsonSerialize() method.
        return ['user_id' => $this->user_id, 'first_name' => $this->first_name, 'last_name' => $this->last_name, 'company_name' => $this->company_name, 'phone' => $this->phone, 'email' => $this->email, 'vendor_id' => $this->vendor_id, 'active' => $this->active ];
    }
}