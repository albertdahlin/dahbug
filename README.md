## Dahbug

### Installation & Configuration
Include dahbug.php in your index.php or add it to the auto_prepend_file setting in php.ini.

### Reference
* [dump](#dump)
* [methods](#methods)

#### dump
Formats and prints debug information of data.
```php
    mixed dump(mixed $var, string $label = null, int $maxDepth = null)
```
* `$var` The variable you want to dump.
* `$label` [optional] You can pass a string here to be used as label in your log.
* `$maxDepth` [optional] The maximum recursion depth when printing arrays.

#### methods
This method prints the class methods of an object or a class and its parents. Also prints the source code of a method if specified.
```php
    void methods(mixed $object, string $method = null)
```
* `$object` The object you want to print the methods from.
* `$method` [optional] If you pass a method name the source code and php doc of that method will be printed.
