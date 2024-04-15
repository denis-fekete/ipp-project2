<?php
namespace IPP\Student;

use IPP\Student\Instruction;
use IPP\Student\Variable;
use IPP\Student\Argument;
use IPP\Student\Interpreter;

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
            $interpreter->errorHandler("Syntactic error: Missing argument 1", 32);
            return InstExec::ERR;
        }
        $arg1 = $instruction->getArg(1);
        if($arg1 == null) 
        { 
            $interpreter->errorHandler("Syntactic error: Missing argument 2", 32);
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
            $interpreter->errorHandler("Syntactic error: Second argument is not type", 32);
            return InstExec::ERR;
        }

        $val = $interpreter->read($arg1->getValue());

        // if nothing was read, set nil
        if($val == null)
        { 
            $valueResult->setValue(null, Variable::NIL);
        }
        else
        {
            // this arg1 contains type of value in its value
            $valueResult->setValue($val, $arg1->getValue());
        }

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
            $interpreter->errorHandler("Syntactic error: Missing argument 1", 32);
            return InstExec::ERR;
        }
    
        if($arg1->getType() != Argument::VAR)
        {
            $interpreter->errorHandler("Semantic error: Bad argument type (" . $arg1->getType() . ")", 32);
            return InstExec::ERR;
        }
        /** @var array<string,string> arr */
        $arr = Argument::breakIntoNameAndScope($arg1->getValue());

        $found = $interpreter->getVariable($arr["name"], $arr["scope"]);
        if($found !== null)
        {
            $interpreter->errorHandler("Semantic error: redefinition of variable :" . $arr["name"], 52);
        }

        // adds new variable into an variable list
        $interpreter->addVariable(new Variable($arr["name"]), $arr["scope"]);

        return InstExec::NO_ERR;
    }
        
    /**
     * Perform return instruction
     *
     * @param Interpreter $interpreter interpreter object
     * @return bool
     */
    public static function Return(Interpreter &$interpreter) : bool
    {
        $returnTo = $interpreter->popInstructionCounter();
        if($returnTo === null)
        {
            $interpreter->errorHandler("Semantic error: trying to pop from empty instruction counter", 56);
            return InstExec::ERR;
        }
        
        $interpreter->changeInstructionCounter($returnTo);
        return InstExec::NO_ERR;
    }

    /**
     * Perform jump instruction
     *
     * @param Instruction $instruction instruction that will be performed
     * @param Interpreter $interpreter interpreter object
     * @param string $opcode opcode of instruction 
     * @return bool
     */
    public static function Jump(Instruction &$instruction, Interpreter &$interpreter, string &$opcode) : bool
    {   
        $argLabel = $instruction->getArg(0);
        if($argLabel == null)
        { 
            $interpreter->errorHandler("Syntactic error: Missing argument 1", 32);
            return InstExec::ERR;
        }

        $arg1 = null;
        $arg2 = null;
        if($opcode != Opcode::JUMP && $opcode != Opcode::CALL)
        {
            $arg1 = $instruction->getArg(1);
            if($arg1 == null)
            { 
                $interpreter->errorHandler("Syntactic error: Missing argument 2", 32);
                return InstExec::ERR;
            }

            $arg2 = $instruction->getArg(2);
            if($arg2 == null)
            {
                $interpreter->errorHandler("Syntactic error: Missing argument 3", 32);
                return InstExec::ERR;
            }
        }

        /** @var Label value1 */
        $label = null;
        /** @var Variable value1 */
        $value1 = null;
        /** @var Variable value2 */
        $value2 = null;

        $label = $interpreter->getLabel($argLabel->getValue());
        if($label == null)
        {
            $interpreter->errorHandler("Semantic control: Using undefined Label:" . $argLabel->getValue(), 52);
            return InstExec::ERR;
        }

        if($opcode != Opcode::JUMP && $opcode != Opcode::CALL)
        {
            // check if variable is defined, if not return error message
            if(InstExec::checkVariableList($interpreter, $arg1, InstExec::CHECK_DEFINED, $value1))
            { return InstExec::ERR; }
                
            // check if variable is declared, if not return error message
            if(InstExec::checkVariableList($interpreter, $arg2, InstExec::CHECK_DEFINED, $value2))
            { return InstExec::ERR; }

            $type = null;
            // check variable types based on $opcode, return type in $type variable
            $errMsg = Helper::checkVariableType($opcode, $value1, $arg1, $value2, $arg2, $type);
            if($errMsg != null)
            { 
                $interpreter->errorHandler($errMsg, 53);
                return InstExec::ERR;
            }
        }

        switch($opcode)
        {
            case Opcode::CALL:
                $interpreter->pushInstructionCounter();
                $interpreter->changeInstructionCounter($label->getInstructionIndex());
                break;
            case Opcode::JUMP:
                $interpreter->changeInstructionCounter($label->getInstructionIndex());
                break;
            case Opcode::JUMPIFEQ:
                if($value1->getValue() == $value2->getValue())
                {
                    $interpreter->changeInstructionCounter($label->getInstructionIndex());
                }
                break;
            case Opcode::JUMPIFNEQ:
                if($value1->getValue() != $value2->getValue())
                {
                    $interpreter->changeInstructionCounter($label->getInstructionIndex());
                }
                break;
            default:
                $interpreter->errorHandler("Internal error: Unexpected \$opcode in Jump(): " . $opcode, 99);
                return InstExec::ERR;
        }

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
    public static function ArithmeticOrString(Instruction &$instruction, Interpreter &$interpreter, string &$opcode) : bool
    {   
        $argResult = $instruction->getArg(0);
        if($argResult == null)
        { 
            $interpreter->errorHandler("Syntactic error: Missing argument 1", 32);
            return InstExec::ERR;
        }

        $arg1 = $instruction->getArg(1);
        if($arg1 == null)
        { 
            $interpreter->errorHandler("Syntactic error: Missing argument 2", 32);
            return InstExec::ERR;
        }

        $arg2 = null;
        // if operation is NOT or INT2CHAR no need for 2nd value/argument
        if($opcode != Opcode::opNOT && $opcode != Opcode::INT2CHAR && $opcode != Opcode::STRLEN)
        {
            $arg2 = $instruction->getArg(2);
            if($arg2 == null)
            {
                $interpreter->errorHandler("Syntactic error: Missing argument 3", 32);
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
        if(! ($opcode == Opcode::opNOT || $opcode == Opcode::INT2CHAR || $opcode == Opcode::STRLEN))
        {
            // check if variable is defined, if not return error message
            if(InstExec::checkVariableList($interpreter, $arg2, InstExec::CHECK_DEFINED, $value2))
            { return InstExec::ERR; }
        }
        
        if($opcode == Opcode::SETCHAR)
        {
            // check if variable is declared, if not return error message
            if(InstExec::checkVariableList($interpreter, $argResult, InstExec::CHECK_DEFINED, $valueResult))
            { return InstExec::ERR; }

            if($valueResult == null || $valueResult->getType() != Variable::STRING)
            {
                $interpreter->errorHandler("Semantic error: First argument is not declared or is not of type string", 58);
                return InstExec::ERR;
            }
        }
        else
        {
            // check if variable is declared, if not return error message
            if(InstExec::checkVariableList($interpreter, $argResult, InstExec::CHECK_DECLARED, $valueResult))
            { return InstExec::ERR; }
        }

        $nilFound = false;
        if($value1 != null && $value1->getType() == Variable::NIL)
            $nilFound = true;
        if($value2 != null && $value2->getType() == Variable::NIL)
            $nilFound = true;
        if($arg1 != null && $arg1->getType() == Argument::LITERAL_NIL)
            $nilFound = true;
        if($arg2 != null && $arg2->getType() == Argument::LITERAL_NIL)
            $nilFound = true;
        if($nilFound == true)
        {
            if($opcode == Opcode::EQ)
            {
                if($value1->getType() != Variable::NIL)
                    $valueResult->setValue(false, Variable::BOOL);
                else if($value2->getType() != Variable::NIL)
                    $valueResult->setValue(false, Variable::BOOL);
                else // both are nill
                    $valueResult->setValue(true, Variable::BOOL);

                return InstExec::NO_ERR;
            }
            else
            {
                $interpreter->errorHandler("Semantic control: nil is not allowed in this type of expression", 53);
                return InstExec::ERR;
            }
            
        }

        $type = null;
        // check variable types based on $opcode, return type in $type variable
        $errMsg = Helper::checkVariableType($opcode, $value1, $arg1, $value2, $arg2, $type);
        if($errMsg != null)
        { 
            $interpreter->errorHandler($errMsg, 53);
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
            case Opcode::EQ:
                $valueResult->setValue($value1->getValue() == $value2->getValue(), $type);
                break;
            case Opcode::opAND:
                $valueResult->setValue($value1->getValue() && $value2->getValue(), $type);
                break;
            case Opcode::opOR:
                $valueResult->setValue($value1->getValue() || $value2->getValue(), $type);
                break;
            case Opcode::CONCAT:
                $valueResult->setValue($value1->getValue() . $value2->getValue(), $type);
                break;
            case Opcode::opNOT:
                $valueResult->setValue(!$value1->getValue(), $type);
                break;
            case Opcode::STRLEN:
                $valueResult->setValue(strlen($value1->getValue()), $type);
                break;
            case Opcode::GETCHAR:
                if(strlen($value1->getValue()) <= $value2->getValue())
                {
                    $interpreter->errorHandler("Semantic error: Index out of bounds of the given string", 58);
                }
                $char = substr($value1->getValue(), $value2->getValue(), 1);
                $valueResult->setValue($char, $type);
                break;
            case Opcode::SETCHAR:
                if(strlen($valueResult->getValue()) <= $value1->getValue())
                {
                    $interpreter->errorHandler("Semantic error: Index out of bounds of the given string", 58);
                }
                if(strlen($value2->getValue()) <= 0)
                {
                    $interpreter->errorHandler("Semantic error: Given string cannot be empty", 58);
                }

                $oldString = $valueResult->getValue();
                $prefix = substr($oldString, 0, $value1->getValue());
                $suffix = substr($oldString, $value1->getValue() + 1);
                $newChar = substr($value2->getValue(), 0, 1);
                $valueResult->setValue($prefix . $newChar . $suffix, $type);
                break;
            case Opcode::INT2CHAR:
                /** @var ?string $str*/
                $str = Helper::convertToUnicode($value1->getValue());

                if($str === null)
                { 
                    $interpreter->errorHandler("Syntactic error: Provided integer 
                        value is not valid Unicode character", 58);
                    return InstExec::ERR;
                }
                else
                {
                    $valueResult->setValue($str, $type);
                }
                break;
            case Opcode::STRI2INT:
                if($value2->getType() != Variable::INT)
                {
                    $interpreter->errorHandler("Semantic error: Provided argument is not of type int", 53);
                    return InstExec::ERR;
                }
                if(strlen($value1->getValue()) <= $value2->getValue())
                {
                    $interpreter->errorHandler("Semantic error: Index out of bounds of the given string", 58);
                }

                // store string from $value1 from index $value2
                $char = substr($value1->getValue(), $value2->getValue(), 1);
                $valueResult->setValue(ord($char), $type);
                break;
            default:
                $interpreter->errorHandler("Internal error: Unexpected \$opcode in Arithmetic(): " . $opcode, 99);
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
    public static function Write(Instruction &$instruction, Interpreter &$interpreter, string $opcode) : bool
    {
        $arg1 = $instruction->getArg(0);
        if($arg1 == null)
        { 
            $interpreter->errorHandler("Syntactic error: Missing argument 1", 32);
            return InstExec::ERR;
        }

        /** @var Variable value2 */
        $value1 = null;

        // check if variable is declared, if not return error message
        if(InstExec::checkVariableList($interpreter, $arg1, InstExec::CHECK_DEFINED, $value1))
        { return InstExec::ERR; }

        $valueToPrint = "";
        switch($value1->getType())
        {
            case Variable::STRING:
                $valueToPrint = $value1->getValue();

                // find escape sequences and change them into character representation
                $index = strpos($valueToPrint, "\\");
                while(!($index === false))
                {
                    $prefix = substr($valueToPrint, 0, $index);
                    $escapeSec = substr($valueToPrint, $index + 1, 3);
                    $suffix = substr($valueToPrint, $index + 4);

                    $integerEscSec = intval($escapeSec);

                    if($integerEscSec == 0 && $escapeSec != "000")
                    {
                        $interpreter->errorHandler("Unknown escape sequence", 32);
                        return InstExec::ERR;
                    }
                    else if (! (($integerEscSec > 0 && $integerEscSec <= 32) || $integerEscSec == 35 || $integerEscSec == 92) )
                    {
                        $interpreter->errorHandler("Unknown escape sequence", 32);
                        return InstExec::ERR;
                    }

                    $valueToPrint = $prefix . chr($integerEscSec) . $suffix;

                    $index = strpos($valueToPrint, "\\");
                }
                
                break;
            case Variable::TYPE:
            case Variable::INT:
                $valueToPrint = $value1->getValue();
                break;
            case Variable::BOOL:
                $valueToPrint = $value1->getValue();
                if($valueToPrint == true)
                    $valueToPrint = "true";
                else if($valueToPrint == false)
                    $valueToPrint = "false";
                break;
            case Variable::NIL:
                break;
        }

        if($opcode == Opcode::WRITE)
            $interpreter->print($valueToPrint);
        else
            $interpreter->printErr($valueToPrint);
        $interpreter->print("");

        return InstExec::NO_ERR;
    }

    public static function Exit(Instruction $instruction, Interpreter &$interpreter): bool
    {
        $arg1 = $instruction->getArg(0);
        if($arg1 == null)
        {
            $interpreter->errorHandler("Syntactic error: Missing argument 1", 32);
            return InstExec::ERR;
        }

        /** @var Variable value1 */
        $value1 = null;

        // check if variable is defined, if not return error message
        if(InstExec::checkVariableList($interpreter, $arg1, InstExec::CHECK_DEFINED, $value1))
        { return InstExec::ERR; }

        $dummy = null;
        $dummy = Helper::checkVariableType(Opcode::EXIT, $value1, null, null, null, $dummy);
        if($dummy != null || $value1->getType() != Variable::INT)
        {
            $interpreter->errorHandler("Semantic error: Invalid value for EXIT", 53);
        }
        if(! ($value1->getValue() >= 0 && $value1->getValue() <= 9))
        {
            $interpreter->errorHandler("Semantic error: Invalid integer value for EXIT", 57);
        }
        
        exit(intval($value1->getValue()));
    }

    /**
     * Perform move instruction on given instruction
     *
     * @param Instruction $instruction instruction that will be performed
     * @param Interpreter $interpreter interpreter object
     * @return bool
     */
    public static function VariableStack(Instruction $instruction, Interpreter &$interpreter, string $opcode): bool
    {            
        $arg1= $instruction->getArg(0);
        if($arg1 == null)
        {
            $interpreter->errorHandler("Syntactic error: Missing argument 1", 32);
            return InstExec::ERR;
        }

        /** @var Variable valueResult */
        $value1 = null;

        switch($opcode)
        {
            case Opcode::PUSHS:
                // check if variable is defined, if not return error message
                if(InstExec::checkVariableList($interpreter, $arg1, InstExec::CHECK_DEFINED, $value1))
                { return InstExec::ERR; }

                $newToBePushed = new Variable($value1->getName());
                $newToBePushed->setValue($value1->getValue(), $value1->getType());
                $interpreter->pushVariableStack($newToBePushed);
                break;
            case Opcode::POPS:
                // check if variable is defined, if not return error message
                if(InstExec::checkVariableList($interpreter, $arg1, InstExec::CHECK_DECLARED, $value1))
                { return InstExec::ERR; }

                $returnedValue = $interpreter->popVariableStack();
                if($returnedValue == null)
                {
                    $interpreter->errorHandler("Semantic error: trying to pop from empty variable stack", 56);
                    return InstExec::ERR;
                }
                $value1->setValue($returnedValue->getValue(), $returnedValue->getType());
                break;
        }

        return InstExec::NO_ERR;
    }

    /**
     * Perform move instruction on given instruction
     *
     * @param Instruction $instruction instruction that will be performed
     * @param Interpreter $interpreter interpreter object
     * @return void
     */
    public static function Break(Instruction $instruction, Interpreter &$interpreter, ?Instruction $last, ?Instruction $next): void
    {
        $interpreter->printErr("\n------------------------------\n");
        $interpreter->printErr("Debug info:");
        $interpreter->printErr("\n------------------------------\n");
        $interpreter->printErr("Number of executed instructions: " . $interpreter->numberOfInstructions);
        $interpreter->printErr("\n------------------------------\n");

        if($last != null)
        {
            $interpreter->printErr("Last instruction:\n");
            $interpreter->printErr($last->toString());
            $interpreter->printErr("\n------------------------------\n");
        }
        if($next != null)
        {
            $interpreter->printErr("Next instruction:\n");
            $interpreter->printErr($next->toString());
        }


        $interpreter->printErr("\n------------------------------\n");
        $interpreter->printFrameStack();
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
     * Perform type instruction
     *
     * @param Instruction $instruction instruction that will be performed
     * @param Interpreter $interpreter interpreter object
     * @return bool
     */
    public static function Type(Instruction $instruction, Interpreter &$interpreter): bool
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
        if(InstExec::checkVariableList($interpreter, $arg2, InstExec::CHECK_DECLARED, $value2))
        { return InstExec::ERR; }

        $valueResult->setValue($value2->getType(), Variable::STRING);

        return InstExec::NO_ERR;
    }

    /**
     * Perform Label instruction
     *
     * @param Instruction $instruction instruction that will be performed
     * @param Interpreter $interpreter interpreter object
     * @param int $index index of current instruction
     * @return bool
     */
    public static function Label(Instruction $instruction, Interpreter &$interpreter, int $index): bool
    {
        $arg1 = $instruction->getArg(0);
        if($arg1 == null)
        {
            $interpreter->errorHandler("Syntactic error: Missing argument 1", 32);
            return InstExec::ERR;
        }

        // check if label doesn't exists already
        /** @var ?Label $label */
        $label = $interpreter->getLabel($arg1->getValue());
        if($label != null)
        {
            if($label->getInstructionIndex() != $index)
            {
                $interpreter->errorHandler("Semantic error: Label already defined", 52);
            }
        } 
    
        // add new label to the label list
        $interpreter->addLabel(new Label($arg1->getValue(), $index));

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
            $variable = $interpreter->getVariable($arr["name"], $arr["scope"]);
            // check if argument of type variable is not in variable list
            if($variable == null)
            {
                $interpreter->errorHandler("Semantic error: Variable with name \"" . 
                    $arr["name"] . "\" is not declared", 54);

                return InstExec::ERR;
            }

             // if $check is check defined, check if defined 
            if($check == InstExec::CHECK_DEFINED)
            {
                if(!$variable->isDefined())
                {
                    $interpreter->errorHandler("Semantic error: Variable with " .
                    "name \"" . $arr["name"] . "\" is not defined", 54);
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
                     \"" . $arr["name"] . "\" is not defined", 54);
                return InstExec::ERR;
            }
        }
        else
        {
            // update global literal and pass it as value
            $value = new Variable("");
            switch($arg->getType())
            {
                case Argument::LITERAL_BOOL:
                    if($arg->getValue() == "true")
                        $value->setValue(true, $arg->getType());
                    else
                        $value->setValue(false, $arg->getType());
                    break;
                case Argument::LITERAL_INT:
                case Argument::LITERAL_FLOAT:
                    $value->setValue(strval($arg->getValue()), $arg->getType());
                    break;
                case Argument::LITERAL_STRING:
                    $value->setValue($arg->getValue(), $arg->getType());
                    break;
                case Argument::LITERAL_NIL:
                    $value->setValue("", $arg->getType());
                    break;
                default:
                    $interpreter->errorHandler("Internal type: Unknown data type in argument", 54);
                    break;
            }
            // $value->setValue($arg->getValue(), $arg->getType());
        }

        return InstExec::NO_ERR;
    }
    
    
}