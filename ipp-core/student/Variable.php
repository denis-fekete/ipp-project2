<?php
namespace IPP\Student;

use IPP\Student\Argument;

class Variable
{
    public const string NIL     = Argument::LITERAL_NIL;
    public const string INT     = Argument::LITERAL_INT;
    public const string FLOAT   = Argument::LITERAL_FLOAT;
    public const string STRING  = Argument::LITERAL_STRING;
    public const string BOOL    = Argument::LITERAL_BOOL;

    public const string TYPE    = "type";

    public const string TF_SCOPE = "TF";
    public const string LF_SCOPE = "LF";
    public const string GF_SCOPE = "GF";

    /** 
     * @var bool $defined bool value representing whenever variable was defined
     */
    protected bool $defined;
    /** 
     * @var string $name name of variable
     */
    protected string $name;
    // /** 
    //  * @var string $scope scope in which variable is defined
    //  */
    // protected string $scope;
    /** 
     * @var string $type type of variable
     */
    protected string $type;
    /** 
     * @var string|int|float|bool|null $value value that variable holds
     */
    protected $value;
    
    /**
     * Constructor of Variable object
     * @param string $name name to be set to the variable
     * @return void
     */
    // public function __construct(string $name, string $scope)
    public function __construct(string $name)
    {
        $this->name = $name;
        // $this->scope = $scope;
        $this->type = "";
        $this->defined = false;
    }
    
    /**
     * Sets value of Variable object, and sets it as defined
     *
     * @param string|int|float|bool|null $value value of the variable
     * @param string $type types of the variable
     * 
     * @return void
     */
    public function setValue(string|int|float|bool|null $value, string $type) : void
    {
        $this->defined = true;

        switch($type)
        {
            case Variable::NIL:
            case Variable::INT:
            case Variable::STRING:
            case Variable::FLOAT:
            case Variable::BOOL:
            case Variable::TYPE:
                $this->type = $type;
                break;
            default:
                throw new StudentExceptions("Unknown variable type: " . $type . "\n", 1); /*TODO:*/
        }

        $this->value = $value;
    }
    
    /**
     *
     * @return  string|int|null|float|bool returns value of the variable 
     */
    public function getValue() : string|int|null|float|bool
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