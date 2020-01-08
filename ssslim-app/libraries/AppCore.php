<?php
/**
 * Created by IntelliJ IDEA.
 * User: vds
 * Date: 18/07/2016
 * Time: 18.06
 */

namespace Ssslim\Libraries;


use Ssslim\Core\Libraries\Logger;
use Ssslim\Libraries\User\UserFactory;

class AppCore
{
    private $db;
    private $loader;
    private $cacheFactory;
    private $userFactory;
    private $logger;
    private $token;

    private $request;
    /** @var DeviceData holds platform, connection type, native app version, web app version. This data is passed from client and stored into token and into deviceData object */
    private $deviceData;

    public function __construct(Logger $logger, \DB $db, \CI_Loader $load, Cache\CacheFactory $cacheFactory, UserFactory $userFactory, Token $token)
    {
        $this->logger = $logger;
        $this->db = $db;
        $this->loader = $load;
        $this->cacheFactory = $cacheFactory;
        $this->userFactory = $userFactory;
        $this->token = $token;

        $this->initRequest();

/*        if (!empty($this->request->authToken)) $this->token->setPayloadFromToken($this->request->authToken);

        if (!empty($this->request->setDD)) {    // this is a trigger telling that client is passing with this call one or more of the values below to be stored into token and into deviceData object
            if (!empty($this->request->p)) $this->token->setPayloadVar('p', $this->request->p); // platform (a : android, i : ios)
            if (!empty($this->request->c)) $this->token->setPayloadVar('c', $this->request->c); // connection type
            if (!empty($this->request->nv)) $this->token->setPayloadVar('nv', $this->request->nv); // native app version
            if (!empty($this->request->wv)) $this->token->setPayloadVar('wv', $this->request->wv); // web app version
        }

        $tokenPayload = $this->token->getPayload();
        $this->userFactory->loginFromToken($tokenPayload);

        $this->deviceData = new DeviceData();
        $this->deviceData->setFromTokenPayload($tokenPayload);

        // PRETTY HACKISH
        $this->userFactory->deviceData = $this->deviceData;*/
    }

    /**
     * @return mixed
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @return DeviceData
     */
    public function getDeviceData()
    {
        return $this->deviceData;
    }

    private function initRequest()
    {
        //Start analyzing the request
        $this->request = json_decode(file_get_contents('php://input')) ?: new \stdClass();

        foreach ($_GET as $var => $val) {
            $this->request->$var = $val;
        }
        foreach ($_POST as $var => $val) {
            $this->request->$var = $val;
        }
    }

    function show_404(){
      //  http_response_code(404);
        show_404();
        exit();
    }

    function redirect($uri = '', $method = 'location')
    {
        switch($method)
        {
            case 'refresh'	: header("Refresh:0;url=".site_url($uri));
                break;
            default			: header("location:".site_url($uri));
                break;
        }
        exit;
    }

    public function testFramework($expire = "")
    {
        if ($expire) $this->cacheFactory->invalidateCacheFile('cache_test');
        $c = $this->cacheFactory->get("cache_test");

        if (!$c->isValid()) {

            print ("cache expired<br><br>");

            $r = $this->db->query("SELECT * FROM users WHERE 1")->result();
            $c->userData['users'] = $r;
            $c->commit(0, 1);
        }

        print 'cache expiring at: ' . $c->getExpiration() . '<br><br>';

        foreach ($c->userData['users'] as $u) {
            print "$u->first_name $u->last_name" . "<br>";
        }

        print'<br><br>base url is: ' . \base_url();
    }

    public function getCountries()
    {
        $c = $this->cacheFactory->get("countries");
        if (!$c->isValid()) {
            $result = $this->db->query("SELECT * FROM countries ORDER BY name")->result();

            $c->userData = array();
            foreach ($result as $r) {
                $c->userData[$r->id] = $r->name;
            }
            $c->commit(24);
        }
        return $c->userData;
    }

    public function buildAppConfig()
    {
        //Cached stuff
    /*    $c = $this->cacheFactory->get("baseConfig");
        if (!$c->isValid()) {
            $result = $this->db->query("SELECT * FROM countries ORDER BY name")->result();

            $c->userData = array();
            $c->userData['countries'] = array();
            foreach ($result as $r) {
                $c->userData['countries'][$r->id] = $r->name;
            }

            //			'staticURL' => STATIC_BASE_URL,
//			'paths' => array(
//				'apps_img' => 'bitmiles/users/apps',
//				'app_rews_img' => 'bitmiles/users/app_rewards',
//			)
            $c->commit(24);
        }
        $config = $c->userData;*/
      //  $config['countries']=$this->getCountries();

        $config['authToken'] = $this->token->generateToken();

        //Live stuff
        if ($this->userFactory->isLoggedIn()) {
            $config['user'] = $this->userFactory->getMyUser();
        }

        return $config;
    }

    public function renderJson($data, $header = "Content-Type: application/json")
    {
        header($header);
        echo json_encode($data);
    }

    public function renderApp()
    {
        $data['config'] = json_encode($this->buildAppConfig());

        $data['cordovaPlatform'] = $this->deviceData->getPlatform();

        $this->loader->view('webapp/tpl_v', $data);
    }

    /**
     * TODO this would work much better as a global function
     * @param $var
     */
    public function debug($var){
        $this->logger->debug($var);
    }

    public function ESTToUTC($datetime){
        $tz_from = 'America/New_York';
        $tz_to = 'UTC';
        $format = 'Y-m-d H:i';
        $dt = new \DateTime($datetime, new \DateTimeZone($tz_from));
        $dt->setTimeZone(new \DateTimeZone($tz_to));
        return $dt->format($format);
    }
    public function UTCToEst($datetime){
        $tz_to = 'America/New_York';
        $tz_from = 'UTC';
        $format = 'Y-m-d H:i';
        $dt = new \DateTime($datetime, new \DateTimeZone($tz_from));
        $dt->setTimeZone(new \DateTimeZone($tz_to));
        return $dt->format($format);
    }

}

class DeviceData
{
    private $nativeAppVersion = '';
    private $webAppVersion = '';
    private $platform = 'web';
    private $connection = '';

    private $platformMap = ['i' => 'ios', 'a' => 'android'];

    public function setFromTokenPayload($tokenPayload)
    {
        if (isset($tokenPayload->nv)) $this->nativeAppVersion = $tokenPayload->nv;
        if (isset($tokenPayload->wv)) $this->webAppVersion = $tokenPayload->wv;
        if (isset($tokenPayload->p)) $this->platform = isset($this->platformMap[$tokenPayload->p]) ? $this->platformMap[$tokenPayload->p] : '';
        if (isset($tokenPayload->c)) $this->connection = $tokenPayload->c;
    }

    /**
     * @return string
     */
    public function getPlatform()
    {
        return $this->platform;
    }

}


class Response
{
    public $s;
    public $err = "";
    public $error = "";

    private $errCodes = array(
        "USER_NOT_FOUND" => "Sorry, e-mail or password not found.",
        "UNKNOWN_ACTION" => "Invalid action specified.",
        "USER_INACTIVE" => "Sorry, this account is not currently active.",
        "VALIDATION_ERRORS" => "One or more fields were not properly filled in.",
        "GENERIC_ERROR" => "An error has occurred",
        "UNAUTHORIZED_ACTION" => "Sorry, you are not authorized to execute this action",
        "UNAUTHORIZED_VIEW" => "Sorry, you are not authorized to view this page",
        "DUPLICATE_EMAIL" => "Sorry, the specified e-mail is already in use.");

    function __construct($errCode = null, $errorMsg = null)
    {
        if ($errCode == null) {
            $this->s = 1;
            return;
        }

        if ($errorMsg !== null) {
            $this->error = $errorMsg;
        }
        else if (array_key_exists($errCode, $this->errCodes)) {
            $this->error = $this->errCodes[$errCode];
        }

        $this->s = 0;
        $this->err = $errCode;
    }
}