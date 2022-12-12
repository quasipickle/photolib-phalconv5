<?php

namespace Component;

use Phalcon\Http\Response;

/**
 * A simple helper class for returning values (usually via JSON)
 *
 * Pass an associative array when constructing to set initial properties

 * Call a method on the class to set a property with that name.
 *  ex: $Retval->success(false) sets $Retval->success = false;
 *
 * Method is chainable, so this works:
 *  $Retval->success(false)->message("Error");
 *
 * return response() to get a proper Phalcon response object
 */
class Retval
{
    private $properties = [];

    public function __construct(array $properties = [])
    {
        if (!array_key_exists("success", $properties)) {
            $properties["success"] = false;
        }

        foreach ($properties as $property => $name) {
            $this->properties[$property] = $name;
        }
    }

    /**
     * Magic function that would get called when setting a property
     *
     * Usage: $Retval->success("Yay");
     *
     * If called with no parameter, returns the property
     * If called with a parameter, returns $this
     *
     * @param string $name PHP provided name of function
     * @param [mixed] $args The arguments passed
     * @return mixed
     */
    public function &__call(string $name, $args)
    {
        if ($args === []) {
            return $this->properties[$name];
        } else {
            $this->properties[$name] = $args[0];
            return $this;
        }
    }

    /**
     * Generate & return a Response object for this object
     *
     * @return Response
     */
    public function response(): Response
    {
        $Response = new Response();
        $Response->setContent(json_encode($this->properties));
        $Response->setContentType("application/json", "UTF-8");
        return $Response;
    }
}
