<?php
namespace IPP\Student;

use IPP\Core\AbstractInterpreter;
use IPP\Core\Exception\NotImplementedException;

use IPP\Student\Argument;
use IPP\Student\StudentExceptions;

class Instruction
{
    /** 
     * @var string $opcode Stores operation code (opcode) of the instruction
     */
    protected string $opcode;

    /** 
     * @var int $order Stores order of instruction
     */
    protected int $order;

    /** 
     * @var bool $executed Specifies whenever instruction was already executed
     */
    protected bool $executed;
    
    /** 
     * @var array<Argument> $args list of arguments for given instruction
     */
    protected ?array $args;

    /**
     * Constructor of Instruction class
     * @param string $opcode the operation code (opcode) of the instruction
     * @param int $order the order of the instruction
     * @param array<Argument> $args the list of arguments
     */
    public function __construct(string $opcode, int $order, ?array $args)
    {
        $this->opcode = $opcode;
        $this->order = $order;
        $this->args = $args;
        $this->executed = false;
    }
    
    /**
     * Returns order
     * @return int
     */
    public function getOrder() : int
    {
        return $this->order;
    }
    
    /**
     * Returns opcode
     * @return string
     */
    public function getOpcode() : string
    {
        return $this->opcode;
    }

        
    /**
     * @return array<Argument> returns argument list of this instruction
     */
    public function getArgs() : ?array
    {
        return $this->args;
    }

    /**
     * Returns Argument at given index
     * 
     * @param int $index index of the argument that will be returned
     * 
     * @return ?Argument returns argument at given index
     * 
     */
    public function getArg(int $index) : ?Argument
    {

        if($this->args == null)
        {
            return null;
        }
        
        return $this->args[$index];
    }

        
    /**
     *
     * @return int Length of argument list
     */
    public function getArgsLength() : int
    {
        if($this->args == null)
        {
            return 0;
        }
        else
        {
            return count($this->args);
        }
    }
        
    /**
     * Returns string representation of instruction
     * This function is for debug purposes
     * @return string Converted instruction in an string format
     */
    public function toString() : string
    {
        $string = "opcode=" . $this->opcode . ", order=" .  strval($this->order) . ", arguments:";
        $i = 1;
        if($this->args == null) 
        { 
            return $string;
        }

        foreach($this->args as $arg)
        {
            $string = $string . "\n\t" . "arg" . $i . ":". $arg->toString() ; 
            $i++;
        }
        return $string;
    }
}