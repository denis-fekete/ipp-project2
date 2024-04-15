<?php

namespace IPP\Student;

use IPP\Core\AbstractInterpreter;

use IPP\Student\Instruction;
use IPP\Student\StudentExceptions;
use IPP\Student\Opcode;
use IPP\Student\Variable;
use IPP\Student\Label;
use IPP\Student\InstExec as IExec;

class Interpreter extends AbstractInterpreter
{
    public Variable $globalLiteral;
    /**
     * @var array<Instruction> $instructions list of Instruction objects 
     * holding instructions loaded from XML
     */ 
    private array $instructions; 
    /**
     * @var array<Variable> $tempFrame list of Variable objects holding 
     * declared variables in temporary frame
     */     
    private ?array $tempFrame;
        /**
     * @var array<Variable> $globalFrame list of Variable objects holding 
     * declared variables in global frame
     */     
    private array $globalFrame;
    /**
     * @var array<array<Variable>> $frameStack is an array of the Variable 
     * arrays to hold frames
     */ 
    private array $frameStack;
    /**
     * @var array<Label>
     */
    private array $labelArr;
    /**
     * @var array<int> $instructionStack stack of instruction counters 
     */
    private $instructionStack;
    /**
     * @var array<Variable> stack of variables for PUSHS and POPS
     */ 
    private array $variableStack;
    public int $numberOfInstructions;
    /**
     * Instruction counter of the program, which instruction is currently 
     * being performed
     * @var int $instCnt
     */
    private int $instCnt;

    public function execute(): int
    {
        // initialize global literal variable
        $this->globalLiteral = new Variable("");
        // initialize and setup frame stack
        $this->initFrameStack();
        // initialize other arrays
        $this->instCnt = 0;
        $this->labelArr = [];
        $this->instructions = [];
        $this->instructionStack = [];
        $this->variableStack = [];
        $this->tempFrame = null;
        $this->numberOfInstructions = 0;

        // get xml file
        $dom = $this->source->getDOMDocument();
        $root = $dom->getElementsByTagName("*");

        // check names of the elements in xml
        Helper::checkNodeNames($root, $this);

        // get raw data from input xml 
        $rawInstructions = $dom->getElementsByTagName("instruction");

        // convert raw xml data into and Instruction class/objects 
        $highestOrder = 0;
        $this->instructions = Helper::convertToInstructions($rawInstructions, $this, $highestOrder);

        // order instruction lost by Instruction->order value
        Helper::sortMyLists($this->instructions, $highestOrder);

        // first run, declaring labels
        $this->executeInstructions(true);
        // second run, full semantic control and code executions
        $this->executeInstructions(false);
        exit(0);
    }
    
    
    /**
     * Performs Instructions in in Instruction list ($instructions) and returns 
     * whenever error occurred 
     * @param bool $onlyLabels if set to true, only label opcode will be executed
     * @return bool Returns true if no error occurred
     */
    public function executeInstructions(bool $onlyLabels) : bool 
    {
        // execute instructions in order
        // foreach($this->instructions as $instruction)
        for(; $this->instCnt < count($this->instructions); $this->instCnt++)
        {
            $instruction = $this->instructions[$this->instCnt];
            // get instruction opcode and convert it into an upper case
            $opcode = $instruction->getOpcode();


            if($onlyLabels)
            {
                if($opcode == Opcode::LABEL)
                {
                    if(IExec::Label($instruction, $this, $this->instCnt))
                    { return false; }
                }
                continue;
            }
            
            $this->numberOfInstructions++;

            if ( Opcode::isArithmeticOrString($opcode) )
            {
                if(IExec::ArithmeticOrString($instruction, $this, $opcode))
                { return false; }
                continue; // do not go to switch
            }
            else if(Opcode::isJump($opcode))
            {
                if(IExec::Jump($instruction, $this, $opcode))
                { return false; }
                continue; // do not go to switch
            }
            switch($opcode)
            {
                case Opcode::DEFVAR:
                    if(IExec::Defvar($instruction, $this))
                    { return false; }
                    break;
                case Opcode::MOVE:
                    if(IExec::Move($instruction, $this))
                    { return false; }
                    break;
                case Opcode::READ:
                    if(IExec::Read($instruction, $this))
                    { return false; }
                    break;
                case Opcode::WRITE:
                case Opcode::DPRINT: 
                    if(IExec::Write($instruction, $this, $opcode))
                    { return false; }
                    break;
                case Opcode::PUSHFRAME:
                    $this->pushFrame();
                    break;
                case Opcode::POPFRAME:
                    $this->popFrame();
                    break;
                case Opcode::CREATEFRAME:
                    $this->createFrame();
                    break;
                case Opcode::EXIT:
                    if(IExec::EXIT($instruction, $this))
                    { return false; }
                    break;
                case Opcode::LABEL:
                    break;
                case Opcode::RETURN:
                    if(IExec::Return($this))
                    { return false; }
                    break;
                case Opcode::TYPE:
                    if(IExec::Type($instruction, $this))
                    { return false; }
                    break;
                case Opcode::PUSHS:
                case Opcode::POPS:
                    if(IExec::VariableStack($instruction, $this, $opcode))
                    { return false; }
                    break;
                case Opcode::BREAK:
                    $last = null;
                    $next = null;
                    if($this->instCnt > 1)
                    {
                        $last = $this->instructions[$this->instCnt - 1];
                    }
                    if($this->instCnt + 1 < count($this->instructions))
                    {
                        $next = $this->instructions[$this->instCnt + 1];
                    }
                    IExec::Break($instruction, $this, $last, $next);

                    break;
                default:
                    $this->errorHandler("Unknown operation code", 32);
                    return false;
            }
        }
        
        // if only labels, reset instruction counter
        if($onlyLabels)
        {
            $this->instCnt = 0;
        }
        return true;
    }

    // ------------------------------------------------------------------------
    //
    // ------------------------------------------------------------------------
    
    /**
     * Handler error occurrence
     *
     * @param string $errMsg message to be printed into stderr
     * @param int $errCode error code that program will exit with
     * @return void
     */
    public function errorHandler(string $errMsg, int $errCode) : void
    {
        $inst = "cannot be accessed";
        if($this->instructions != null)
        {
            $inst = $this->instructions[$this->instCnt]->toString();
        }

        // $this->stderr->writeString($errMsg . "\nCurrent instruction: " . $inst . "\n");
        $this->stderr->writeString($errMsg . "\n");
        exit($errCode);
    }

    /**
     * Prints instructions in $instructionList
     * @param array<Instruction> $instructionList array to be printed
     */
    public function printInstructions(array $instructionList): void
    {
        foreach($instructionList as $inst)
        {
            $this->println($inst->toString());
        }
    }
    

    public function printFrameStack() : void
    {

        $this->stderr->writeString("Frames / scopes\n");
        $this->stderr->writeString("Temp frame (TF@):\n");
        if($this->tempFrame != null)
        {
            foreach($this->tempFrame as $frame)
            {
                $this->println("\t" . $frame->toString() . "\n");
            }
        }
        
        $this->stderr->writeString("\n------------------------------\n");
        $this->stderr->writeString("Global frame (GF@):\n");
        foreach($this->globalFrame as $frame)
        {
            $this->stderr->writeString("\t" . $frame->toString() . "\n");
        }

        $this->stderr->writeString("\n------------------------------\n");
        $this->stderr->writeString("Local frame (LF@):\n");
        $i = 0;
        foreach($this->frameStack as $local)
        {
            $this->stderr->writeString("\t" . $i . ":\n");
            if($local != null)
            {
                foreach($local as $frame)
                {
                    $this->stderr->writeString("\t" . $frame->toString() . "\n");
                }
            }

            $i++;
        }
    }
    // ------------------------------------------------------------------------
    //
    // ------------------------------------------------------------------------

       /**
     * Read input from standard input
     *
     * @param $type type of value to read
     * @return int|float|string|bool|null
     */
    public function read(string $type) : int|float|string|bool|null
    {  
        switch($type)
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
        throw new StudentExceptions("Internal error: Unknown type to read in Interpreter::read()", 1);
    }

    /**
     * Writes to standard output
     *
     * @param  mixed $value value to be printed to the stdout
     * @return void
     */
    public function print(mixed $value): void
    {
        if (is_string($value))
            $this->stdout->writeString($value);
        else if( is_int($value) )
            $this->stdout->writeInt($value);
        else if(is_float($value))
            $this->stdout->writeFloat($value);
        else if(is_bool($value))
            $this->stdout->writeBool($value);
        else if(is_null($value))
            $this->stdout->writeString("nil");
        
        return;
    }

    public function printErr(mixed $value): void
    {
        if (is_string($value))
            $this->stderr->writeString($value);
        else if( is_int($value) )
            $this->stderr->writeInt($value);
        else if(is_float($value))
            $this->stderr->writeFloat($value);
        else if(is_bool($value))
            $this->stderr->writeBool($value);
        else if(is_null($value))
            $this->stderr->writeString("nil");

        return;
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

    // ------------------------------------------------------------------------
    //
    // ------------------------------------------------------------------------

    /**
     * Adds new variable to the variable list in current scope/frame at given 
     * position $pos
     *
     * @param Variable $newVar Variable to be added to the Variable list
     */
    public function addVariable(Variable $newVar, string $scope) : void
    {
        switch($scope)
        {
            case Variable::GF_SCOPE:
                array_push($this->globalFrame, $newVar);
                break;
            case Variable::LF_SCOPE:
                if($this->frameStack == null)
                {
                    $this->errorHandler("Semantic error: Trying to access undefined frame stack: " . $scope, 55);
                    return;
                }

                array_push($this->frameStack[count($this->frameStack) - 1], $newVar);
                break;
            case Variable::TF_SCOPE:
                if($this->tempFrame === null)
                {
                    $this->errorHandler("Semantic error: Trying to access undefined frame stack: " . $scope, 55);
                    return;
                }
                array_push($this->tempFrame, $newVar);
                break;
            default:
                $this->errorHandler("Syntactic error: Unknown scope: " . $scope, 1);
                break;
        }   

    }

    /**
     * Look in provided frame and finds variable with provided name
     * @param string $key name of the variable to be found
     * @param array<Variable> $variableArray array of Variables
     * @return Variable|null 
     */
    private function findInFrames(string $key, array $variableArray) : ?Variable
    {
        foreach($variableArray as $variable)
        {
            if($variable->getName() == $key)
            {
                return $variable;
            }
        }

        return null;
    }

    /**
     * Returns Variable element from the Variable list in current 
     * scope/frame based on provided key ($key) 
     * 
     * @param string $key key that will be looked up in Variable list returned
     * @param string $scope scope that should be looked up
     * @return ?Variable value found, returns null if not found
     */
    public function getVariable(string $key, string $scope) : ?Variable
    {
        if($scope == Variable::TF_SCOPE)
        {
            if($this->tempFrame === null)
            {
                $this->errorHandler("Semantic error: Trying to access invalid frame", 54);   
                return null;
            }

            $foundVariable = $this->findInFrames($key, $this->tempFrame);

            if($foundVariable === null)
            {
                return $this->findInFrames($key, $this->globalFrame);
            }
            else
            {
                return $foundVariable;
            }
        }
        else if($scope == Variable::GF_SCOPE)
        {
            return $this->findInFrames($key, $this->globalFrame);
        }
        else
        {
            $foundVariable = null;
            for($i = count($this->frameStack) - 1; $i >= 0; $i--)
            {
                $tempFrame = $this->frameStack[$i];
                if($tempFrame !== null)
                {
                    $foundVariable = $this->findInFrames($key, $tempFrame);
                    if($foundVariable != null)
                    {
                        return $foundVariable;
                    }
                }
            }

            return $this->findInFrames($key, $this->globalFrame);
        }
    }

    // ------------------------------------------------------------------------
    //
    // ------------------------------------------------------------------------
    
    /**
     * Adds new label to the global label list
     * @param Label $newLabel new label to be added
     * @return void
     */
    public function addLabel(Label $newLabel) : void
    {
        $this->labelArr[] = $newLabel;
    }
        
    /**
     * Finds label in global label name
     * @param string $labelName name of the label to be found
     * @return ?Label returns found label, returns null if not found
     */
    public function getLabel(string $labelName) : ?Label
    {
        foreach($this->labelArr as $label)
        {
            if($label instanceof Label)
            {
                if($label->getName() == $labelName)
                {
                    return $label;
                }
            }
        }

        return null;
    }

    // ------------------------------------------------------------------------
    //
    // ------------------------------------------------------------------------    
    /**
     * Changes instruction counter of the interpret
     *
     * @param int $index new value of instruction counter
     * @return void
     */
    public function changeInstructionCounter(int $index) : void
    {
        $this->instCnt = $index;
    }

    // ------------------------------------------------------------------------
    //
    // ------------------------------------------------------------------------

    /**
     * Creates new temporary frame and throws away temporary frame
     *
     * @return void
     */
    public function createFrame() : void
    {
        // throw away old tempFrame and create new
        $this->tempFrame = [];
    }
    
    /**
     * Pushes new frame to the frame stack and invalidates temporary frame 
     * @return void
     */
    public function pushFrame() : void
    {
        if($this->tempFrame === null)
        {
            $this->errorHandler("Semantic error: Trying to push uninitialized temporary frame", 55);
        }

        // add current temporary frame to the stack frame
        array_push($this->frameStack, $this->tempFrame);
        // invalidate current temp frame
        $this->tempFrame = null;
    }
    
    /**
     * Pops frame from the frame stack throwing away old temporary frame 
     * @return void
     */
    public function popFrame() : void
    {        
        // pop from frameStack and put it in temporary frame
        $this->tempFrame = array_pop($this->frameStack);
        // if frameStack is empty
        if($this->tempFrame == null)
        {
            $this->errorHandler("Semantic error: Trying to pop from empty frame stack", 55);
            return;
        }
    }

    /**
     * Initializes frameStack
     *
     * @return void
     */
    private function initFrameStack() : void
    {
        // initialize array of variables 
        $this->tempFrame = null;
        // array to the frame stack
        $this->frameStack = [];
        // initialize global array of variables
        $this->globalFrame = [];
    }
    
    // ------------------------------------------------------------------------
    //
    // ------------------------------------------------------------------------

    /**
     * Pushes current instruction counter to the stack of instructions counters
     * @return void
     */
    public function pushInstructionCounter() : void
    {
        array_push($this->instructionStack, $this->instCnt);
    }
    
    /**
     * Pop instruction counter from the stack of instructions counters
     * @return ?int popped instruction counter value, null if pop failed
     */
    public function popInstructionCounter() : ?int
    {
        return array_pop($this->instructionStack);
    }

    // ------------------------------------------------------------------------
    //
    // ------------------------------------------------------------------------

    /**
     * Pushes current input Variable to the stack of Variables
     * @param Variable $value to be pushed to the stack 
     * @return void
     */
    public function pushVariableStack(Variable $value) : void
    {
        array_push($this->variableStack, $value);
    }
    
    /**
     * Pop Variable from the stack of Variables
     * @return ?Variable popped instruction counter value, null if pop failed
     */
    public function popVariableStack() : ?Variable
    {
        return array_pop($this->variableStack);
    }

} /*INTERPRETER*/