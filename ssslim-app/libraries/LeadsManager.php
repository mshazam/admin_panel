<?php
namespace Ssslim\Libraries;
/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

use Ssslim\Core\Libraries\Logger;
use Ssslim\Libraries\Notification\NotificationsManager;
use Ssslim\Libraries\Notification\Notification;

class LeadsManager
{
    private $logger;
    private $db;
    private $cacheFactory;
    private $notificationsManager;

    /* getLeads stuff */
    private $getLeadsAndFilters = [];

    private $limit = 0;
    private $offset = 0;

    const LEADS_FILTER_START_DATE = 1;
    const LEADS_FILTER_END_DATE = 2;
    const LEADS_FILTER_STATUS = 3;
    const LEADS_FILTER_TYPE = 4;
    /* end of getLeads stuff */

    private $attachmentDownloadEndPoint = "get_quote/";

    public function __construct(Logger $logger, \DB $db, Cache\CacheFactory $cacheFactory)
    {
        $this->logger = $logger;
        $this->db = $db;
        $this->cacheFactory = $cacheFactory;
    }

    public static function getFromDbRow($data) {
        return new Lead($data);
    }

    public function addLeadsFilter($filterType, $filterValue = null, $type = "AND")
    {
        $f = new \stdClass();
        $f->type = $filterType;
        $f->value = $filterValue;

        $this->getLeadsAndFilters[] = $f;
        return $this;
    }

    public function setOffset($offset, $limit = null)
    {
        if ($offset !== null) $this->offset = $offset;
        if ($limit !== null) $this->limit = $limit;
        return $this;
    }

    function getLeads($returnCount = false){

        $qWhere = " WHERE 1";

        foreach ($this->getLeadsAndFilters as $f) {

            switch ($f->type) {
                case self::LEADS_FILTER_START_DATE:
                    $qWhere .= " AND leads.transdate >= '" . $f->value . "'";
                    break;


                case self::LEADS_FILTER_END_DATE:
                    $qWhere .= " AND leads.transdate <= '" . $f->value . "'";
                    break;


/*                case self::LEADS_FILTER_STATUS:
                    $qWhere .= " AND leads.status = '" . $f->value . "'";
                    break;


                case self::LEADS_FILTER_TYPE:
                    $qWhere .= " AND leads.type = '" . $f->value . "'";
                    break;*/

                default:
                    throw new \Exception("UNKNOWN FILTER");
                    break;
            }
        }

        $qLimit = ($this->limit) ? " LIMIT $this->offset , $this->limit" : '';

        if ($returnCount) return $this->db->query("SELECT COUNT(lead_id) AS n FROM leads $qWhere ")->row()->n;
        else $r = $this->db->query("SELECT leads.* FROM leads 
                                        /*LEFT JOIN gdpr ON gdpr.q_user_id = leads.q_user_id*/  
                                        $qWhere 
                                        ORDER BY transdate DESC $qLimit")->result();

        $toReturn = [];
        foreach ($r as $item) {
            $toReturn[] = self::getFromDbRow($item);
        }
        return $toReturn;

       /* $c = $this->cacheFactory->get("contents/leads/list");

        if (true || !$c->isValid()) {
            $l=$this->db->query("SELECT lead_id FROM leads ORDER BY generatedTime ASC")->result();
            $c->userData = $l;
            $c->commit(24);
        }

        return $c->userData;*/
    }

    /**
     * @param $lead_id
     * @return Lead
     */
    function getLead($lead_id){
        $c = $this->cacheFactory->get("contents/leads/".$lead_id);

        if (true || !$c->isValid()) {
            $e = $this->db->query("SELECT leads.*/*, COALESCE(gdpr.GDPRAcceptedDate, '') AS GDPRAcceptedDate */FROM leads 
                                    /*LEFT JOIN gdpr ON gdpr.q_user_id = old_leads.q_user_id*/
                                    WHERE lead_id=$lead_id")->row();
            // $this->logger->debug($e);
            if(!empty($e)) {
                $c->userData = new Lead($e);
            }else{
                $c->userData=null;
            }
//            $c->commit(24);
        }

        return $c->userData;
    }

    /**
     * @param string $email
     * @return Lead
     * @throws \Exception
     */
    public function getLeadFromEmail($email = "")
    {
        if (!$email) throw new \Exception("emailNotProvided");
        $l = $this->db->query("SELECT * FROM leads WHERE email = " . $this->db->escape($email))->row();
        return new Lead($l ? $l : null);
    }

    public function deleteLead($lead_id){
        $this->db->query("DELETE FROM leads WHERE lead_id=$lead_id");

        $this->cacheFactory->invalidateCacheFile("contents/leads/$lead_id");
//        $this->cacheFactory->invalidateCacheFile("contents/leads/list/".LeadsManager::DEFAULT_EVENTS_LABEL);
    }

    public function saveLead(Lead $e){
        if($e->getLeadId() > 0){
            $lead_id=intval($e->getLeadId());

            //This is an update, we can proceed!
            $this->db->query(
                "UPDATE leads SET
                 fname=".$this->db->escape($e->getFname())
                .",lname=".$this->db->escape($e->getLname())
                .",jobtitle=".$this->db->escape($e->getJobtitle())

                .",company=".$this->db->escape($e->getCompany())
                .",state=".$this->db->escape($e->getState())
                .",country=".$this->db->escape($e->getCountry())
                .",email=".$this->db->escape($e->getEmail())
                .($e->getTransdate() ? ",transdate=".$this->db->escape($e->getTransdate()) : "")
                ." WHERE lead_id=$lead_id");
        }else{
            //This is an insert
            $this->db->query(
                "INSERT IGNORE INTO leads (transdate, fname, lname, jobtitle, company, state, country, email) VALUES ("
                . ($e->getTransdate() ? $this->db->escape($e->getTransdate()) :  $this->db->escape(gmdate("Y-m-d H:i:s")))
                .",".$this->db->escape($e->getFname())
                .",".$this->db->escape($e->getLname())
                .",".$this->db->escape($e->getLeadId())
                .",".$this->db->escape($e->getCompany())
                .",".$this->db->escape($e->getState())
                .",".$this->db->escape($e->getCountry())
                .",".$this->db->escape($e->getEmail())
                .")");
            if($this->db->affected_rows()==0){
                return false;
            }

            $e->setLeadId($this->db->insert_id());
        }

        $this->cacheFactory->invalidateCacheFile("contents/leads/".$e->getLeadId());

        return $e;
    }

    public function getAvailableCountries()
    {
        $toReturn = [];
        $countries = $this->db->query('SELECT * FROM countries')->result();
        foreach ($countries as $country) {
            $toReturn[] = ['name' => $country->name, 'code' => $country->country_code];
        }
        return $toReturn;
    }

}


class Lead{

    private $lead_id=0;
    public $transdate = "";
    private $fname = "";
    private $lname = "";
    private $jobtitle = "";
    private $company = "";
    private $state = "";
    private $country = "";
    private $email = "";

    private static $empty;

    public function __construct($data)
    {
        if($data==null){
            $this->transdate = gmdate('Y-m-d H:i:s');
            return;
        }

        $this->lead_id=$data->lead_id;
        $this->transdate=$data->transdate;

        if (isset($data->fname)) $this->fname=$data->fname;
        if (isset($data->lname)) $this->lname=$data->lname;
        if (isset($data->jobtitle)) $this->jobtitle=$data->jobtitle;
        if (isset($data->company)) $this->company=$data->company;
        if (isset($data->state)) $this->state=$data->state;
        if (isset($data->country)) $this->country=$data->country;
        if (isset($data->email)) $this->email=$data->email;
    }

    /**
     * @return int
     */
    public function getLeadId()
    {
        return $this->lead_id;
    }

    /**
     * @param int $lead_id
     * @return Lead
     */
    public function setLeadId($lead_id)
    {
        $this->lead_id = $lead_id;
        return $this;
    }

    /**
     * @return string
     */
    public function getTransdate()
    {
        return $this->transdate;
    }

    /**
     * @param string $transdate
     * @return Lead
     */
    public function setTransdate($transdate)
    {
        $this->transdate = $transdate;
        return $this;
    }

    /**
     * @return string
     */
    public function getFname()
    {
        return $this->fname;
    }

    /**
     * @param string $fname
     * @return Lead
     */
    public function setFname($fname)
    {
        $this->fname = $fname;
        return $this;
    }

    /**
     * @return string
     */
    public function getLname()
    {
        return $this->lname;
    }

    /**
     * @param string $lname
     * @return Lead
     */
    public function setLname($lname)
    {
        $this->lname = $lname;
        return $this;
    }

    /**
     * @return string
     */
    public function getJobtitle()
    {
        return $this->jobtitle;
    }

    /**
     * @param string $jobtitle
     * @return Lead
     */
    public function setJobtitle($jobtitle)
    {
        $this->jobtitle = $jobtitle;
        return $this;
    }

    /**
     * @return string
     */
    public function getCompany()
    {
        return $this->company;
    }

    /**
     * @param string $company
     * @return Lead
     */
    public function setCompany($company)
    {
        $this->company = $company;
        return $this;
    }

    /**
     * @return string
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * @param string $state
     * @return Lead
     */
    public function setState($state)
    {
        $this->state = $state;
        return $this;
    }

    /**
     * @return string
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * @param string $country
     * @return Lead
     */
    public function setCountry($country)
    {
        $this->country = $country;
        return $this;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param string $email
     * @return Lead
     */
    public function setEmail($email)
    {
        $this->email = $email;
        return $this;
    }



    public static function getEmpty(){
        if(Lead::$empty==null){
            Lead::$empty=new Lead(null);
        }
        return Lead::$empty;
    }

}


