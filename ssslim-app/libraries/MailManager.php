<?php

namespace Ssslim\Libraries;

use Ssslim\Core\Libraries\Logger;
use Ssslim\Libraries\User\UserFactory;

if (!defined('BASEPATH'))
	exit('No direct script access allowed');

class MailManager
{
	private $logger;
	private $db;
	private $userFactory;
    private $loader;


    function __construct(Logger $logger,  \CI_Loader $loader, \DB $db, UserFactory $userFactory)
	{
		$this->logger=$logger;
        $this->loader=$loader;
		$this->db=$db;
		$this->userFactory=$userFactory;
	}

}

class Mail{
	var $email;
	var $text;
	var $subject;

}