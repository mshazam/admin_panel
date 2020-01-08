<?php

namespace Ssslim\Controllers;

use Ssslim\Core\Libraries\Logger;
use Ssslim\Libraries\AppCore;
use Ssslim\Libraries\Pagination;
use Ssslim\Libraries\LeadsManager;
use Ssslim\Libraries\Forms;
use Ssslim\Libraries\User\UserFactory;
use Ssslim\Libraries\MailManager;


class PublicSite
{

    private $leadsManager;
    private $userFactory;
    private $appCore;
    private $forms;
    private $loader;
    private $mailManager;
    private $pagination;

    const gateLiftedCookieName = 'gt';

    /**
     * @var Logger
     */
    private $logger;


    function __construct(AppCore $appCore, LeadsManager $leadsManager, UserFactory $userFactory, Forms $forms, \CI_Loader $loader, MailManager $mailManager, Pagination $pagination, Logger $logger)
    {
        $this->appCore = $appCore;
        $this->leadsManager = $leadsManager;
        $this->userFactory = $userFactory;
        $this->forms = $forms;
        $this->loader = $loader;
        $this->mailManager = $mailManager;
        $this->pagination = $pagination;
        $this->logger = $logger;
    }

    public function home()
    {
        $r = $this->appCore->getRequest();
        $viewData['content'] = $this->loader->view('home_v', ['passedValue' => 'test'], true);
        $this->_render($viewData);
    }

    public function clearCookie()
    {
        setcookie(self::gateLiftedCookieName, '', time() - 3600, "/");
        $this->appCore->redirect();
    }


    private function _render($data = [])
    {
        $this->loader->view('tpl_v', $data);
    }

}
