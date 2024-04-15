# IPP Project 2 - PHP Interpret

PHP Interpret is an Interpret for IPPcode24 language implemented in PHP language (designed for version PHP 8.3). The interpreter takes XML input and executes its code. It is expected that XML input will be in the correct format, have correct values etc... however interpret does perform some basic syntactic checks to ensure that the program will exit with the error message instead of exceptions (in case of missing arguments etc...). 

## Implementation

### Interpret class
The interpreter is implemented in the Interpret class, it contains all methods for working with the interpreter and the data it is storing. The interpret instance starts its function upon calling its method **execute**. Method **execute** initializes all internal data structures and reads input XML file. Correct name of elements like *program*, *instruction* and *arg* is checked, if no error is found these elements are converted into an array of **Variables** objects. This array is then sorted by *order* of instructions (internal value of the *instruction* element). After sorting the array a first run of semantic control is executed (method executeInstructions()), where only *LABEL* instructions are executed and other instructions are ignored. An array of **Label** objects is created, this ensures that jump instructions can jump to labels that are yet to be declared from the point of jump instructions. After the array of labels has been filled second run of semantic control is run. In the second run, all instructions (except *LABEL*) are checked and executed.

For better readability of the code methods that perform semantic checks and execute code have been moved into separate classes **Helper** and **InstExec** (InstructionExecuter).

### InstExec(InstructionExecuter) and Helper classes
These classes contain only static methods and don't contain constructions as they are not meant to be instantiated. InstExec class contains methods for specific instructions that perform specific semantic checks and execution of code. Helper class contains an implementation of helping methods like sorting, type control based on instruction etc...

### Instruction class
The Instruction class contains information about instruction, *opcode*, *order* and an array of **Arguments**.

### Argument class
The Argument class contains information about arguments provided in the instruction. Argument stores *order* of argument in given instruction, *type* of the instruction and *value* it is storing.

### Variable class
The Variable class is a class that stores information about variables that have been declared or defined in the program. It contains its *name*, *type*, *value* and if it was defined. Variables can also store literals, this functionality is used for easier working with class and in instructions like *PUSHS* and *POPS*.

### Label class
The Label class contains the *name* of the label and *instructionIndex* to which the internal instruction counter of the Interpret class changes if jump function jumps to this label.

### Opcode class
The Opcode class contains names of the instructions and static methods like *isJump()* that returns a bool value whenever an instruction is type that changes instruction counter of the Interpret. This class purpose is to be equivalent of global "enumeration" that can be found in other programming languages like C, C++ etc...  