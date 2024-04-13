<?php

namespace IPP\Student;

use DOMNodeList;
use DOMElement;
use DOMNode;

use IPP\Core\AbstractInterpreter;
use IPP\Core\Exception\NotImplementedException;

use IPP\Student\Instruction;
use IPP\Student\StudentExceptions;
use IPP\Student\Opcode;
use IPP\Student\Variable;
use IPP\Student\InstructionExecuter as IExec;

class Interpreter extends AbstractInterpreter
{
    public Variable $globalLiteral;

    /**
     * @var array<Instruction> $instructions list of Instruction objects 
     * holding instructions loaded from XML
     */ 
    private array $instructions; 
    /**
     * @var array<Variable> $variables list of Variable objects holding 
     * declared variables
     */     
    private array $variables;

    public function execute(): int
    {
        // TODO: Start your code here
        // Check \IPP\Core\AbstractInterpreter for predefined I/O objects:
        $dom = $this->source->getDOMDocument();
        // get raw data from input xml 
        $rawInstructions = $dom->getElementsByTagName("instruction");
        // convert raw xml data into and Instruction class/objects 
        $this->instructions = Helper::convertToInstructions($rawInstructions);
        // order instruction lost by Instruction->order value
        $this->instructions = Helper::sortInstructionList($this->instructions);
        // initialize variables list
        $this->variables = [];
        // initialize globalLiteral variable
        $this->globalLiteral = new Variable("", "");

        // DEBUG: print found instructions
        $this->printInstructions($this->instructions);

        $errMsg = $this->performInstructions();
        
        if($errMsg != null)
        {
            $this->stderr->writeString($errMsg . "\n");
            return 1;
        }

        // $val = $this->input->readString();
        // $this->stdout->writeString("stdout");
        // $this->stderr->writeString("stderr");
        // throw new NotImplementedException;

        return 0;
    }
    
    
    /**
     * Performs Instructions in in Instruction list ($instructions) and returns 
     * whenever error occurred 
     *
     * @return ?string Returns error message, null if no error happened
     */
    public function performInstructions() : ?string 
    {
        $errMsg = null;
        // execute instructions in order
        foreach($this->instructions as $instruction)
        {
            // get instruction opcode and convert it into an upper case
            $opcode = $instruction->getOpcode();
            
            $errMsg = null;
            if ( Opcode::isArithmetic($opcode) )
            {
                $errMsg = IExec::Arithmetic($instruction, $this, $opcode);
                if($errMsg != null) { break; }
            }
            else if ($opcode == Opcode::DEFVAR)
            {
                $errMsg = IExec::Defvar($instruction, $this);
                if($errMsg != null) { break; }
            }
            else if($opcode == Opcode::MOVE)
            {
                $errMsg = IExec::Move($instruction, $this);
                if($errMsg != null) { break; }
            }
            else if($opcode == Opcode::READ)
            {
                $errMsg = IExec::Read($instruction, $this);
                if($errMsg != null) { break; }
            }
            else if($opcode == Opcode::WRITE)
            {
                $errMsg = IExec::Write($instruction, $this);
                if($errMsg != null) { break; }
            }
        }
        
        return $errMsg;
    }

    // ------------------------------------------------------------------------
    //
    // ------------------------------------------------------------------------

        
    /**
     * Returns list of variables in current scope/frame
     *
     * @return array<Variable> 
     */
    public function &getVariables() : array
    {
        //TODO: work with scopes
        return $this->variables;
    }

    /**
     * Returns Variable element from the Variable list in current 
     * scope/frame based on provided key ($key) 
     * 
     * @param string $key key that will be looked up in Variable list returned
     * @return ?Variable value found 
     */
    public function &getVariable(string $key) : ?Variable
    {
        foreach($this->getVariables() as $variable)
        {
            if($variable->getName() == $key)
            {
                return $variable;
            }
        }

        return null;
    }

    /**
     * Adds new variable to the variable list in current scope/frame at given 
     * position $pos
     *
     * @param Variable $newVar Variable to be added to the Variable list
     */
    public function add2Variables(Variable $newVar) : void
    {
        //TODO: work with scopes
        $this->variables[] = $newVar;
    }

    /**
     * Prints instructions in $instructionList
     * @param array<Instruction> $instructionList array to be printed
     */
    public function printInstructions(array $instructionList): void
    {
        // for($i = 1; $i < count($instructionList); $i++)
        // {
        //     print $instructionList[$i]->toString() . "\n";
        // }

        foreach($instructionList as $inst)
        {
            $this->println($inst->toString());
        }
    }

    // ------------------------------------------------------------------------
    //
    // ------------------------------------------------------------------------

    public function read(string $type) : int|float|string|bool|null
    {  
        switch(strtoupper($type))
        {
            case Variable::INT:
                return $this->input->readInt();
            case Variable::FLOAT:
                return $this->input->readFloat();
            case Variable::STRING:
                return $this->input->readString();
            case Variable::BOOL:
                return $this->input->readBool();
        }
        throw new StudentExceptions("Unknown type to read in Interpreter::read()", 1); // TODO:
    }

    /**
     * Writes to standard output
     *
     * @param  mixed $value value to be printed to the stdout
     * @return void
     */
    public function print(mixed $value): void
    {
        $stringVal = null;

        if (is_string($value))
            $stringVal = $value;
        else if( is_int($value) || is_float($value) || is_null($value) )
            $stringVal = strval($value);
        else
            return;

        $this->stdout->writeString($stringVal);
    }

    /**
     * Writes to standard output and adds newline
     *
     * @param  mixed $value value to be printed to the stdout
     * @return void
     */
    public function println(mixed $value): void
    {
        $this->print($value . "\n");
    }

} /*INTERPRETER*/