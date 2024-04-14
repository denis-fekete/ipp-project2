<?php
namespace IPP\Student;

use IPP\Student\Variable;
use IPP\Student\Opcode;
use IPP\Student\Argument;
use DOMNodeList;
use DOMElement;
use DOMNode;

class Helper
{


     
    /**
     * Private constructor because this class should be "STATIC"
     *
     * @return void
     */
    private function __construct(){}

    
    /**
     * Check names
     *
     * @param DOMNodeList<DOMNode> $root
     * @param Interpreter $interpreter
     * @return bool returns true if no error occurred
     */
    public static function checkNodeNames (DOMNodeList $root, Interpreter $interpreter) : bool
    {
        /** @var bool $hasProgram*/
        $hasProgram = false;
        for($i = 0; $i < $root->count(); $i++)
        {
            $node = $root->item($i);
            if($node == null) break;
            
            if($node->nodeName == "program")
            {
                if($hasProgram == false)
                {
                    $hasProgram = true;
                }
                else
                {
                    $interpreter->errorHandler("Syntactic error: Multiple <program> elements", 32);
                    return false;
                }
            }
            else if($node->nodeName == "instruction") {}
            else
            {   
                $atPos = strpos($node->nodeName, "arg");
                if($atPos !== false)
                {
                        $prefix = substr($node->nodeName, 0, $atPos);
                        $suffix = substr($node->nodeName, $atPos + 3);

                        // $interpreter->println("suffix=" . $suffix . ", prefix=" . $prefix);
                        
                        // check if there is not string before "arg"
                        if($prefix != "")
                        {
                            $interpreter->errorHandler("Syntactic control: Bad child argument name : " . $node->nodeName, 32);
                            return false;
                        }
                        // check if suffix after "arg" are only numbers 
                        if(filter_var($suffix, FILTER_VALIDATE_INT) == false)
                        {
                            $interpreter->errorHandler("Syntactic control: Bad child argument name : "  . $node->nodeName, 32);
                            return false;
                        }
                }
                else
                {
                    $interpreter->errorHandler("Unknown element: " . $node->nodeName, 32);
                    return false;
                }
            }
        }

        return true;
    }


    /**
     * @param DOMNodeList<DOMNode> $instructionList
     * @return array<Instruction>
     */
    public static function convertToInstructions(DOMNodeList $instructionList, Interpreter $interpreter, int &$highestOrder): array
    {
        $arrayOfInstructions = [];
        $highestOrder = 0;
        foreach($instructionList as $inst)
        {
            if ($inst instanceof DOMElement) 
            {
                $opcode = $inst->getAttribute("opcode");
                $order = $inst->getAttribute("order");

                if(!Opcode::isOpcode($opcode))
                {
                    $interpreter->errorHandler("Bad XML: Unknown Opcode", 32);
                }

                foreach($arrayOfInstructions as $pastInstructions)
                {
                    if($pastInstructions->getOrder() == $order)
                    {
                        $interpreter->errorHandler("Bad XML: Duplicate instruction order", 32);
                    }
                }

                if($order > $highestOrder)
                {
                    $highestOrder = $order;
                }

                $convertedInt = intval($order);
                // check if converted integer is valid
                if($convertedInt == 0)
                {
                    $interpreter->errorHandler("Bad XML: Bad order value", 32);
                }
                else if($convertedInt < 0)
                {
                    $interpreter->errorHandler("Bad XML: Negative order of instruction", 32);
                }
                
                /** @var array<Argument> array of arguments*/
                $argsList = [];
                

                // check all childNodes (arguments) of instruction node, args must start from 1
                $argHighestOrder = 0;
                for($i = 1; $i < $inst->childNodes->length; $i++)
                {
                    // get argument name, it must be in format arg{NUMBER}
                    $argName = "arg" . strval($i);

                    // get list of arguments with arg{NUMBER} name
                    $arg = $inst->getElementsByTagName($argName);

                    // if there is no arguments break, no arguments provided in instruction
                    if($arg->length == 0)
                    {
                        break;
                    }
                    // check if element has exactly one argument with same number
                    else if($arg->length != 1)
                    {
                        $interpreter->errorHandler("More than one argument with same name (" . $i . ")\n", 32);
                    }
                    // delete spaces in argument value
                    $argName = str_replace(" ", "", $arg[0]->nodeValue);
                    $argName = str_replace("\n", "", $argName);
                    $argName = str_replace("\t", "", $argName);

                    if(gettype($argName) == "array")
                        $argName = $argName[0];

                    // add new Argument to the argsList
                    $argsList[] = new Argument($i, $arg[0]->getAttribute("type"), $argName);
                    $argHighestOrder = $i;
                }
                
                Helper::sortMyLists($argsList, $argHighestOrder);

                // if length is zero, set argsList to null 
                if(count($argsList) == 0)
                {
                    $argsList = null;
                }
                // add instruction to the array of instructions with its opcode, order and arguments
                $arrayOfInstructions[] = new Instruction(strtoupper($opcode), $convertedInt, $argsList);
            }
        }
        
        return $arrayOfInstructions;
    }

        
    /**
     * Checks if Variables has expected / correct type that should be 
     *
     * @param string $opcode operation that will be performed
     * @param ?Variable $var1 first variable 
     * @param ?Variable $var2 second variable
     * @param ?Argument $arg1 first argument
     * @param ?Argument $arg2 second argument
     * @param ?string $finalType final type of the expression
     * @return ?string Returns null if no error occurred, returns error message if error occurred 
     */
    public static function checkVariableType(string $opcode, ?Variable $var1, ?Argument $arg1, ?Variable $var2, ?Argument $arg2, ?string &$finalType) : ?string
    {
        switch($opcode)
        {
            case Opcode::ADD:
            case Opcode::SUB:
            case Opcode::SUB:
            case Opcode::MUL:
            case Opcode::IDIV:
                if($var1 != null && $var1->getType() != Variable::INT)
                {
                    return "Expected type in first argument: Variable::INT" . ", got: " . $var1->getType();
                }
                if($var2 != null && $var2->getType() != Variable::INT)
                {
                    return "Expected type in second argument: Variable::INT" . ", got: " . $var2->getType();
                }
                $finalType = Variable::INT;
                break;
            case Opcode::CONCAT:
                if($var1 != null && $var1->getType() != Variable::STRING)
                {
                    return "Expected type in first argument: Variable::STRING" . ", got: " . $var1->getType();
                }
                if($var2 != null && $var2->getType() != Variable::STRING)
                {
                    return "Expected type in second argument: Variable::STRING" . ", got: " . $var2->getType();
                }
                $finalType = Variable::STRING;
                break;
            case Opcode::GETCHAR:
                if($var1 != null && $var1->getType() != Variable::STRING)
                {
                    return "Expected type in first argument: Variable::STRING" . ", got: " . $var1->getType();
                }
                if($var2 != null && $var2->getType() != Variable::INT)
                {
                    return "Expected type in second argument: Variable::INT" . ", got: " . $var2->getType();
                }
                $finalType = Variable::STRING;
                break;
            case Opcode::SETCHAR:
                if($var1 != null && $var1->getType() != Variable::INT)
                {
                    return "Expected type in first argument: Variable::INT" . ", got: " . $var1->getType();
                }
                if($var2 != null && $var2->getType() != Variable::STRING)
                {
                    return "Expected type in second argument: Variable::STRING" . ", got: " . $var2->getType();
                }
                $finalType = Variable::STRING;
                break;
            case Opcode::opAND:
            case Opcode::opOR:
                if($var2 != null && $var2->getType() != Variable::BOOL)
                {
                    return "Expected type in second argument: Variable::BOOL" . ", got: " . $var2->getType();
                }
            case Opcode::opNOT:
                if($var1 != null && $var1->getType() != Variable::BOOL)
                {
                    return "Expected type in first argument: Variable::BOOL" . ", got: " . $var1->getType();
                }
                $finalType = Variable::BOOL;
                break;
            case Opcode::LT:
            case Opcode::GT:
            case Opcode::EQ:
            case Opcode::JUMPIFEQ:
            case Opcode::JUMPIFNEQ:
                $finalType = Variable::BOOL;
                if($var1 != null && $var2 != null)
                {
                    if($var1->getType() != $var2->getType())
                    {
                        if($arg1 == null || $arg2 == null)
                        {
                            return "Internal error: Arg1 or Arg2 is null";
                        }
                        else if(Argument::isLiteral($arg1->getType()))
                        {
                            if($arg1->getType() == $var2->getType())
                                return null;
                            else if($arg1->getType() == Argument::LITERAL_NIL && $var2->getType() == Variable::INT)
                                return null;
                        }                         
                        else if(Argument::isLiteral($arg2->getType()))
                        {
                            if($arg2->getType() == $var1->getType())
                                return null;
                            else if($arg2->getType() == Argument::LITERAL_NIL && $var1->getType() == Variable::INT)
                                return null;
                        }
                        return "Semantic error: Unknown combination of operands/types";
                    }
                    else
                    {
                        return null;
                    }
                }
                break;
            case Opcode::STRLEN:
                if($var1 != null && $var1->getType() != Variable::STRING)
                {
                    return "Expected type in first argument: Variable::STRING" . ", got: " . $var1->getType();
                }
                $finalType = Variable::INT;
                break;
            case Opcode::INT2CHAR:
                if($var1 != null && $var1->getType() != Variable::INT)
                {
                    return "Expected type in first argument: Variable::INT" . ", got: " . $var1->getType();
                }
                $finalType = Variable::STRING;
                break;
            case Opcode::STRI2INT:
                if($var1 != null && $var1->getType() != Variable::STRING)
                {
                    return "Expected type in first argument: Variable::STRING" . ", got: " . $var1->getType();
                }
                if($var2 != null && $var2->getType() != Variable::INT)
                {
                    return "Expected type in second argument: Variable::INT" . ", got: " . $var2->getType();
                }
                $finalType = Variable::INT;
                break;
            case Opcode::EXIT:
                if($var1 != null && $var1->getType() != Variable::INT)
                {
                    return "Expected type in first argument: Variable::INT" . ", got: " . $var1->getType();
                }
                break;
            default:
                throw new StudentExceptions("Internal error: Unexpected " .
                "\$opcode in checkVariableType(): " . $opcode, 1);
        }

        return null;
    }

    /**
     * Sorts instruction list by Instruction->order integer value
     *
     * @param array<Instruction>|array<Argument> $list list of instructions 
     * @param int $highestOrder value with highers found order 
     */
    public static function sortMyLists(array &$list, int $highestOrder) : void
    {
        $orderedInst = [];
        for($index = 0; $index <= $highestOrder; $index++)
        {
            foreach($list as $element)
            {
                if($index == $element->getOrder())
                {
                    $orderedInst[] = $element;
                }
            }
        }

        // return $orderedInst;
        $list = $orderedInst;
    }
    
    public static function isValidUnicode(int $input) : bool
    {
        return ($input >= 0 && $input <= 0x10FFFF);
    }
    
    /**
     * convertToUnicode
     *
     * @param int $input
     * @return mixed
     */
    public static function convertToUnicode(int $input) : mixed
    {
        if (Helper::isValidUnicode($input)) {
            return json_decode('"\u' . sprintf('%04x', $input) . '"');
        } else {
            return null;
        }
    }

}