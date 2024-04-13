<?php
namespace IPP\Student;

use BadFunctionCallException;

class Variable
{
    public const string NULL   = "NULL";
    public const string INT    = "INT";
    public const string FLOAT  = "FLOAT";
    public const string STRING = "STRING";

    /** 
     * @var bool $defined bool value representing whenever variable was defined
     */
    protected bool $defined;
    /** 
     * @var string $name name of variable
     */
    protected string $name;
    /** 
     * @var string $scope scope in which variable is defined
     */
    protected string $scope;
    /** 
     * @var string $type type of variable
     */
    protected string $type;
    /** 
     * @var string|int|float|null $value value that variable holds
     */
    protected $value;
    
    /**
     * Constructor of Variable object
     * @param string $name name to be set to the variable
     * @param string $scope scope of the variable
     * @return void
     */
    public function __construct(string $name, string $scope)
    {
        $this->name = $name;
        $this->scope = $scope;
        $this->type = "null";
        $this->defined = false;
    }
    
    /**
     * Sets value of Variable object, and sets it as defined
     *
     * @param string $value value of the variable
     * @param string $type types of the variable
     * 
     * @return void
     */
    public function setValue(string $value, string $type) : void
    {
        $this->defined = true;

        switch($type)
        {
            case Variable::NULL:
            case Variable::INT:
            case Variable::STRING:
            case Variable::FLOAT:
                $this->type = $type;
                break;
            default:
                throw new StudentExceptions("Unknown variable type: " . $type . "\n", 1); /*TODO:*/
        }

        $this->value = $value;
    }
    
    /**
     *
     * @return  string|int|null|float returns value of the variable 
     */
    public function getValue() : string|int|null|float
    {
        return $this->value;
    }

    /**
     * Returns whenever this Variable is defined
     *
     * @return bool true if Variable is defined, false if not defined
     */
    public function isDefined() : bool
    {
        return $this->defined;   
    }
    
    /**
     * Returns type of this variable
     * 
     * @return string int representation of variable
     */
    public function getType() : string
    {
        return $this->type;
    }
    
    /**
     * Returns name of Variable
     *
     * @return string string representation of the variable name
     */
    public function getName() : string
    {
        return $this->name;
    }

    public function toString() : string 
    {
        return "variable: name=" . $this->name . ", type=" . $this->type . 
            ", value=" . strval($this->value) . ", isDefined=" . 
            strval($this->isDefined());  
    }
}