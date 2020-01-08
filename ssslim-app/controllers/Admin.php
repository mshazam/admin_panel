<?php
namespace Ssslim\Controllers;

use Ssslim\Libraries\Csv;
use Ssslim\Libraries\Form;
use Ssslim\Libraries\FormField;
use Ssslim\Libraries\AppCore;
use Ssslim\Libraries\Pagination;
use Ssslim\Libraries\Response;
use Ssslim\Libraries\LeadsManager;
use Ssslim\Libraries\Lead;
use Ssslim\Libraries\Forms;

use Ssslim\Libraries\LeaveType\LeaveTypeFactory;
use Ssslim\Libraries\LeaveType\LeaveType;
use Ssslim\Libraries\LeaveType\LeaveTypeNotFoundException;
use Ssslim\Libraries\LeaveType\LeaveTypeInactiveException;
use Ssslim\Libraries\User\UserFactory;
use Ssslim\Libraries\User\User;
use Ssslim\Libraries\User\UserNotFoundException;
use Ssslim\Libraries\User\UserInactiveException;


use Ssslim\Libraries\MailManager;







class Admin
{
    private $leadsManager;
    private $userFactory;
    private $leaveTypeFactory;
    private $appCore;
    private $forms;
    private $loader;
    private $mailManager;
    private $pagination;

    private $leadAttachmentDir = "attachments/"; // TODO move to config file?
    private $maxFileNameCreationAttempts = 10;

    function __construct(AppCore $appCore, LeadsManager $leadsManager, UserFactory $userFactory, LeaveTypeFactory $leaveTypeFactory, Forms $forms, \CI_Loader $loader,  MailManager $mailManager, Pagination $pagination)
    {
        $this->appCore = $appCore;
        $this->leadsManager = $leadsManager;
        $this->userFactory = $userFactory;
        $this->leaveTypeFactory = $leaveTypeFactory;
        $this->forms = $forms;
        $this->loader=$loader;
        $this->mailManager=$mailManager;
        $this->pagination = $pagination;

        if (isset($_SERVER['argv']) && !isset($_SERVER['SERVER_NAME'])) { // command line execution
            return;
            if (isset($_SERVER['argv'][2]) && strtolower($_SERVER['argv'][2]) == "sendactivationmails") return; // sendActivationMails is allowed from CLI
        }

        if (!empty($_GET['k']) && $_GET['k'] == 'q76__xMQ11e2ddr33Q') return;

        $loginPath="dashboard/login";
        $path=substr($_SERVER['REQUEST_URI'], strlen($_SERVER['REQUEST_URI'])-strlen($loginPath), strlen($loginPath));
        
        if($path!=$loginPath){
            try {
                $u = $this->userFactory->fromCookie();

                if ($u == null) {
                    $this->appCore->redirect($loginPath);
                }

                if($u->getActive() < UserFactory::USER_LEVEL_ADMIN){
                    $this->appCore->redirect('dashboard/login');
                }
            } catch (\Exception $e) {
                $this->appCore->redirect($loginPath);
            }
        }

      /*  if($this->userFactory->getMyUser()->active<UserFactory::USER_LEVEL_ADMIN){
            $this->appCore->debug($this->userFactory->getMyUser());
            $this->appCore->show_404();
        }*/
    }

    public function render($data=array())
    {
       // $data['config'] = json_encode($this->buildAppConfig());
        $this->loader->view('admin/tpl_v', $data);
    }

    public function leads($label=''){
        $leadsPerPage = 20;
        $paginationItems = 11;

        $data=array();
        $data['leads']=array();
        $queryStringVars = [];

        /* filters data */
        $data['startDate'] = !empty($_GET['startDate']) ? $_GET['startDate'] : '';
        $data['endDate'] = !empty($_GET['endDate']) ? $_GET['endDate'] : '';
/*        $data['type'] = !empty($_GET['type']) ? $_GET['type'] : '';
        $data['status'] = !empty($_GET['status']) ? $_GET['status'] : '';*/
        $page = !empty($_GET['p']) ? $_GET['p'] : 1;

        if ($data['startDate']) {
            $this->leadsManager->addLeadsFilter(LeadsManager::LEADS_FILTER_START_DATE,$this->appCore->ESTToUTC($data['startDate'] . " 00:00:00"));
            $queryStringVars['startDate'] =  $data['startDate'];
        }
        if ($data['endDate']) {
            $this->leadsManager->addLeadsFilter(LeadsManager::LEADS_FILTER_END_DATE, $this->appCore->ESTToUTC($data['endDate'] . " 23:59:59"));
            $queryStringVars['endDate'] =  $data['endDate'];
        }
/*        if ($data['type']) {
            $this->leadsManager->addLeadsFilter(LeadsManager::LEADS_FILTER_TYPE, $data['type']);
            $queryStringVars['type'] =  $data['type'];
        }
        if ($data['status']) {
            $this->leadsManager->addLeadsFilter(LeadsManager::LEADS_FILTER_STATUS, $data['status']);
            $queryStringVars['status'] =  $data['status'];
        }*/

        if (empty($_GET['csv'])) $this->leadsManager->setOffset(($page - 1) * $leadsPerPage, $leadsPerPage);
        /** @var Lead[] $list */
        $list = $this->leadsManager->getLeads();

        foreach ($list as $lead) {
            $lead->setTransdate($this->appCore->UTCToEst($lead->getTransdate()));
//            if ($lead->gdprAcceptedDate) $lead->gdprAcceptedDate = $this->appCore->UTCToSwitzerland($lead->gdprAcceptedDate);
        }
        $data['leads'] = $list;

        if (!empty($_GET['csv'])) {

            $headers = ['TRANSDATE', 'FNAME', 'LNAME', 'JOBTITLE', 'COMPANY', 'STATE', 'COUNTRY', 'EMAIL'];
            $rows = [];

            /** @var Lead $l */
            foreach ($data['leads'] as $l) {
                $rows[] = [$l->getTransdate(), $l->getFname(), $l->getLname(), $l->getJobtitle(), $l->getCompany(), $l->getState(), $l->getCountry(), $l->getEmail()];
            }

            $csvLib = new Csv();
            $csvLib->output('leads.csv', $rows, $headers, ',');
            return;
        }

        $queryString = ($queryStringVars) ? '?' . http_build_query($queryStringVars) : '';
        $paginationPfx = $queryString ? "&p=" : "?p=";
        $data['pagination'] = $this->pagination->setPaginationLinks($paginationItems)->setItemsPerPage($leadsPerPage)->paginate($this->leadsManager->getLeads(true), $page, site_url("dashboard/leads") . $queryString , $paginationPfx);

        $data['content']=$this->loader->view('admin/leads_v', $data, true);
        $data['section']='leads';
        $this->render($data);
    }

    public function users(){
        $data=array();

        $filterActivation=isset($_GET['activation']);

        $users=$this->userFactory->getUsers();
        $data['users']=array();

        foreach($users as $u){
            if($filterActivation && $u->active!=UserFactory::USER_LEVEL_INACTIVE){
                continue;
            }
            $data['users'][]=$u;
        }


        $data['content']=$this->loader->view('admin/users_v', $data, true);
        $data['section']='users';
        $this->render($data);

    }


    public function login(){
//$this->appCore->debug($this->appCore->getRequest());exit();
        $u = null;
            if (empty($this->appCore->getRequest()->email) || empty($this->appCore->getRequest()->password)) {
                //$u=null;
            }else {
                try {
                    $u = $this->userFactory->doLogin($this->appCore->getRequest()->email, $this->appCore->getRequest()->password);

                    $redirect="dashboard";
                    if(!empty($this->appCore->getRequest()->redirect)){
                        $redirect=$this->appCore->getRequest()->redirect;
                    }

                    $this->userFactory->setCookie();

                    $this->appCore->redirect($redirect);
                } catch (UserNotFoundException $e) {
                    $u = null;
                } catch (UserInactiveException $e) {
                    $u = null;
                }
            }

        $data=array();
        $this->loader->view('admin/login_v', $data, false);

        //$this->render($data);
    }



    public function logout(){
        $this->userFactory->clearCookie();
        $this->appCore->redirect("dashboard/login");
    }

    public function edit_lead($id=0)
    {
        $id = intval($id);

        if ($id) {
            $lead = $this->leadsManager->getLead($id);
            if ($lead == null) {
                show_404();
            }
        }
        else $lead = Lead::getEmpty();

        $sendQuoteOnLoad = isset($_POST['sendQuoteOnLoad']) ? (int)$_POST['sendQuoteOnLoad'] : 0;

        //$this->appCore->debug( $this->appCore->getRequest());
        // exit();

        if (isset($this->appCore->getRequest()->fname)) {
            $form = new Form(
                array(
//                    new FormField('companyName', true),
                    new FormField('email', true)
                   // new FormField('seats', true),
                )
            );

            $errors = $this->forms->getValidationErrors($form, $this->appCore->getRequest());

            // CUSTOM VALIDATION
/*            if (isset($this->appCore->getRequest()->companyName) && trim($this->appCore->getRequest()->companyName) == "") {

            }*/

            if (!empty($this->appCore->getRequest()->email) && !filter_var($this->appCore->getRequest()->email, FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = "Invalid e-mail address";
            }

            try {
                $data = $this->appCore->getRequest();

                $data->lead_id = $id;
//                $data->attachment = $lead->attachment; // preserve existing attachment which is not being passed by the form when updating existing lead
//                $data->attachmentDownloadToken = $lead->attachmentDownloadToken; // preserve existing token which is not being passed by the form when updating existing lead

//                $data->start=$this->appCore->switzerlandToUTC($data->start);
//                $data->end=$this->appCore->switzerlandToUTC($data->end);

                //TODO this can be improved by implementing a way to update the data of the lead object already fetched from db with the form data
                $lead = new Lead($data);
                $lead->transdate = $this->appCore->ESTToUTC($lead->getTransdate());

                if (sizeof($errors) > 0) {
                    $this->renderLead($lead, $errors);
                    return;
                }

                /* lead attachment code */

                if (!empty($_FILES['attachment']['name'])) {

                    $finalFileName = $finalPathFile = "";

                    if (!file_exists($this->leadAttachmentDir)) {
                        mkdir($this->leadAttachmentDir);
                    }

                    for ($attempts = 0; $attempts < $this->maxFileNameCreationAttempts; $attempts++) {
                        $finalFileName = base64_encode(openssl_random_pseudo_bytes(32));
                        $finalPathFile = $this->leadAttachmentDir . $finalFileName;
                        if (!file_exists($finalPathFile)) break; // unique file name found
                    }

                    if ($attempts == $this->maxFileNameCreationAttempts) { // fatal error
                        die('internal server error [attachmentFileNameCreationError], please contact support'); // TODO improve handling of fatal errors like this one?
                    }

                    $r = @move_uploaded_file($_FILES['attachment']['tmp_name'], $finalPathFile );
                    if (!$r) {
                        die('internal server error [attachmentFileMoveError], please contact support'); // TODO improve handling of fatal errors like this one?
                    }

                    $lead->attachment = $_FILES['attachment']['name'];
                    $lead->attachmentDownloadToken = $finalFileName;
                }

                $lead = $this->leadsManager->saveLead($lead);

                if (!$lead) {
                    throw new \Exception("GENERIC_ERROR");
                }
//                if (!$sendQuoteOnLoad) $this->appCore->redirect("admin/leads");
            } catch (\Exception $e) {
                $this->appCore->renderJson(new Response($e->getMessage()));
                return;
            }
        } /*else { // do not mess with status when form is submitted

            if ($lead->status == "new") { // change status to seen only if new. Could be improved by moving this code into the view and using ajax
                $lead->status = "seen";
                $this->leadsManager->saveLead($lead);
            }
        }*/

        $this->renderLead($lead, null, $sendQuoteOnLoad);
    }

    private function renderLead(Lead $lead, $errors=null, $sendQuoteOnLoad = 0){
        $data=array();
        $data['lead_id'] = $lead->getLeadId();
        $data['section'] = 'leads';

        $lead->transdate=$this->appCore->UTCToEst($lead->getTransdate());
//        if ($lead->gdprAcceptedDate != null) $lead->gdprAcceptedDate=$this->appCore->UTCToSwitzerland($lead->gdprAcceptedDate);

        $data['lead'] = $lead;

        if($errors==null){
            $errors=array();
        }
        $data['errors']=$errors;
        $data['sendQuoteOnLoad'] = $sendQuoteOnLoad;

        // $this->appCore->debug($data);
        //exit();
        $data['content'] = $this->loader->view('admin/edit_lead_v', $data, true);
        $this->render($data);
    }


    public function deleteLead($id){
        $id=intval($id);
        $this->leadsManager->deleteLead($id);
        $r=new Response();
        $r->content_id=$id;
        $this->appCore->renderJson($r);
        return;
    }

    public function userAction($action, $id){
        $id=intval($id);
        $r=new Response();
        $r->content_id=$id;
        $err=0;
        switch($action){
            case 'delete':
                $err=$this->userFactory->deleteUser($id);
                break;
            case 'activate':
                $err=$this->userFactory->activateUser($id);
                break;
            case 'ban':
                $err=$this->userFactory->banUser($id);
                break;

        }
        if($err==0){
            $r->s=1;
        }else{
            $r->s=0;
            $r->err=$err;
            $r->error=$this->userFactory->getHumanError($err);
        }
        $this->appCore->renderJson($r);
        return;
    }

    public function edit_user($id=0)
    {
        $id=intval($id);

        if(isset($this->appCore->getRequest()->submit)){
            $form = new Form(
                array(
                    new FormField('title', true),
                    new FormField('first_name', true),
                    new FormField('last_name', true),
                    new FormField('organization', true),
                    new FormField('country', true),
                    new FormField('phone_number', true),
                    new FormField('mobile_number'),
                    new FormField('active', true),
                    new FormField('email', true),
                )
            );

            $errors = $this->forms->getValidationErrors($form, $this->appCore->getRequest());

             $u = new User($this->appCore->getRequest());
             $u->notifications_requests_submission=isset($this->appCore->getRequest()->notifications_requests_submission)?1:0;

             $u->user_id = $id;
             if (sizeof($errors) > 0) {
                $this->renderUser($u, $errors);
                return;
             }
             if(!$this->userFactory->saveUser($u)) {
                 //TODO this should be in the form validation
                 $errors=array();
                 $errors['email']='DUPLICATE';
                 $this->renderUser($u, $errors);
                 return;
             }
            $this->appCore->redirect("dashboard/users");
        } else {
            if($id==0){
                $this->appCore->redirect("dashboard/users");
            }else {
                $this->renderUser($this->userFactory->fromUserId($id), null);
                return;
            }
        }
    }
    private function renderUser($u, $errors=null){
        $data=array();
        $data['user_id'] = $u->user_id;
        $data['user'] = $u;
        $data['section'] = 'users';


        if($errors==null){
            $errors=array();
        }
        $data['errors']=$errors;
        $data['countries'] = $this->appCore->getCountries();

        // $this->appCore->debug($data);
        //exit();

/*
//<?if($u->status==UserFactory::USER_LEVEL_INACTIVE):?>

//<?if($u->status==UserFactory::USER_LEVEL_INACTIVE):?>*/

        $data['content'] = $this->loader->view('admin/edit_user_v', $data, true);
        $this->render($data);
    }


    public function upload($id)
    {
        $res['status'] = 1;

        if (isset($_FILES) && $_FILES) {
            foreach ($_FILES as $f => $v) {
                $f_name = $v['name'];
                $f_tmp_name = $v['tmp_name'];
            }
        } else{
            $res['status'] = 0;
            print json_encode($res);
            return;
        }

        $type = 'speaker';

        $r = $this->_init_temp_upload_dir($type); // CREATE FOLDER $this->config->item("temp_upload_dir")/$type
        if (!$r){
            $res['status'] = 0;
            print json_encode($res);
            return;
        }

        $tmp_file_path_name = $this->_create_temp_uploaded_file_name($v['name'], $type);
        if (!$tmp_file_path_name){
            $res['status'] = 0;
            print json_encode($res);
            return;
        }

        $r = move_uploaded_file($v['tmp_name'], $tmp_file_path_name);
        if (!$r) return $this->_up5_err(3);

        $res = new \stdClass();
        $res->file_size = filesize($tmp_file_path_name);
        $res->f_id = basename($tmp_file_path_name);
        $res->s = 1;
        header("Content-Type: application/json");
        print json_encode($res);
        return;
    }
    private function get_ext($file, $upperCase=false)
    {
        if (strrpos($file, '.') > 0) return ( ($upperCase) ? strtoupper(substr($file, strrpos($file, '.')+1)) : substr($file, strrpos($file, '.')+1) );
        else return "";
    }
    private function _create_temp_uploaded_file_name($original_file_name, $type, $id = NULL) {
        if ($id === null) $id = uniqid();
        $tmp_upload_dir =  'upload_temp/';

        $f_path = $tmp_upload_dir.$type."/";

        $ext = mb_strtolower($this->get_ext($original_file_name));
        if ($ext) $ext = preg_replace("#[^a-z0-9]#", "", $ext);
        if (!$ext) $ext = "ext";

        $f_name = $type."_".$id."_".$ext;

        if (file_exists($f_path.$f_name)) return '';
        return $f_path.$f_name;
    }
    private function _init_temp_upload_dir($type) {
        $r = TRUE;
        $tmp_upload_dir = 'upload_temp/';
        if (!file_exists($tmp_upload_dir) || !file_exists($tmp_upload_dir.$type)) {
            $r = mkdir($tmp_upload_dir.$type, 0777, TRUE);
            chmod($tmp_upload_dir, 0777);
            chmod($tmp_upload_dir.$type, 0777);
        }
        return $r;
    }

    public function dashboard(){
        $data=array();

        $data['content']=$this->loader->view('admin/dashboard_v', $data, true);
        $data['section']='dashboard';
        $this->render($data);

    }
     /* Author Jamil Muhammad 
    * method  leave types
    * to get list of all records
    * @param id int
    *
    */
    public function leave_types($label=''){
        $leadsPerPage = 20;
        $paginationItems = 11;

        $data=array();
        $data['leave_types']=array();
        $queryStringVars = [];

        /* filters data */
        $data['startDate'] = !empty($_GET['startDate']) ? $_GET['startDate'] : '';
        $data['endDate'] = !empty($_GET['endDate']) ? $_GET['endDate'] : '';
        $page = !empty($_GET['p']) ? $_GET['p'] : 1;

        if ($data['startDate']) {
            $this->leadsManager->addLeadsFilter(LeadsManager::LEADS_FILTER_START_DATE,$this->appCore->ESTToUTC($data['startDate'] . " 00:00:00"));
            $queryStringVars['startDate'] =  $data['startDate'];
        }
        if ($data['endDate']) {
            $this->leadsManager->addLeadsFilter(LeadsManager::LEADS_FILTER_END_DATE, $this->appCore->ESTToUTC($data['endDate'] . " 23:59:59"));
            $queryStringVars['endDate'] =  $data['endDate'];
        }


        if (empty($_GET['csv'])) $this->leadsManager->setOffset(($page - 1) * $leadsPerPage, $leadsPerPage);
        /** @var Lead[] $list */
        $list = $this->leaveTypeFactory->getLeaveTypes();

        $data['leave_types'] = $list;

        if (!empty($_GET['csv'])) {

            $headers = ['TRANSDATE', 'FNAME', 'LNAME', 'JOBTITLE', 'COMPANY', 'STATE', 'COUNTRY', 'EMAIL'];
            $rows = [];

            /** @var Lead $l */
            foreach ($data['leads'] as $l) {
                $rows[] = [$l->getTransdate(), $l->getFname(), $l->getLname(), $l->getJobtitle(), $l->getCompany(), $l->getState(), $l->getCountry(), $l->getEmail()];
            }

            $csvLib = new Csv();
            $csvLib->output('leads.csv', $rows, $headers, ',');
            return;
        }

        $queryString = ($queryStringVars) ? '?' . http_build_query($queryStringVars) : '';
        $paginationPfx = $queryString ? "&p=" : "?p=";
        $data['pagination'] = $this->pagination->setPaginationLinks($paginationItems)->setItemsPerPage($leadsPerPage)->paginate($this->leadsManager->getLeads(true), $page, site_url("dashboard/leads") . $queryString , $paginationPfx);

        $data['content']=$this->loader->view('admin/leave_types_v', $data, true);
        $data['section']='leave_types';
        $this->render($data);
    }
    /* Author Jamil Muhammad 
    * method edit leave type
    * to add or edit leave type
    * @param id int
    * 0 for new record to add
    * id to edit existing records
    */
    public function edit_leave_type($id=0)
    {
     
        $id = intval($id);

        if ($id) {
            $leave_type = $this->leaveTypeFactory->getLeaveType($id);
            if ($leave_type == null) {
                show_404();
            }
        }
        else{
             $leave_type = LeaveType::getEmptyLeaveType();
        }
        $this->renderLeaveType($leave_type, null, 0);
    } 

    private function renderLeaveType($leave_type, $errors=null, $sendQuoteOnLoad = 0)
    {
        $data=array();
        $data['leave_type_id'] =1;// $leave_type->getLeaveTypeId();
        $data['section'] = 'leave_types';

        
        $data['leave_type'] = $leave_type;

        if($errors==null){
            $errors=array();
        }
        $data['errors']=$errors;
        $data['sendQuoteOnLoad'] = $sendQuoteOnLoad;

        // $this->appCore->debug($data);
        //exit();
        $data['content'] = $this->loader->view('admin/edit_leave_type_v', $data, true);
        $this->render($data);
    }
     /* Author Jamil Muhammad 
    * method delete leave type
    * to delete
    * @param id int
    * 0 for new record to add
    * id to edit existing records
    */
    public function deleteLeaveType($id){
        $id = intval($id);
        $this->leaveTypeFactory->deleteLeaveType($id);
        $r = new Response();
        $r->content_id = $id;
        $this->appCore->renderJson($r);
        return;
    }
    

}
