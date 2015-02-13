## Dahbug
This script grew from the frustration when trying to log debug data with `var_dump()` or `print_r()`. If you have a large multidimensional array, or an object with circular references, those functions will just output to much data.
To solve this I wrote this little script that writes debug data to a log file.

### 1. Installation & Configuration
Include `dahbug.php` in your index.php or add it to the `auto_prepend_file` setting in your php.ini. The file declares one class, `dahbug` that works as wrapper for the dump methods.

There are a few configuration options for the script. You will find them in the config.json file. 
If you wish to modify these settings, create a file called `local.json` and override them there.

##### Configuration

```
    log_file:         This is the file where the output will be printed. 
                      Remember that it has to be writable by the web server.
    output:           file: Logs dumps to log_file.
                      print: Prints (echo) dumps to output.
    label_format:     The format of the labels printed by the dump() method.
    string_format:    String format for the dump() method.
    timestamp_format: Format for the header timestamp. Checkout the PHP manual page for the
                      `date()` function for format options.
    print_timestamp:  If set to true, the timestamp will be added to the output header according
                      to the format specified in the `timestamp_format` option.
    print_filename:   If set to true, the filename and line number from where dump()
                      is called will be printed before the output.
    string_cap:       When strings are printed by dump() they will be capped to this length.
                      If set to 0 or false, string cap will be disabled.
    ascii_notation    "caret" or "escape". How to output non printable ascii characters.
                      eg. new line will be printed as ^J (caret) or \n (escape).
    line_endings:     The line ending format your terminal is using.
                      Can be set to: "LF" (linux), "CR" (old Mac) or "CRLF" (Windows)
    output_encoding:  The character encoding of your terminal, eg. "UTF-8", "ISO-8859-1",
                      "ASCII" etc... Accepts all values that are listed by `mb_list_encodings()`
    indent:           When printing arrays, each level will be indented this number of spaces.
    max_depth:        The maximum number of recursions (levels) when printing arrays.
    use_colors:       Set to true if you want to colorize terminal output.
    theme:            Select color theme. Themes are saved in <theme name>.theme files.
    color:            Terminal color values. Don't touch...
    escape_chars      Escape character map. Don't touch...
```

##### Color themes
You can create your own color themes. Just copy `default.theme` and change the colors.
Valid color values for standard 16 color terminal can be found in the config.json file.
For 256 color mode values, run `php palette.php` and it will print a palette with color values.
Use the three digit values from the palette to create your theme.

### 2. Usage
Just make a static call to the method you want to use:

```php
    dahbug::dump($var);
```

When you use any of the dump methods the output will be printed to the log file.
I would recommed you to open a terminal and run `tail -f <logfile>` to see the output as the application is running..

When you make a request to a script, eg. http://example.com/some/path the following will be printed to the log file:

```
192.168.1.87 example.com GET /some/path

Request processing time: 6.46 ms   Memory Usage: 1 Mb
```

The debug output will be printed between those lines.

### 3. Example

I will use this code for the examples in the function reference

```php
class foo
{
    protected $data;

    public function setData($data)
    {
        $this->data = $data;
    }

    public function getData()
    {
        return $this->data;
    }
}

class bar extends foo
{
    public function printData()
    {
        if ($this->_canPrint()) {
            print_r($this->data);
        }
    }

    protected function _canPrint()
    {
        return !empty($this->data);
    }
}

$bar = new bar;
$bar->setData(array(
    'some_string' => 'Foo Bar Baz',
    'a_numer' => 123,
    'an_array' => array(
        'more' => 'String',
        'data' => 123
    ),
    'an_object' => new stdClass()
));
```

### 4. Function Reference
* [dump()](#dump) Dump variables and formated printing.
* [methods()](#methods) Dumps class methods or method code.
* [backtrace()](#backtrace) Prints a backtrace.
* [write()](#write) Outputs text.
* [hex()](#hex) Prints a hex dump.
* [nl()](#nl) Prints a newline.

#### dump
Formats and prints debug information of data. 

Arrays and objects will be printed recursively, but only as many levels as specified in the max_depth config option or by the third function parameter ($maxDepth).

Strings will be capped to the length specified in config.json.

```php
    mixed dump(mixed $var, string $label = null, int $maxDepth = null)
```
* `$var` The variable to dump. This will also be the return value.
* `$label` [optional] You can pass a string here to be used as label in your log.
* `$maxDepth` [optional] The maximum recursion depth when printing arrays.

###### Example:
Adding `dahbug::dump($bar);` to the end of the example script will output:

```
192.168.1.87 test.dev GET /dahbug/example.php

 In file /var/www/dahbug/example.php:44
  [$bar] = (object:1) bar
    [*data] => (array:4) 
        [some_string] => (string:11:ASCII) 'Foo Bar Baz'
        [a_numer] => (int) 123
        [an_array] => (array:2) 
            [more] => (string:6:ASCII) 'String'
            [data] => (int) 123
        [an_object] => (object:0) stdClass

Request processing time: 13.81 ms   Memory Usage: 1 Mb

```

#### methods
This method prints the class methods of an object or a class and its parents. Also prints the source code of a method if specified.

```php
    void methods(mixed $object, string $method = null)
```
* `$object` The object you want to print the methods from.
* `$method` [optional] If you pass a method name the source code and php doc of that method will be printed.

###### Example:
Adding `dahbug::methods($bar);` to the end of the example script will output:

```
192.168.1.87 test.dev GET /dahbug/example.php

 In file /var/www/dahbug/example.php:44
 class bar
    printData ()

 extends foo
    setData ($data)
    getData ()

Request processing time: 3.40 ms   Memory Usage: 1 Mb

```
As you can see, only the public methods are listed.

Adding `dahbug::methods($bar, 'printData');` to the end of the example script will output:

```
192.168.1.87 test.dev GET /dahbug/example.php

defined in class bar
  file /var/www/dahbug/example.php:20

    public function printData()
    {
        if ($this->_canPrint()) {
            print_r($this->data);
        }
    }

Request processing time: 6.46 ms   Memory Usage: 1 Mb

```

#### backtrace
Prints a backtrace.

```php
    void backtrace()
```

#### write
Prints a string to the log without formating the text.

```php
    void write(mixed $var, string $encoding = null)
```
* `$var` The variable to print. If an object is passed, the __toString() method will be called to generate the output.
* `$encoding` Force encoding of output to this encoding.

#### hex
Prints a hex dump of a binary string.

```php
    void hex(string $bin)
```
* `$bin` The binary string you want to dump.

#### nl
Prints a new line to the log file.

```php
    void nl()
```
