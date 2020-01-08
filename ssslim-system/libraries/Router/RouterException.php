<?php
namespace Ssslim\Core\Libraries\Router;

class RouterException extends \RuntimeException
{
    /**
     * The error code.
     *
     * @var integer
     */
    protected $code = 500;
}
