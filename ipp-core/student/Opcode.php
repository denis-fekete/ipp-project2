<?php
/**
 * IPP - PHP Project 2
 * 
 * Opcode.php 
 * Enum like class for storing Opcode names to and methods for categorizing them
 * 
 * @author Denis Fekete (xfeket01@fit.vutbr.cz)
 * @
 */
namespace IPP\Student;

/**
 * Opcodes is "enum" like class to make sure no typos happen
 */
abstract class Opcode
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
    public const string EQ = "EQ";
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
    public static function isArithmeticOrString(?string $value) : bool
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
            case Opcode::EQ         :
            case Opcode::opAND      :
            case Opcode::opOR       :
            case Opcode::opNOT      :
            case Opcode::INT2CHAR   :
            case Opcode::STRI2INT   :
            case Opcode::GETCHAR    :
            case Opcode::STRLEN     :
            case Opcode::SETCHAR    :
            case Opcode::CONCAT     :
                return true;
            default: return false;
        }
    }

    /**
     * @param ?string $value input value to be checked
     * @return bool true if opcode is jumping operation code, false if it 
     * not or input is null
     */
    public static function isJump(?string $value) : bool
    {
        if($value == null)  return false;

        switch($value)
        {
            case Opcode::CALL       : 
            case Opcode::JUMP       : 
            case Opcode::JUMPIFEQ   :
            case Opcode::JUMPIFNEQ  :
                return true;
            default: return false;
        }
    }

        
    /**
     * Checks if $value is opcode
     *
     * @param string $value value to be checked
     * @return bool true if $value is opcode, false otherwise
     */
    public static function isOpcode(string $value) : bool
    {
        switch($value)
        {
            case Opcode::MOVE       :
            case Opcode::CREATEFRAME:
            case Opcode::PUSHFRAME  :
            case Opcode::POPFRAME   :
            case Opcode::DEFVAR     :
            case Opcode::CALL       :
            case Opcode::RETURN     :
            case Opcode::PUSHS      :
            case Opcode::POPS       :
            case Opcode::ADD        :
            case Opcode::SUB        :
            case Opcode::MUL        :
            case Opcode::IDIV       :
            case Opcode::LT         :
            case Opcode::GT         :
            case Opcode::EQ         :
            case Opcode::opAND      :
            case Opcode::opOR       :
            case Opcode::opNOT      :
            case Opcode::INT2CHAR   :
            case Opcode::STRI2INT   :
            case Opcode::READ       :
            case Opcode::WRITE      :
            case Opcode::CONCAT     :
            case Opcode::STRLEN     :
            case Opcode::GETCHAR    :
            case Opcode::SETCHAR    :
            case Opcode::TYPE       :
            case Opcode::LABEL      :
            case Opcode::JUMP       :
            case Opcode::JUMPIFEQ   :
            case Opcode::JUMPIFNEQ  :
            case Opcode::EXIT       :
            case Opcode::DPRINT     :
            case Opcode::BREAK      :
                return true;
            default:
                return false;
        }
    }   
}