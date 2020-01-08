<?php
require("/ssslim-system/libraries/Loader.php");    
// if (!defined('BASEPATH."loader."')) exit('No direct script access allowed');

class Loader extends CI_Loader {
	/**
	 * Loader constructor.
	 */
	public function __construct()
	{
		parent::__construct();
	}

	public function init(){
		$this->getConfig()->load('config_local', false, true);
	}

	/** LIBRARIES */

    function getUserFactory() {
        if (!empty($this->_class_cache['UserFactory'])) return $this->_class_cache['UserFactory'];
        return $this->_class_cache['UserFactory'] = new \Ssslim\Libraries\User\UserFactory($this->getLogger(), $this->getDB(), $this->getCacheFactory(), $this->getToken());
    }

    function getLeaveTypeFactory() {
        if (!empty($this->_class_cache['LeaveTypeFactory'])) return $this->_class_cache['LeaveTypeFactory'];
        return $this->_class_cache['LeaveTypeFactory'] = new \Ssslim\Libraries\LeaveType\LeaveTypeFactory($this->getLogger(), $this->getDB(), $this->getCacheFactory(), $this->getToken());
    }

    function getToken() {
        if (!empty($this->_class_cache['Token'])) return $this->_class_cache['Token'];
        return $this->_class_cache['Token'] = new \Ssslim\Libraries\Token($this->getConfig());
    }

    function getCacheFactory() {
        if (!empty($this->_class_cache['CacheFactory'])) return $this->_class_cache['CacheFactory'];
        return $this->_class_cache['CacheFactory'] = new \Ssslim\Libraries\Cache\CacheFactory($this->getLogger());
    }


    function getAppCore() {
        if (!empty($this->_class_cache['AppCore'])) return $this->_class_cache['AppCore'];
        return $this->_class_cache['AppCore'] = new \Ssslim\Libraries\AppCore($this->getLogger(), $this->getDB(), $this, $this->getCacheFactory(), $this->getUserFactory(), $this->getToken());
    }

    function getLeadsManager() {
        if (!empty($this->_class_cache['LeadsManager'])) return $this->_class_cache['LeadsManager'];
        return $this->_class_cache['LeadsManager'] = new \Ssslim\Libraries\LeadsManager($this->getLogger(), $this->getDB(), $this->getCacheFactory());
    }

    function getForms(){
        if (!empty($this->_class_cache['Forms'])) return $this->_class_cache['Forms'];
        return $this->_class_cache['Forms'] = new \Ssslim\Libraries\Forms($this->getLogger());
    }

    function getMailManager(){
        if (!empty($this->_class_cache['MailManager'])) return $this->_class_cache['MailManager'];
        return $this->_class_cache['MailManager'] = new \Ssslim\Libraries\MailManager($this->getLogger(), $this, $this->getDB(), $this->getUserFactory());
    }

    function getPagination(){
        if (!empty($this->_class_cache['Pagination'])) return $this->_class_cache['Pagination'];
        return $this->_class_cache['Pagination'] = new \Ssslim\Libraries\Pagination($this);
    }

    /** CONTROLLERS */

    function getPublicSite() {
        if (!empty($this->_class_cache['PublicSite'])) return $this->_class_cache['PublicSite'];
        return $this->_class_cache['PublicSite'] = new PublicSite($this->getAppCore(), $this->getLeadsManager(), $this->getUserFactory(), $this->getForms(), $this, $this->getMailManager(), $this->getPagination(), $this->getLogger());
    }

    function getAdmin() {
        if (!empty($this->_class_cache['Admin'])) return $this->_class_cache['Admin'];
        return $this->_class_cache['Admin'] = new Admin($this->getAppCore(), $this->getLeadsManager(), $this->getUserFactory(), $this->getLeaveTypeFactory(), $this->getForms(), $this, $this->getMailManager(), $this->getPagination());
    }

}
 
?>