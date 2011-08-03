<?php
/**
 * Description of Base
 *
 * @author cdraycott
 * Defines a base set of methods for a data model, along with abstract definitions for each model
 */

abstract class BasePayvment {

    /**
     * This magic function will be called if the given method 
     * does not exist. 
     * @param string $methodName
     * @param mixed $argument
     */
    public function __call($methodName, $argument)
    {
        preg_match("/(get|set)(.*)$/", $methodName, $pieces);
        if (count($pieces) != 3)
        {
            throw new \Exception("Method $methodName not found on class ".get_class($this));
        }

        $pieces[2] = lcfirst($pieces[2]);

        switch ($pieces[1]) {
            case 'get':
                return $this->get('_'.$pieces[2]);
                break;
            case 'set':
                $this->set('_'.$pieces[2], $argument[0]);
                break;
            default:
                throw new \Exception("Method $methodName not found on class ".get_class($this));
        }
    }

    /*
     * Default set function. Will set the internal property to the passed 
     * argument value
     * @param string $methodName
     * @param unknown_type $argument
     */
    private function set($variableName, $argument)
    {
        if (property_exists(get_class($this), $variableName))
        {
            $this->$variableName = $argument;
        }
        else {
            throw new \Exception("Member $variableName does not exist on class ".get_class($this) . ' so could not be set');
        }
    }

     /*
     * Retrieve the internal property based on the method name
     * @param string $methodName
     * @return mixed attributes of the object
     */
    private function get($variableName)
    {
        if (property_exists(get_class($this), $variableName))
        {
            return $this->$variableName;
        }
        else {
            throw new \Exception("Member $variableName does not exist on class ".get_class($this));
        }
    }

}