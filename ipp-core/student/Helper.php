<?php
namespace IPP\Student;

use DOMNodeList;
use DOMElement;
use DOMNode;

class Helper
{
    /**
     * @param DOMNodeList<DOMNode> $instructionList
     * @return array<Instruction>
     */
    public static function convertToInstructions(DOMNodeList $instructionList): array
    {
        $arrayOfInstructions = [];

        foreach($instructionList as $inst)
        {
            if ($inst instanceof DOMElement) 
            {
                $opcode = $inst->getAttribute("opcode");
                $order = $inst->getAttribute("order");
                
                $convertedInt = intval($order);
                // check if converted integer is valid
                if($convertedInt == 0)
                {
                    throw new  StudentExceptions("Bad order value", 1); /*TODO: change*/
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
                        throw new StudentExceptions("More than one argument with same name (" . $i . ")\n", 1); /*TODO:*/
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