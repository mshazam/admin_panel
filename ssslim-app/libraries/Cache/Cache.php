<?php

/**
 * Created by IntelliJ IDEA.
 * User: kRs
 * Date: 06/07/2016
 * Time: 11.43
 */
namespace Ssslim\Libraries\Cache;

class Cache
{
    private $id = '';
    private $isPacked = true;
    private $expiration = 0;
    private $isValid = false;
    
    private $factory;

    public $userData = array();


    public function __construct($id, $userData = array(), $expiration = -1, $isValid = false, $isPacked = true, CacheFactory $factory)
    {
        $this->id = $id;
        $this->userData = $userData;
        $this->expiration = $expiration;
        $this->isValid = $isValid;
        $this->isPacked = $isPacked;
        
        $this->factory = $factory;
    }

    public function isValid() {
        return $this->isValid;
    }

    public function getExpiration() {
        return gmdate("Y-m-d H:i:s", $this->expiration);
    }

    public function commit($expirationHours = 0, $expirationMinutes = 0, $mustPack = true)
    {
        if (is_numeric($expirationHours)) {
            if ($expirationHours > 0 || $expirationMinutes > 0) $this->expiration = gmmktime((int)gmdate('H') + $expirationHours, (int)gmdate('i') + $expirationMinutes);
        }

        $this->factory->writeCacheFile($this->id, $this->userData, $this->expiration, $mustPack);
    }


}