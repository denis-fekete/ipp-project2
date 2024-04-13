<?php
namespace IPP\Student;

class Argument
{
    public const string VAR = "VAR";
    public const string LABEL = "LABEL";

    public const string LF = "LF@"; 
    public const string GF = "GF@"; 

    public const string SCOPE_INT = "INT@"; 
    public const string SCOPE_STRING= "STRING@"; 
    public const string SCOPE_FLOAT = "FLOAT@"; 
    public const string SCOPE_NILL = "NILL@"; 


    /** 
     * @var int $order order of argument in instruction
     */
    protected int $order;
    /** 
     * @var string $type type of argument
     */
    protected string $type;
    /** 
     * @var string $value value of argument
     */
    protected string $value;

        
    /**
     * Constructor of Argument class
     *
     * @param int $order order of argument in instruction
     * @param string $type type of argument
     * @param string $value value of argument
     * @return void
     */
    public function __construct(int $order, string $type, string $value)
    {
        $this->order = $order;
        $this->type = $type;
        $this->value = $value;
    }
    
    /**
     * Converts argument to string representation
     * This function is for debug purposes
     * @return string representation of argument in string format
     */
    public function toString() : string
    {
        return "order=" . $this->order . ", type=" . $this->type . ", value=" . $this->value;
    }
    
    /**
     * @return int return order of argument
     */
    public function getOrder() : int
    {
        return $this->order;
    }
    
    /**
     * @return string return type of argument
     */
    public function getType() : string
    {
        return $this->type;
    }
    
    /**
     * @return string returns value of argument
     */ 
    public function getValue() : string
    {
        return $this->value;
    }

    
    /**
     * Breaks value of argument into name and scope 
     * @param string $value value to be broken into name and scope
     * 
     * @return array<string, string> containing name and scope
     */
    public static function breakIntoNameAndScope(string $value) : ?array
    {
        $atPos = strpos($value, "@");

        $name = "";
        $scope = "";
        if($atPos != null)
        {
            $scope = substr($value, 0, $atPos + 1);
            $name = substr($value, $atPos + 1);

            return ["name" => $name, "scope" => $scope];
        } 

        return null;
    }
}