<?php
/**
 * Created by IntelliJ IDEA.
 * User: kRs
 * Date: 21/07/2016
 * Time: 22.22
 */

namespace Ssslim\Libraries;


// use Ssslim\Core\Libraries\Config;
use Ssslim\Libraries\Config;
class Token
{

    private $payload;
    private $secretkey="";
    /**
     * @var Config
     */
    private $config;


    public function __construct(Config $config)
    {
        $this->payload = new \stdClass();
        $this->config = $config;
        if (!$this->config->item("AuthTokenSecretKey")) throw new \Exception("Please set a valid secret key for auth token");
        $this->secretkey = $this->config->item("AuthTokenSecretKey");
    }


    public function setPayloadFromToken($token)
    {
        if(!$this->validateToken($token)){
            return null;
        }

        return $this->payload = $this->getPayloadFromToken($token);
    }

    public function getPayloadFromToken($token) {
        if(!$this->validateToken($token)){
            return null;
        }

        $tokenAr=explode(".", $token);
        return json_decode(base64_decode($tokenAr[0]));
    }

    public function getPayload()
    {
        return $this->payload;
    }

    public function setPayloadVar($name, $val) {
        $this->payload->{$name} = $val;
    }

    public function generateToken($payload = null){
        if ($payload === null) $payload = $this->payload;
        $payloadStr=base64_encode(json_encode($payload));
        $sig=hash_hmac("sha256", $payloadStr, $this->secretkey);
        return $payloadStr.".".$sig;
    }

    private function validateToken($token){
        //Split the token
        $tokenAr=explode(".", $token);
        if(sizeof($tokenAr)<2){
            return false;
        }
        $recalc_signature=hash_hmac("sha256", $tokenAr[0], $this->secretkey);
        // $this->logger->debug(md5($recalc_signature));
        // $this->logger->debug(md5($tokenAr[1]));
        if(md5($recalc_signature)==md5($tokenAr[1])){ //We're comparing md5 to prevent timing attacks on direct comparation of the token
            return true;
        }else{
            return false;
        }
    }


}