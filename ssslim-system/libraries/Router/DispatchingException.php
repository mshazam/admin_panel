<?php
namespace Ssslim\Core\Libraries\Router;

class DispatchingException extends \RuntimeException
{
    /**
     * The error code.
     *
     * @var integer
     */
    protected $code = 500;
}
