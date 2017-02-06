/**
 *  Turn on the showDebug mode to get a friendly output format for 
 *  the execution when viewing in a web browser.
 */
class BrainTwist {
    
    protected $code;
    protected $input;
    protected $pc;      // Program counter
    protected $memory;
    protected $mp;      // Memory Pointer
    protected $result;
    protected $execLevel = 0;   // Used by the debug method to nest output for easier viewing.
    protected $showDebug = 0;   // Output debugging statements to browser.

    /**
     *  Initializes the interpreter to run new code, then calls run().
     *  After execution finishes, it returns the contents of the Result data member
     *  as a string.
     */
    public function interpret($code, $input) {
        // Ensure the code passed in is valid to run by removing any characters not present 
        // in the language.
        $code = preg_replace('/[^\>\<\+\-\.\,\[\]]/', '', $code);

        // Setup for this run
        $this->code = $code;
        $this->input = str_split($input);
        $this->pc = 0;
        $this->memory = [];
        $this->mp = 0;
        $this->result = [];

        // Start interpretation
        $this->debug('Interpreting: '.$code);
        $this->debug('-------------------------------------------------');
        $this->run();
        return implode('', $this->result);
    }

    /**
     *  As long as there's more code to execute, run the interpreter.
     */
    public function run() {
        $codeLength = strlen($this->code);
        while ($codeLength > $this->pc) {
            $this->execCmd();
        }
    }


    /**
     *  Uses the Program Counter to grab the next command that needs to be executed,
     *  then executes some code depending on the type of command fetched.
     */
    protected function execCmd() {
        $return = 1;
        $reduceExecLevel = false;
        $cmd = $this->code[$this->pc];
        if ($cmd == '>') {
            $this->debug('CMD is ">", Moving memory pointer to the right.');
            $this->mp += 1;

        } elseif ($cmd == '<') {
            $this->debug('CMD is "<", Moving memory pointer to the left.');
            $this->mp -= 1;

        } elseif ($cmd == '+') {
            $this->debug('CMD is "+", Incrementing the byte at the memory pointer.');
            $this->memory[$this->mp] = $this->getCharCode($this->getMemoryValue($this->mp), 1);

        } elseif ($cmd == '-') {
            $this->debug('CMD is "-", Decrementing the byte at the memory pointer.');
            $this->memory[$this->mp] = $this->getCharCode($this->getMemoryValue($this->mp), -1);

        } elseif ($cmd == '.') {
            $this->debug('CMD is ".", Adding byte at memory pointer to result output');
            $this->result[] = $this->getMemoryValue($this->mp);

        } elseif ($cmd == ',') {
            $this->debug('CMD is ",", Getting one byte of input and storing it at current memory pointer location');
            $char = $this->getChar();
            $this->memory[$this->mp] = $char;
            $this->debug('&nbsp;&nbsp;&nbsp; the byte fetched is '.$this->getCharCodeDebug($char));

        } elseif ($cmd == '[') {
            $this->debug('CMD is "["');
            if ($this->getCharCode($this->getMemoryValue($this->mp)) === chr(0)) {
                $this->debug('Byte at current memory location is equal to '.$this->getCharCodeDebug($this->getMemoryValue($this->mp)).', so skipping this loop.');
                $this->skipLoop();
            } else {
                $this->debug('Byte at current memory location is equal to '.$this->getCharCodeDebug($this->getMemoryValue($this->mp)).', executing loop.');
                $this->execLevel++;
                $this->execLoop(++$this->pc);
            }   

        } elseif ($cmd == ']') {
            $this->debug('CMD is "]"');
            if ($this->getCharCode($this->getMemoryValue($this->mp)) === chr(0)) {
                $this->debug('Byte at current memory location is equal to 0, so exiting loop.');
                $reduceExecLevel = true;
                $return = (-1);
            } else {
                $this->debug('Byte at current memory location is equal to '.$this->getCharCodeDebug($this->getMemoryValue($this->mp)).', restarting loop.');
                $return = 2;
            }
        }

        $this->debugState();
        $this->debug('-------------------------------------------------');
        if ($reduceExecLevel) {
            $this->execLevel--;
        }
        if ($return != (-1)) {
            $this->pc++;
        }
        return $return;
    }


    /**
     *  Executes a loop. 
     */
    protected function execLoop($spc) {
        $result = $this->execCmd();
        
        // While the result of the last command executed isn't to end the loop...
        while($result != (-1)) {
            // If the loop needs to be restarted, set the Program Counter to the saved value 
            // that represents the start of the loop code.
            if ($result == 2) {
                $this->pc = $spc;
            }
            // Execute the next command at specified by the Program Counter
            $result = $this->execCmd();
        }
        return true;
    }

    protected function skipLoop() {
        // Grab the next command.
        $cmd = $this->code[++$this->pc];
        
        // While we haven't reached the end of the loop...
        while($cmd != ']') {
            // If we encounter a loop within a loop, just call this method recursively.
            if ($cmd == '[') {
                $this->skipLoop();
            }
            // Keep grabbing and looking at the next command.
            $cmd = $this->code[++$this->pc];
        }
        return true;
    }

    /**
     *  Fetches the value out of our Memory array using 
     *  the current Memory Pointer (mp). 
     *  If the array offset isn't set, return a zeroed out byte.
     */
    protected function getMemoryValue($offset) {
        if(isset($this->memory[$offset])) {
            return $this->memory[$offset];
        }
        // Else return a zero byte.
        return chr(0);
    }

    /**
     *  Get the character represented by a given character and an offset. 
     *  See: http://www.asciitable.com/ 
     *  and refer to the decimal column. 
     *  
     *  An offset should be given as a positive or negative 
     *  number that will be applied to the given char. 
     *  I.E. Given "A" and an offset of +1, will return "B". 
     *       Given "B" and an offset of -1, will return "A".
     *
     *  There's logic in place to ensure a valid 0-255 code will always be used. 
     *  Negative numbers start decrementing from 256, and positive numbers roll over. 
     *  A character code of 256 == 0. 
     *  A character code of -1 == 255.
     */
    protected function getCharCode($char, $offset = 0) {
        $code = ord($char);
        $code += $offset;
        if ($code < 0) {
            $code * (-1);
        }
        return chr($code % 256);
    }

    /**
     *  Because not possible byte values have a visible character associated with them,
     *  This method returns a string in for the form of a pair of values. 
     *  The left-hand value is the character representation (which is what you pass in),
     *  and the right-hand side is the decimal value of that character in ascii.
     */
    public function getCharCodeDebug($char) {
        return '['.$char.', '.ord($char).']';
    }

    /**
     *  Simulates capturing input from a user by taking the next unused character entered into 
     *  the input array.
     */
    protected function getChar() {
        return array_shift($this->input);
    }

    /**
     *  Returns a series of HTML &nbsp's for prettier display of execution 
     *  via nesting loops.
     */
    protected function getIndent()
    {
        $indent = '';
        for ($i=0; $i<$this->execLevel; $i++) {
            $indent .= '&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp';
        }
        return $indent;
    }

    /**
     *  Output a line of debug info.
     */
    protected function debug($str) {
        if ($this->showDebug) {
            echo $this->getIndent().$str."<br>";
        }
    }

    /**
     *  Output the values of the program state.
     */
    protected function debugState()
    {
        //$this->debug('Code: '.$this->code);
        $this->debug('Input: '.implode('', $this->input));
        $this->debug('PC: '.$this->pc);
        $this->debug('Memory: '.implode('', $this->memory).', Bytes: '.implode('', array_map([$this,'getCharCodeDebug'], $this->memory)));
        $this->debug('Mp: '.$this->mp);
        $this->debug('Result: '.implode('', $this->result));
    }
}
