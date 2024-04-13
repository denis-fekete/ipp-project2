<?php
namespace IPP\Student;

/**
 * Opcodes is "enum" like class to make sure no typos happen
 */
class Opcode
{
    // MEMORY FRAMES AND FUNCTION CALLS
    public const string MOVE = "MOVE"; 
    public const string CREATEFRAME = "CREATEFRAME"; 
    public const string PUSHFRAME = "PUSHFRAME"; 
    public const string POPFRAME = "POPFRAME"; 
    public const string DEFVAR = "DEFVAR"; 
    public const string CALL = "CALL";
    public const string RETURN = "RETURN";
    // WORKING WITH STACK
    public const string PUSHS = "PUSHS";
    public const string POPS = "POPS";
    // ARITHMETIC OPERATORS
    public const string ADD = "ADD";
    public const string SUB = "SUB";
    public const string MUL = "MUL";
    public const string IDIV = "IDIV";
    public const string LT = "LT";
    public const string GT = "GT";
    public const string opAND = "AND";
    public const string opOR = "OR";
    public const string opNOT = "NOT";
    public const string INT2CHAR = "INT2CHAR";
    public const string STRI2INT = "STRI2INT";
    // INPUT / OUTPUT OPERATIONS
    public const string READ = "READ";
    public const string WRITE = "WRITE"; 
    // WORKING WITH STRING
    public const string CONCAT = "CONCAT";
    public const string STRLEN = "STRLEN";
    public const string GETCHAR = "GETCHAR";
    public const string SETCHAR = "SETCHAR";
    // TYPE CONTROL 
    public const string TYPE = "TYPE";
    // PROGRAM FLOW
    public const string LABEL = "LABEL";
    public const string JUMP = "JUMP";
    public const string JUMPIFEQ = "JUMPIFEQ";
    public const string JUMPIFNEQ = "JUMPIFNEQ";
    public const string EXIT = "EXIT";
    // DEBUGGING
    public const string DPRINT = "DPRINT";
    public const string BREAK = "BREAK";
    
    /**
     * @param ?string $value input value to be checked
     * @return bool true if opcode is arithmetic, false if it not or input 
     * is null
     */
    public static function isArithmetic(?string $value) : bool
    {
        if($value == null)  return false;

        switch($value)
        {
            case Opcode::ADD        : 
            case Opcode::SUB        :
            case Opcode::MUL        :
            case Opcode::IDIV       :
            case Opcode::LT         :
            case Opcode::GT         :
            case Opcode::opAND      :
            case Opcode::opOR       :
            case Opcode::opNOT      :
            case Opcode::INT2CHAR   :
            case Opcode::STRI2INT   :
                return true;
            default: return false;
        }
    }
}