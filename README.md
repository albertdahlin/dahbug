## Dahbug

### Installation & Configuration
Include dahbug.php in your index.php or add it to the auto_prepend_file setting in php.ini.

### Reference

#### dump
Formats and prints debug information of data.
```php
    mixed dump(mixed $var, string $label = null, int $maxDepth = null)
```
* `$var` The variable you want to dump.
* `$label` [optional] You can pass a string here to be used as label in your log.
* `$maxDepth` [optional] The maximum recursion depth when printing arrays.

#### methods
Prints the class methods of the object's class and its parents.
```php
    void methods(object $object)
```
* `$object` The object you want to print the methods from.
