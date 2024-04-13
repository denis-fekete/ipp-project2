<?php
namespace IPP\Student;

use IPP\Student\Variable;
use IPP\Student\Opcode;

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
    public static function convertToInstructions(DOMNodeList $instructionList, Interpreter $interpreter): array
    {
        $arrayOfInstructions = [];

        foreach($instructionList as $inst)
        {
            if ($inst instanceof DOMElement) 
            {
                $opcode = $inst->getAttribute("opcode");
                $order = $inst->getAttribute("order");

                foreach($arrayOfInstructions as $pastInstructions)
                {
                    if($pastInstructions->getOrder() == $order)
                    {
                        $interpreter->errorHandler("Syntactic control: Duplicate instruction order", 32);
                    }
                }
                
                $convertedInt = intval($order);
                // check if converted integer is valid
                if($convertedInt == 0)
                {
                    $interpreter->errorHandler("Syntactic control: Bad order value", 32);
                }
                else if($convertedInt < 0)
                {
                    $interpreter->errorHandler("Syntactic control: Negative order of instruction", 32);
                }
                
                // list or arguments
                $argsList = [];
                

                // check all childNodes (arguments) of instruction node, args must start from 1
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
                    
                    // add new Argument to the argsList
                    $argsList[] = new Argument($i, strtoupper($arg[0]->getAttribute("type")), $arg[0]->nodeValue);
                }
                
                

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
     * @param Variable $var1 first variable 
     * @param Variable $var2 second variable
     * @param string $finalType final type of the expression
     * @return string Returns null if no error occurred, returns error message if error occurred 
     */
    public static function checkVariableType(string $opcode, ?Variable $var1, ?Variable $var2, ?string &$finalType) : ?string
    {
        switch($opcode)
        {
            case Opcode::ADD:
            case Opcode::SUB:
            case Opcode::SUB:
            case Opcode::MUL:
            case Opcode::IDIV:
                // TODO: add floats maybe
                if($var1 != null && $var1->getType() != Variable::INT)
                {
                    return "Expected type in first argument: Variable::INT" . ", got: " . $var1->getType(); // TODO:
                }
                if($var2 != null && $var2->getType() != Variable::INT)
                {
                    return "Expected type in second argument: Variable::INT" . ", got: " . $var2->getType(); //TODO:
                }
                $finalType = Variable::INT;
                break;
            case Opcode::LT:
            case Opcode::GT:
            case Opcode::opAND:
            case Opcode::opOR:
            case Opcode::opNOT:
                // TODO: add floats maybe
                if($var1 != null && $var1->getType() != Variable::BOOL)
                {
                    return "Expected type in first argument: Variable::BOOL" . ", got: " . $var1->getType(); // TODO:
                }
                if($var2 != null && $var2->getType() != Variable::BOOL)
                {
                    return "Expected type in second argument: Variable::BOOL" . ", got: " . $var2->getType(); //TODO:
                }
                break;
            case Opcode::INT2CHAR:
                if($var1 != null && $var1->getType() != Variable::BOOL)
                {
                    return "Expected type in first argument: Variable::INT" . ", got: " . $var1->getType(); // TODO:
                }
                break;
            case Opcode::STRI2INT:
                if($var1 != null && $var1->getType() != Variable::STRING)
                {
                    return "Expected type in first argument: Variable::STRING" . ", got: " . $var1->getType(); // TODO:
                }
                if($var2 != null && $var2->getType() != Variable::INT)
                {
                    return "Expected type in second argument: Variable::INT" . ", got: " . $var2->getType(); //TODO:
                }
                break;
            default:
                throw new StudentExceptions("Internal error: Unexpected 
                \$opcode in checkVariableType(): " . $opcode, 1); // TODO:
        }

        return null;
    }
    /**
     * Sorts instruction list by Instruction->order integer value
     *
     * @param array<Instruction> $instructions list of instructions 
     * @return array<Instruction> returns sorted list of instructions
     */
    public static function sortInstructionList(array $instructions) : array
    {
        $orderedInst = [];
        $count = count($instructions);
        for($index = 0; $index <= $count; $index++)
        {
            foreach($instructions as $inst)
            {
                if($index == $inst->getOrder())
                {
                    $orderedInst[$index] = $inst;
                }
            }

        }

        return $orderedInst;
    }

    

}