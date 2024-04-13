<?php
namespace IPP\Student;

use IPP\Student\Instruction;
use IPP\Student\Variable;
use IPP\Student\Argument;
use IPP\Student\Interpreter;


class InstructionExecuter extends InstExec {};

class InstExec
{
    private const int CHECK_DECLARED = 0;
    private const int CHECK_DEFINED = 1;

    private const bool NO_ERR = false;
    private const bool ERR = true;


    /**
     * Perform input instruction on given instruction
     *
     * @param Instruction $instruction instruction that will be performed
     * @param Interpreter $interpreter interpreter object
     * @return bool
     */
    public static function Read(Instruction &$instruction, Interpreter &$interpreter) : bool
    {
        $argResult = $instruction->getArg(0);
        if($argResult == null)
        { 
            $interpreter->errorHandler("Syntactic error: Missing argument 1", 32); //TODO:
            return InstExec::ERR;
        }
        $arg1 = $instruction->getArg(1);
        if($arg1 == null) 
        { 
            $interpreter->errorHandler("Syntactic error: Missing argument 2", 32); //TODO:
            return InstExec::ERR;
        }

        /** @var Variable result */
        $valueResult = null;

        // check if variable is declared, if not return error message
        if(InstExec::checkVariableList($interpreter, $argResult, InstExec::CHECK_DECLARED, $valueResult))
        { return InstExec::ERR; }

        $val = null;
        // check if value1 is type
        if($arg1->getType() != Variable::TYPE)
        {
            $interpreter->errorHandler("Syntactic error: Second argument is not type", 32); //TODO:
            return InstExec::ERR;
        }

        $val = $interpreter->read($arg1->getValue());

        if($val == null)
        { 
            $interpreter->errorHandler("Internal error: Reading from standard output failed", 99); //TODO:
            return InstExec::ERR;
        } // TODO:
        
        $valueResult->setValue($val, strtoupper($arg1->getValue()));

        return InstExec::NO_ERR;
    }

    /**
     * Perform defvar instruction on given instruction
     *
     * @param Instruction $instruction instruction that will be performed
     * @param Interpreter $interpreter interpreter object
     * @return bool
     */
    public static function Defvar(Instruction &$instruction, Interpreter &$interpreter) : bool
    {
        $arg1 = $instruction->getArg(0);

        if($arg1 == null)
        {
            $interpreter->errorHandler("Syntactic error: Missing argument 1", 32); //TODO:
            return InstExec::ERR;
        }
    
        if($arg1->getType() != Argument::VAR)
        {
            $interpreter->errorHandler("Semantic error: Bad argument type (" . $arg1->getType() . ")", 31); //TODO:
            return InstExec::ERR;
        }
        /** @var array<string,string> arr */
        $arr = Argument::breakIntoNameAndScope($arg1->getValue());

        // adds new variable into an variable list
        $interpreter->add2Variables(new Variable($arr["name"], $arr["scope"]));

        return InstExec::NO_ERR;
    }
    

    /**
     * Perform arithmetic instruction on given instruction
     *
     * @param Instruction $instruction instruction that will be performed
     * @param Interpreter $interpreter interpreter object
     * @param string $opcode opcode of instruction 
     * @return bool
     */
    public static function Arithmetic(Instruction &$instruction, Interpreter &$interpreter, string &$opcode) : bool
    {   
        $argResult = $instruction->getArg(0);
        if($argResult == null)
        { 
            $interpreter->errorHandler("Syntactic error: Missing argument 1", 32); //TODO:
            return InstExec::ERR;
        }

        $arg1 = $instruction->getArg(1);
        if($arg1 == null)
        { 
            $interpreter->errorHandler("Syntactic error: Missing argument 2", 32); //TODO:
            return InstExec::ERR;
        }

        $arg2 = null;
        // if operation is NOT or INT2CHAR no need for 2nd value/argument
        if($opcode != Opcode::opNOT && $opcode != Opcode::INT2CHAR)
        {
            $arg2 = $instruction->getArg(2);
            if($arg2 == null)
            {
                $interpreter->errorHandler("Syntactic error: Missing argument 3", 32); //TODO:
                return InstExec::ERR;
            }
        }

        /** @var Variable value1 */
        $value1 = null;
        /** @var Variable value2 */
        $value2 = null;
        /** @var Variable result */
        $valueResult = null;

        // check if variable is defined, if not return error message
        if(InstExec::checkVariableList($interpreter, $arg1, InstExec::CHECK_DEFINED, $value1))    
        { return InstExec::ERR; }

        // if operation is NOT or INT2CHAR no need for 2nd value/argument
        if(! ($opcode == Opcode::opNOT || $opcode == Opcode::INT2CHAR))
        {
            // check if variable is defined, if not return error message
            if(InstExec::checkVariableList($interpreter, $arg2, InstExec::CHECK_DEFINED, $value2))
            { return InstExec::ERR; }
        }
            
        // check if variable is declared, if not return error message
        if(InstExec::checkVariableList($interpreter, $argResult, InstExec::CHECK_DECLARED, $valueResult))
        { return InstExec::ERR; }

        $type = null;
        // check variable types based on $opcode, return type in $type variable
        $errMsg = Helper::checkVariableType($opcode, $value1, $value2, $type);
        if($errMsg != null)
        { 
            $interpreter->errorHandler($errMsg, 53); //TODO:
            return InstExec::ERR;
        }

        switch($opcode)
        {
            case Opcode::ADD:
                $valueResult->setValue($value1->getValue() + $value2->getValue(), $type);
                break;
            case Opcode::SUB:
                $valueResult->setValue($value1->getValue() - $value2->getValue(), $type);
                break;
            case Opcode::MUL:
                $valueResult->setValue($value1->getValue() * $value2->getValue(), $type);
                break;
            case Opcode::IDIV:
                if($value2->getValue() == 0)
                {
                    $interpreter->errorHandler("Semantic error: Zero division", 57);
                    return InstExec::ERR;
                }
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
                { 
                    $interpreter->errorHandler("Syntactic error: Provided integer 
                        value is not valid Unicode character", 53); // TODO:
                    return InstExec::ERR;
                }
                
                $valueResult->setValue($str, $type);
            case Opcode::STRI2INT:
                // store string from $value1 from index $value2
                if(!is_int($value2->getValue()))
                {
                    $interpreter->errorHandler("Semantic error: Provided argument is not of type int", 53); // TODO:
                    return InstExec::ERR;
                }

                $str = substr($value1->getValue(), $value2->getValue());
                $valueResult->setValue($str, $str, $type);
                break;
            default:
                $interpreter->errorHandler("Internal error: Unexpected 
                    \$opcode in Arithmetic(): " . $opcode, 99); // TODO:
                return InstExec::ERR;
        }

        return InstExec::NO_ERR;
    }

        /**
     * Perform output instruction on given instruction
     *
     * @param Instruction $instruction instruction that will be performed
     * @param Interpreter $interpreter interpreter object
     * @return bool
     */
    public static function Write(Instruction &$instruction, Interpreter &$interpreter) : bool
    {
        $arg1 = $instruction->getArg(0);
        if($arg1 == null)
        { 
            $interpreter->errorHandler("Syntactic error: Missing argument 1", 32); //TODO:
            return InstExec::ERR;
        }

        /** @var Variable value2 */
        $value1 = null;

        // check if variable is declared, if not return error message
        if(InstExec::checkVariableList($interpreter, $arg1, InstExec::CHECK_DEFINED, $value1))
        { return InstExec::ERR; }

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

        return InstExec::NO_ERR;
    }

        /**
     * Perform move instruction on given instruction
     *
     * @param Instruction $instruction instruction that will be performed
     * @param Interpreter $interpreter interpreter object
     * @return bool
     */
    public static function Move(Instruction $instruction, Interpreter &$interpreter): bool
    {            
        $argResult = $instruction->getArg(0);
        if($argResult == null)
        {
            $interpreter->errorHandler("Syntactic error: Missing argument 1", 32);
            return InstExec::ERR;
        }

        $arg2 = $instruction->getArg(1);
        if($arg2 == null)
        {
            $interpreter->errorHandler("Syntactic error: Missing argument 2", 32);
            return InstExec::ERR;
        }
        /** @var Variable valueResult */
        $valueResult = null;
        /** @var Variable value2 */
        $value2 = null;

        // check if variable is defined, if not return error message
        if(InstExec::checkVariableList($interpreter, $argResult, InstExec::CHECK_DECLARED, $valueResult))
        { return InstExec::ERR; }

        // check if variable is defined, if not return error message
        if(InstExec::checkVariableList($interpreter, $arg2, InstExec::CHECK_DEFINED, $value2))
        { return InstExec::ERR; }

        $valueResult->setValue($value2->getValue(), $value2->getType());

        return InstExec::NO_ERR;
    }

    /**
     * Check if variable is defined/declared (based on $check) and 
     * returns Variable element from global Variable array  
     * @param Interpreter $interpreter interpreter object
     * @param Argument $arg argument to be checked
     * @param ?Variable &$value output variable to hold found Variable in Variable list
     * @return bool
     */
    private static function checkVariableList(Interpreter &$interpreter, Argument &$arg, int $check, ?Variable &$value) : bool
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
                $interpreter->errorHandler("Semantic error: Variable with name
                    \"" . $arr["name"] . "\" is not declared", 54); //TODO:
                return InstExec::ERR;
            }

             // if $check is check defined, check if defined 
            if($check == InstExec::CHECK_DEFINED)
            {
                if(!$variable->isDefined())
                {
                    $interpreter->errorHandler("Semantic error: Variable with name
                        \"" . $arr["name"] . "\" is not defined", 54); //TODO:
                    return InstExec::ERR;
                }
                
                $value = $variable;
            }
            else if($check == InstExec::CHECK_DECLARED)
            {
                $value = $variable;
            }
            else
            {
                $interpreter->errorHandler("Semantic error: Variable with name
                     \"" . $arr["name"] . "\" is not defined", 54); //TODO:
                return InstExec::ERR;
            }
        }
        else
        {
            // update global literal and pass it as value
            $interpreter->globalLiteral->setValue($arg->getValue(), $arg->getType()) ;
            $value = $interpreter->globalLiteral;
        }

        return InstExec::NO_ERR;
    }

    
}