<?php
namespace IPP\Student;

class Label
{
    /**
     * @var string @name name of the label 
     */
    private string $name;
    /**
    * @var int $instructionIndex index of the instruction to return to
    */
    private int $instructionIndex;

    public function __construct(string $name, int $instructionIndex)
    {
        $this->name = $name;
        $this->instructionIndex = $instructionIndex;
    }
    
    /**
     * @return string returns name of the label
     */
    public function getName() : string
    {
        return $this->name;
    }
    
    /**
     * @return int returns name of the instruction index that this label points to
     */
    public function getInstructionIndex() : int
    {
        return $this->instructionIndex;
    }
    
        
    /**
     * @return string returns string representation of label
     */
    public function toString() : string 
    {
        return "label: name=" . $this->name . ", instructionIndex=" . $this->instructionIndex;  
    }   
}