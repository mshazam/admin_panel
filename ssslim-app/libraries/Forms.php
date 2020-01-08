<?php
/**
 * Created by PhpStorm.
 * User: mauryr
 * Date: 15/07/16
 * Time: 16:59
 */
namespace Ssslim\Libraries {

    use Ssslim\Core\Libraries\Logger;

    if (!defined('BASEPATH'))
        exit('No direct script access allowed');

    class Forms{
        private $logger;

        public function __construct(Logger $logger)
        {
            $this->logger=$logger;
        }

        function getForm(array $fields){
            return new Form($fields);
        }


        function getValidationErrors(Form $form, $values){
            $errors=array();
            foreach($form->fields as $field){
                if($field->mandatory){
                    $fname=$field->name;
                    if(!isset($values->$fname) || $values->$fname==""){
                        $errors[$fname]="MISSING";
                        continue;
                    }
                }/*else if(key_exists($field->name)){
                    //Do additional validation here!
                }*/
            }
            return $errors;
        }
    }

    class Form{
        var $fields;
        function __construct(array $fields)
        {
            foreach($fields as $field){
                if(!($field instanceof FormField)){
                    throw new InvalidFormFieldException();
                }
            }
            $this->fields=$fields;
        }

    }
    class FormField{
        var $name;
        var $mandatory;

        function __construct($name, $mandatory=false)
        {
            $this->name=$name;
            $this->mandatory=$mandatory;
        }
        //...
    }
    class InvalidFormFieldException extends \Exception{
        function __construct($message)
        {
            parent::__construct($message, 0, null);
        }
    }
}
