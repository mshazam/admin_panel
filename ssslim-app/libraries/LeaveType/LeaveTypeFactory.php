<?php
/**
 * Created by IntelliJ IDEA.
 * User: kRs
 * Date: 12/07/2016
 * Time: 13.18
 */
namespace Ssslim\Libraries\LeaveType;

require_once "LeaveTypeExceptions.php";

use Ssslim\Core\Libraries\Logger;
use Ssslim\Libraries\Cache\CacheFactory;
use Ssslim\Libraries\Token;

class LeaveTypeFactory
{
    /** @var  \Ssslim\Libraries\DeviceData */
    public $deviceData = null; // this is meant to be assigned in AppCore constructor so we must check that it's not null before using it

    private $logger;
    private $db;
    private $cacheFactory;
    private $notificationsManager;
    private $token;

    private $myLeaveType;

    public function __construct(Logger $logger, \DB $db, CacheFactory $cacheFactory, Token $token)
    {
        $this->logger = $logger;
        $this->db = $db;
        $this->cacheFactory = $cacheFactory;
        $this->token = $token;

    
    }
    public static function getFromDbRow($data) {
        return new LeaveType($data);
    }
    function getLeaveTypes($returnCount = false){

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

        if ($returnCount) {
             return $this->db->query("SELECT COUNT(leave_type_id) AS n FROM leave_types $qWhere ")->row()->n;
        } else {
             $sql = "SELECT leave_types.* FROM leave_types $qWhere  ORDER BY leave_type_id $qLimit";
             $r = $this->db->query($sql)->result();
        }
        //var_dump($r);
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
     * @param $leave_type_id
     * @return leave_type
     */
    function getLeaveType($leave_type_id){
            
            $leave_type = $this->db->query( "SELECT * from leave_types where leave_type_id =".$leave_type_id )->row();
            // $this->logger->debug($leave_type);
            if(!empty($leave_type)) {
               return $leave_type;
            }else{
                return null;
            }

    }

    public static function getEmptyLeaveType(){
        if(LeaveType::$empty==null){
            LeaveType::$empty=new LeaveType(null);
        }
        return LeaveType::$empty;
    }
}

class LeaveType implements \JsonSerializable {
    private $leave_type_id = 0;
    private $country;
    private $time_account_type;
    private $translation;
    private $enabled;
    private $attachment;

    /**
     * @return int
     */
    public function getLeaveTypeId()
    {
        return $this->leave_type_id;
    }

    /**
     * @return mixed
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * @return string
     */
    public function getTimeAccountType()
    {
        return $this->time_account_type;
    }

    /**
     * @return mixed
     */
    public function getTranslation()
    {
        return $this->translation;
    }

    /**
     * @return mixed
     */
    public function getEnabled()
    {
        return $this->enabled;
    }

    /**
     * @return mixed
     */
    public function getAttachment()
    {
        return $this->attachment;
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
        if(isset($data->leave_type_id)) {
            $this->leave_type_id = $data->leave_type_id;
        }

        if (isset($data->country)){
             $this->country = $data->country;
        }

        if (isset($data->time_account_type)){
             $this->time_account_type = $data->time_account_type;
        }

        if (isset($data->translation)) {
             $this->translation = $data->translation;
        }

        if (isset($data->enabled)){
             $this->enabled = $data->enabled;
        }

        if (isset($data->attachment)){
             $this->attachment=$data->attachment;
        }

    }
    public function jsonSerialize()
    {
        // TODO: Implement jsonSerialize() method.
        return ['user_id' => $this->user_id, 'first_name' => $this->first_name, 'last_name' => $this->last_name, 'company_name' => $this->company_name, 'phone' => $this->phone, 'email' => $this->email, 'vendor_id' => $this->vendor_id, 'active' => $this->active ];
    }

}