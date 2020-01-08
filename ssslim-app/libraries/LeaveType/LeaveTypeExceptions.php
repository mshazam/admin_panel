<?php
/**
 * Created by PhpStorm.
 * User: mauryr
 * Date: 15/07/16
 * Time: 10:53
 */

namespace Ssslim\Libraries\LeaveType {

    Class LeaveTypeNotFoundException extends \Exception{
        function __construct()
        {
            parent::__construct("USER_NOT_FOUND", 0, null);
        }
    }

    Class LeaveTypeInactiveException extends \Exception{
        function __construct()
        {
            parent::__construct("USER_INACTIVE", 0, null);
        }
    }
}