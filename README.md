# BrainTwist
An implementation of the BrainF**k language.

## Usage Example
> $code = '>+++++++++[<++++++++>-]<.>+++++++[<++++>-]<+.+++++++..+++.[-]>++++++++[<++++>-]
           <.#>+++++++++++[<+++++>-]<.>++++++++[<+++>-]<.+++.------.--------.[-]>++++++++[
           <++++>-]<+.[-]++++++++++.';
> $input = '';
> $bt = new BrainTwist();
> $result = $bt->interpret($code, $input);
> echo $result;
