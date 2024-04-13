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

class Interpreter extends AbstractInterpreter
{
    private const int CHECK_DECLARED = 0;
    private const int CHECK_DEFINED = 1;
    private Variable $globalLiteral;

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
    
    // ------------------------------------------------------------------------
    //
    // ------------------------------------------------------------------------

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
                $errMsg = $this->performArithmeticInstruction($instruction, $opcode);
                if($errMsg != null) { break; }
            }
            else if ($opcode == Opcode::DEFVAR)
            {
                $errMsg = $this->performDefvarInstruction($instruction);
                if($errMsg != null) { break; }
            }
            else if($opcode == Opcode::MOVE)
            {
                $errMsg = $this->performMoveInstruction($instruction);
                if($errMsg != null) { break; }
            }
        }

        return $errMsg;
    }
    
    /**
     * Check if variable is defined/declared (based on $check) and 
     * returns Variable element from global Variable array  
     *
     * @param Argument $arg argument to be checked
     * @param ?Variable &$value output variable to hold found Variable in Variable list
     * @return ?string returns error message
     */
    public function checkVariableList(Argument &$arg, int $check, ?Variable &$value) : ?string
    {
        if($arg->getType() == Argument::VAR)
        {
            /** @var array<string,string> arr */
            $arr = Argument::breakIntoNameAndScope($arg->getValue());

            // look up variable in the Variable array/list
            $variable = $this->getVariable($arr["name"]);

            // check if argument of type variable is not in variable list
            if($variable == null)
            {
                $value = null;
                return "Variable with name \"" . $arr["name"] . "\" is not declared";
            }

             // if $check is check defined, check if defined 
            if($check == Interpreter::CHECK_DEFINED)
            {
                if(!$variable->isDefined())
                {
                    $value = null;
                    return "Variable is not defined";
                }
                
                $value = $variable;
                return null;
            }
            else if($check == Interpreter::CHECK_DECLARED)
            {
                $value = $variable;
                return null;
            }
            else
            {
                $value = null;
                return "Unexpected \$check value in checkVariableList()";
            }
        }
        else
        {
            // update global literal and pass it as value
            $this->globalLiteral->setValue($arg->getValue(), $arg->getType()) ;
            $value = $this->globalLiteral;
            return null;
        }
    }
    
    /**
     * Perform move instruction on given instruction
     *
     * @param Instruction $instruction instruction that will be performed
     * @return ?string returns null if no error was found, returns error message 
     * if error was found
     */
    public function performMoveInstruction(Instruction $instruction): ?string
    {
        // semantic control: number of arguments
        if( $instruction->getArgsLength() != 2 )
            return "Bad argument count";   

            
        $argResult = $instruction->getArg(0);
        if($argResult == null) { return "Argument 1 not found";}

        $arg2 = $instruction->getArg(1);
        if($arg2 == null) { return "Argument 2 not found";}

        /** @var Variable valueResult */
        $valueResult = null;
        /** @var Variable value2 */
        $value2 = null;

        // check if variable is defined, if not return error message
        $errMsg = $this->checkVariableList($argResult, Interpreter::CHECK_DECLARED, $valueResult);        
        if($errMsg != null) { return $errMsg; }

        // check if variable is defined, if not return error message
        $errMsg = $this->checkVariableList($arg2, Interpreter::CHECK_DEFINED, $value2);        
        if($errMsg != null) { return $errMsg; }

        $valueResult->setValue($value2->getValue(), $value2->getType());

        return null;
    }

    /**
     * Perform defvar instruction on given instruction
     *
     * @param Instruction $instruction instruction that will be performed
     * 
     * @return ?string returns null if no error was found, returns error message 
     * if error was found
     */
    public function performDefvarInstruction(Instruction &$instruction) : ?string
    {
        // semantic control: number of arguments
        if( $instruction->getArgsLength() != 1 )
            return "Bad argument count";     

        $arg1 = $instruction->getArg(0);

        if($arg1 == null)
            return "Argument not found inside instruction";
    
        if($arg1->getType() != Argument::VAR)
            return "Bad argument type (" . $arg1->getType() . ")";

        /** @var array<string,string> arr */
        $arr = Argument::breakIntoNameAndScope($arg1->getValue());

        // adds new variable into an variable list
        $this->add2Variables(new Variable($arr["name"], $arr["scope"]));

        return null;
    }

    /**
     * Perform arithmetic instruction on given instruction
     *
     * @param Instruction $instruction instruction that will be performed
     * @param string $opcode opcode of instruction 
     * 
     * @return ?string returns null if no error occurred, returns error 
     * message (string) if error occurred
     */
    public function performArithmeticInstruction(Instruction &$instruction, string &$opcode) : ?string
    {   
        // semantic control: number of arguments
        if( $instruction->getArgsLength() != 3)
        {
            if( ($opcode == Opcode::opNOT || $opcode == Opcode::INT2CHAR) && $instruction->getArgsLength() != 2)
            {
                return "Bad argument count";
            }
        }     

        $argResult = $instruction->getArg(0);
        if($argResult == null) { return "Argument 1 not found";}

        $arg1 = $instruction->getArg(1);
        if($arg1 == null) { return "Argument 2 not found";}

        $arg2 = null;
        // if operation is NOT or INT2CHAR no need for 2nd value/argument
        if($opcode != Opcode::opNOT && $opcode != Opcode::INT2CHAR)
        {
            $arg2 = $instruction->getArg(2);
            if($arg2 == null) { return "Argument 3 not found";}
        }

        /** @var Variable value1 */
        $value1 = null;
        /** @var Variable value2 */
        $value2 = null;
        /** @var Variable result */
        $valueResult = null;

        // check if variable is defined, if not return error message
        $errMsg = $this->checkVariableList($arg1, Interpreter::CHECK_DEFINED, $value1);        
        if($errMsg != null) { return $errMsg; }

        // if operation is NOT or INT2CHAR no need for 2nd value/argument
        if($opcode != Opcode::opNOT && $opcode != Opcode::INT2CHAR)
        {
            // check if variable is defined, if not return error message
            $errMsg = $this->checkVariableList($arg2, Interpreter::CHECK_DEFINED, $value2);        
            if($errMsg != null) { return $errMsg; }
        }
            
        // check if variable is declared, if not return error message
        $errMsg = $this->checkVariableList($argResult, Interpreter::CHECK_DECLARED, $valueResult);        
        if($errMsg != null) { return $errMsg; }

        $type = $value1->getType();

        switch($opcode)
        {
            case Opcode::ADD:
                $valueResult->setValue($value1->getValue() + $value2->getValue(), $type);
                break;
            case Opcode::SUB:
                $valueResult->setValue($value1->getValue() - $value2->getValue(), $type);
                break;
            case Opcode::SUB:
                $valueResult->setValue($value1->getValue() * $value2->getValue(), $type);
                break;
            case Opcode::IDIV:
                $valueResult->setValue($value1->getValue() / $value2->getValue(), $type);
                break;
            case Opcode::LT:
                $valueResult->setValue($value1->getValue() < $value2->getValue(), $type);
                break;
            case Opcode::GT:
                $valueResult->setValue($value1->getValue() > $value2->getValue(), $type);
                break;
            case Opcode::opAND:
                $valueResult->setValue($value1->getValue() && $value2->getValue(), $type);
                break;
            case Opcode::opOR:
                $valueResult->setValue($value1->getValue() || $value2->getValue(), $type);
                break;
            case Opcode::opNOT:
                $valueResult->setValue($value1->getValue(), $type);
                break;
            case Opcode::INT2CHAR:
                $valueResult->setValue($value1->getValue(), $type);
                break;
            default:
                throw new StudentExceptions("Internal error: Unexpected 
                \$opcode in performArithmeticInstruction()", 1); // TODO:
        }

        // DEBUG:
        $this->println("Calculated value: " . strval($valueResult->getValue()));
        return null;
    }

    // ------------------------------------------------------------------------
    //
    // ------------------------------------------------------------------------

        
    /**
     * Returns list of variables in current scope/frame
     *
     * @return array<Variable> 
     */
    public function getVariables() : array
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
    public function getVariable(string $key) : ?Variable
    {
        //TODO: working with scopes
        foreach($this->variables as $variable)
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