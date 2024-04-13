<?php
namespace IPP\Student;

use IPP\Student\Instruction;
use IPP\Student\Variable;
use IPP\Student\Argument;
use IPP\Student\Interpreter;

class InstructionExecuter
{
    private const int CHECK_DECLARED = 0;
    private const int CHECK_DEFINED = 1;

    /**
     * Perform input instruction on given instruction
     *
     * @param Instruction $instruction instruction that will be performed
     * @param Interpreter $interpreter interpreter object
     * @return ?string returns null if no error occurred, returns error 
     * message (string) if error occurred
     */
    public static function Read(Instruction &$instruction, Interpreter &$interpreter) : ?string
    {
        $argResult = $instruction->getArg(0);
        if($argResult == null) { return "Argument 1 not found";} // TODO: err message
        $arg1 = $instruction->getArg(1);
        if($arg1 == null) { return "Argument 2 not found";} // TODO: err message

        /** @var Variable result */
        $valueResult = null;

        // check if variable is declared, if not return error message
        $errMsg = InstructionExecuter::checkVariableList($interpreter, $argResult, InstructionExecuter::CHECK_DECLARED, $valueResult);        
        if($errMsg != null) { return $errMsg; }

        $val = null;
        // check if value1 is type
        if($arg1->getType() != Variable::TYPE)
        { return "Second argument is not type"; } 

        $val = $interpreter->read($arg1->getValue());

        if($val == null)
        { return "Reading from standard input failed"; } // TODO:
        
        $valueResult->setValue($val, strtoupper($arg1->getValue()));

        return null;
    }

    /**
     * Perform defvar instruction on given instruction
     *
     * @param Instruction $instruction instruction that will be performed
     * @param Interpreter $interpreter interpreter object
     * @return ?string returns null if no error was found, returns error message 
     * if error was found
     */
    public static function Defvar(Instruction &$instruction, Interpreter &$interpreter) : ?string
    {
        $arg1 = $instruction->getArg(0);

        if($arg1 == null)
            return "Argument not found inside instruction";
    
        if($arg1->getType() != Argument::VAR)
            return "Bad argument type (" . $arg1->getType() . ")";

        /** @var array<string,string> arr */
        $arr = Argument::breakIntoNameAndScope($arg1->getValue());

        // adds new variable into an variable list
        $interpreter->add2Variables(new Variable($arr["name"], $arr["scope"]));

        return null;
    }
    

    /**
     * Perform arithmetic instruction on given instruction
     *
     * @param Instruction $instruction instruction that will be performed
     * @param Interpreter $interpreter interpreter object
     * @param string $opcode opcode of instruction 
     * 
     * @return ?string returns null if no error occurred, returns error 
     * message (string) if error occurred
     */
    public static function Arithmetic(Instruction &$instruction, Interpreter &$interpreter, string &$opcode) : ?string
    {   
        $argResult = $instruction->getArg(0);
        if($argResult == null) { return "Argument 1 not found";} // TODO: err message

        $arg1 = $instruction->getArg(1);
        if($arg1 == null) { return "Argument 2 not found";} // TODO: err message

        $arg2 = null;
        // if operation is NOT or INT2CHAR no need for 2nd value/argument
        if($opcode != Opcode::opNOT && $opcode != Opcode::INT2CHAR)
        {
            $arg2 = $instruction->getArg(2);
            if($arg2 == null) { return "Argument 3 not found";} // TODO: err message
        }

        /** @var Variable value1 */
        $value1 = null;
        /** @var Variable value2 */
        $value2 = null;
        /** @var Variable result */
        $valueResult = null;

        // check if variable is defined, if not return error message
        $errMsg = InstructionExecuter::checkVariableList($interpreter, $arg1, InstructionExecuter::CHECK_DEFINED, $value1);        
        if($errMsg != null) { return $errMsg; }

        // if operation is NOT or INT2CHAR no need for 2nd value/argument
        if($opcode != Opcode::opNOT && $opcode != Opcode::INT2CHAR && $opcode != Opcode::STRI2INT)
        {
            // check if variable is defined, if not return error message
            $errMsg = InstructionExecuter::checkVariableList($interpreter, $arg2, InstructionExecuter::CHECK_DEFINED, $value2);        
            if($errMsg != null) { return $errMsg; }
        }
            
        // check if variable is declared, if not return error message
        $errMsg = InstructionExecuter::checkVariableList($interpreter, $argResult, InstructionExecuter::CHECK_DECLARED, $valueResult);        
        if($errMsg != null) { return $errMsg; }
        
        $type = null;
        // check variable types based on $opcode, return type in $type variable
        $errMsg = Helper::checkVariableType($opcode, $value1, $value2, $type);
        if($errMsg != null) { return $errMsg; }

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
                $valueResult->setValue(!$value1->getValue(), $type);
                break;
            case Opcode::INT2CHAR:
                /** @var string|bool $str*/
                $str = mb_chr($value1->getValue(), "UTF-8");

                if(is_bool($str) && $str == false)
                    { return "Provided integer value is not valid Unicode character"; }
                
                $valueResult->setValue($str, $type);
            case Opcode::STRI2INT:
                // store string from $value1 from index $value2
                $str = substr($value1->getValue(), $value2->getValue());

                $valueResult->setValue($str, $str, $type);
                break;
            default:
                throw new StudentExceptions("Internal error: Unexpected 
                \$opcode in performArithmeticInstruction()", 1); // TODO:
        }

        return null;
    }

        /**
     * Perform output instruction on given instruction
     *
     * @param Instruction $instruction instruction that will be performed
     * @param Interpreter $interpreter interpreter object
     * @return ?string returns null if no error occurred, returns error 
     * message (string) if error occurred
     */
    public static function Write(Instruction &$instruction, Interpreter &$interpreter) : ?string
    {
        $arg1 = $instruction->getArg(0);
        if($arg1 == null) { return "Argument 1 not found";} // TODO: err message

        /** @var Variable value2 */
        $value1 = null;

        // check if variable is declared, if not return error message
        $errMsg = InstructionExecuter::checkVariableList($interpreter, $arg1, InstructionExecuter::CHECK_DEFINED, $value1);        
        if($errMsg != null) { return $errMsg; }

        switch($value1->getType())
        {
            case Variable::INT:
            case Variable::STRING:
            case Variable::BOOL:
                $interpreter->print($value1->getValue());
            case Variable::NIL:
                $interpreter->print("");
                break;
        }
        
        return null;
    }

        /**
     * Perform move instruction on given instruction
     *
     * @param Instruction $instruction instruction that will be performed
     * @param Interpreter $interpreter interpreter object
     * @return ?string returns null if no error was found, returns error message 
     * if error was found
     */
    public static function Move(Instruction $instruction, Interpreter &$interpreter): ?string
    {            
        $argResult = $instruction->getArg(0);
        if($argResult == null) { return "Argument 1 not found";}

        $arg2 = $instruction->getArg(1);
        if($arg2 == null) { return "Argument 2 not found";}

        /** @var Variable valueResult */
        $valueResult = null;
        /** @var Variable value2 */
        $value2 = null;

        // check if variable is defined, if not return error message
        $errMsg = InstructionExecuter::checkVariableList($interpreter, $argResult, InstructionExecuter::CHECK_DECLARED, $valueResult);        
        if($errMsg != null) { return $errMsg; }

        // check if variable is defined, if not return error message
        $errMsg = InstructionExecuter::checkVariableList($interpreter, $arg2, InstructionExecuter::CHECK_DEFINED, $value2);        
        if($errMsg != null) { return $errMsg; }

        $valueResult->setValue($value2->getValue(), $value2->getType());

        return null;
    }

    /**
     * Check if variable is defined/declared (based on $check) and 
     * returns Variable element from global Variable array  
     * @param Interpreter $interpreter interpreter object
     * @param Argument $arg argument to be checked
     * @param ?Variable &$value output variable to hold found Variable in Variable list
     * @return ?string returns error message
     */
    private static function checkVariableList(Interpreter &$interpreter, Argument &$arg, int $check, ?Variable &$value) : ?string
    {
        if($arg->getType() == Argument::VAR)
        {
            /** @var array<string,string> arr */
            $arr = Argument::breakIntoNameAndScope($arg->getValue());

            // look up variable in the Variable array/list
            $variable = $interpreter->getVariable($arr["name"]);

            // check if argument of type variable is not in variable list
            if($variable == null)
            {
                $value = null;
                return "Variable with name \"" . $arr["name"] . "\" is not declared";
            }

             // if $check is check defined, check if defined 
            if($check == InstructionExecuter::CHECK_DEFINED)
            {
                if(!$variable->isDefined())
                {
                    $value = null;
                    return "Variable is not defined";
                }
                
                $value = $variable;
                return null;
            }
            else if($check == InstructionExecuter::CHECK_DECLARED)
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
            $interpreter->globalLiteral->setValue($arg->getValue(), $arg->getType()) ;
            $value = $interpreter->globalLiteral;
            return null;
        }
    }
}