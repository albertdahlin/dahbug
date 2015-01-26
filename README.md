## Dahbug
This script grew from the frustration when trying to log debug data with `var_dump()` or `print_r()`. If you have a large multidimensional array, or an object with circular references, those functions will just output to much data.
To solve this I wrote this little script that writes debug data to a log file.

### Installation & Configuration
Include `dahbug.php` in your index.php or add it to the `auto_prepend_file` setting in your php.ini. The file declares one class, `dahbug` that works as wrapper for the dump methods.

There are a few configuration options for the script. You will find them in the config.json file. 
If you wish to modify these settings, create a file called `local.json` and override them there.

```
    log_file:       This is the file where the output will be printed. 
                    Remember that it has to be writable by the web server.
    label_format:   The format of the labels printed by the dump() method.
    string_format:  String format for the dump() method.
    print_filename: If set to true, the filename and line number from where dump() 
                    is called will be printed before the output. 
    string_cap:     When strings are printed by dump() they will be capped to this length.
    indent:         When printing arrays, each level will be indented this number of spaces.
    max_depth:      The maximum number of recursions (levels) when printing arrays.
```

### Usage
When you use any of the dump methods the output will be printed to the log file.
I would recommed you to open a terminal and run `tail -f <logfile>` to see the output as the application is running..

### Function Reference
* [dump()](#dump) Dump variables and formated printing.
* [methods()](#methods) Dumps class methods or method code.
* [backtrace()](#backtrace) Prints a backtrace.
* [outputln()](#outputln) Outputs text.

#### dump
Formats and prints debug information of data. 

Arrays and objects will be printed recursively, but only as many levels as specified in the max_depth config option or by the third function parameter ($maxDepth).

Strings will be capped to the length specified in config.json.

```php
    mixed dump(mixed $var, string $label = null, int $maxDepth = null)
```
* `$var` The variable to dump.
* `$label` [optional] You can pass a string here to be used as label in your log.
* `$maxDepth` [optional] The maximum recursion depth when printing arrays.

#### methods
This method prints the class methods of an object or a class and its parents. Also prints the source code of a method if specified.

```php
    void methods(mixed $object, string $method = null)
```
* `$object` The object you want to print the methods from.
* `$method` [optional] If you pass a method name the source code and php doc of that method will be printed.

#### backtrace
Prints a backtrace.

```php
    void backtrace()
```

#### outputln
Prints a string to the log without formating the text.

```php
    void outputln(mixed $var)
```
* `$var` The variable to print. If an object is passed, the __toString() method will be called to generate the output.
