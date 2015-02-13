<?php

/**
 * Class dahbug
 * A collection of useful dev functions for printing debug information
 * to a file.
 *
 * @copyright Copyright (C) 2015 Albert Dahlin
 * @author Albert Dahlin <info@albertdahlin.com>
 * @license GNU GPL v3.0 <http://www.gnu.org/licenses/gpl-3.0.html>
 */

class dahbug
{
    /**
     *  STATIC CLASS DECLARATIONS
     */

    /**
     * Instance for singleton pattern.
     *
     * @var dahbug
     * @access protected
     */
    static protected $_instance;

    /**
     * Config data.
     *
     * @var array
     * @access protected
     */
    static protected $_data = array();

    /**
     * Holds the file pointer to the log file.
     *
     * @var resource
     * @access protected
     */
    static protected $_logFile;

    /**
     * Holds the backtrace array generated from debug_backtrace.
     *
     * @var array
     * @access protected
     */
    static protected $_backtrace;

    /**
     * Holds last printed filename.
     *
     * @var string
     * @access protected
     */
    static protected $_lastFilename;

    /**
     * Initializes debug. Reads two config files, config.json and local.json.
     *
     * @access public
     * @return void
     */
    static public function init()
    {
        self::setData('root', dirname(__file__));
        self::_loadConfigFile('config.json');
        self::_loadConfigFile('local.json');

        $logFile = self::getData('log_file');
        if ($logFile) {
            self::$_logFile = fopen($logFile, 'a');
        }

        if (!self::$_logFile) {
            throw new Exception("Can not open log file for writing {$logFile}");
        }

        $eol = strtoupper(self::getData('line_endings'));
        switch ($eol) {
            case 'LF':
                define('DAHBUG_EOL', "\n");
                break;

            case 'CR':
                define('DAHBUG_EOL', "\r");
                break;

            case 'CRLF':
                define('DAHBUG_EOL', "\r\n");
                break;

            default:
                throw new Exception("Unknown line ending: {$eol}");
                break;
        }

        $theme = self::getData('theme');
        self::_loadConfigFile('default.theme');
        if ($theme && $theme !== 'default') {
            self::_loadConfigFile($theme . '.theme');
        }

        self::getInstance();
    }

    /**
     * Loads config data from a json file and stores it in self::$_data
     *
     * @param string $filename
     * @static
     * @access protected
     * @return void
     */
    static protected function _loadConfigFile($filename)
    {
        $fullPath = self::getData('root') . DIRECTORY_SEPARATOR . $filename;

        if (file_exists($fullPath)) {
            $config = json_decode(file_get_contents($fullPath), true);
            if (!$config) {
                throw new Exception("Invalid json format in file: {$fullPath}");
            }
            foreach ($config as $index => $value) {
                self::setData($index, $value);
            }
        }
    }

    /**
     * Returns singleton instance of dahbug.
     *
     * @static
     * @access public
     * @return dahbug
     */
    static public function getInstance()
    {
        if (!self::$_instance) {
            self::$_instance = new dahbug;
        }

        return self::$_instance;
    }

    /**
     * Stores value into self::$_data array.
     *
     * @param mixed $key
     * @param mixed $value
     * @static
     * @access public
     * @return void
     */
    static public function setData($key, $value = null)
    {
        if (is_array($key)) {
            self::$_data = $key;
        } else if (is_array($value)) {
            foreach ($value as $k => $v) {
                self::setData($key . '/' . $k, $v);
            }
        } else {
            if (strpos($key, '/')) {
                $keyArr = explode('/', $key);
                $data = &self::$_data;
                foreach ($keyArr as $i => $k) {
                    if (is_array($data)) {
                        if (!isset($data[$k])) {
                            $data[$k] = array();
                        }
                        $data = &$data[$k];
                    }
                }

                $data = $value;
            } else {
                self::$_data[$key] = $value;
            }
        }
    }

    /**
     * Returns data from self::$_data.
     *
     * @param string $key
     * @static
     * @access public
     * @return mixed
     */
    static public function getData($key = '')
    {
        if ($key === '') {
            return self::$_data;
        }

        $data = self::$_data;
        $default = null;

        if (strpos($key, '/')) {
            $keyArr = explode('/', $key);
            foreach ($keyArr as $i => $k) {
                if ($k==='') {

                    return $default;
                }
                if (is_array($data)) {
                    if (!isset($data[$k])) {
                        return $default;
                    }
                    $data = $data[$k];
                } else {

                    return $default;
                }
            }

            return $data;
        }

        if (isset($data[$key])) {
            return $data[$key];
        }

        return $default;
    }

    /**
     * Prints a backtrace.
     *
     * @static
     * @access public
     * @return void
     */
    static public function backtrace()
    {
        $backtrace = debug_backtrace();
        $string = DAHBUG_EOL;
        $string .= "Printing callstack:" . DAHBUG_EOL;

        $root = $_SERVER['DOCUMENT_ROOT'];
        foreach ($backtrace as $row => $data) {
            $file = substr($data['file'], strlen($root));
            $args = array();
            foreach ($data['args'] as $arg) {
                if (is_string($arg)) {
                    if (strlen($arg) > 20) {
                        $arg = '...' . substr($arg, -20);
                    }
                    $arg = self::_colorize(
                        $arg,
                        'dump_string'
                    );
                    $args[] = "'{$arg}'";
                } else if (is_int($arg)) {
                    $args[] = self::_colorize(
                        $arg,
                        'dump_int'
                    );
                } else if (is_bool($arg)) {
                    $args[] = self::_colorize(
                        $arg ? 'true' : 'false',
                        'dump_bool'
                    );
                } else if (is_null($arg)) {
                    $args[] = self::_colorize(
                        'null',
                        'dump_null'
                    );
                } else if (is_float($arg)) {
                    $args[] = self::_colorize(
                        $arg,
                        'dump_float'
                    );
                } else {
                    $args[] = self::_colorize(
                        gettype($arg),
                        'dump_value'
                    );
                }
            }
            $class = '';
            if (isset($data['class'])) {
                $class = self::_colorize(
                    isset($data['object']) ? get_class($data['object']) : $data['class'],
                    'methods_class'
                );
            }

            $function = self::_colorize(
                $data['function'],
                'methods_function'
            );
            $string .= sprintf(
                '#%d %s:(%d) %s%s%s(%s)',
                $row,
                $file,
                $data['line'],
                $class,
                isset($data['type']) ? $data['type'] : '',
                $function,
                implode(', ', $args)
            );
            $string .= DAHBUG_EOL;
        }

        self::_write($string);
    }

    /**
     * Writes a new line.
     * 
     * @static
     * @access public
     * @return void
     */
    static public function nl()
    {
        self::_write(DAHBUG_EOL);
    }

    /**
     * Prints the methods of an object or a class, or the code of a method.
     *
     * @param mixed $object     The object or class to print.
     * @param string $method    If you specify method, it will print the code from it.
     * @static
     * @access public
     * @return void
     */
    static public function methods($object, $method = null)
    {
        if (is_object($object)) {
            $className = get_class($object);
        } else {
            $className = $object;
        }

        if (!class_exists($className)) {
            self::_write("{$className} is not a declared class.");

            return;
        }

        self::$_backtrace = debug_backtrace();
        self::_printFilename();

        $string = DAHBUG_EOL;
        if (self::getData('print_filename')) {
            $string .= str_pad(self::$_backtrace[0]['line'], 4);
        }

        if ($method && method_exists($className, $method)) {
            $refMethod = new ReflectionMethod($className, $method);
            $string .= self::_getMethodInfo($refMethod);
        } else {
            $label = self::_colorize(
                'class',
                'methods_class_label'
            );
            $mainClass = true;

            $classes = self::_getClassMethods($className);
            $string .= "Methods of {$className}" . DAHBUG_EOL;

            foreach ($classes as $class => $methods) {
                $string .= " {$label} ";
                $string .= self::_colorize(
                    $class,
                    ($mainClass === true) ? 'methods_class' : 'methods_class_extends'
                );
                $mainClass = false;
                $string .= DAHBUG_EOL;
                foreach ($methods as $method) {
                    $ref = new ReflectionMethod($class, $method);
                    $params = self::_getMethodParams($ref);
                    $string .= '    ';
                    $string .= self::_colorize(
                        $method,
                        'methods_function'
                    );
                    $string .= "({$params})" . DAHBUG_EOL;
                }
                $label = self::_colorize(
                    'extends',
                    'methods_extends_label'
                );
                $string .= DAHBUG_EOL;
            }
        }

        self::_write($string);
    }

    /**
     * Returns the code and the php doc from a ReflectionMethod.
     *
     * @param ReflectionMethod $method
     * @static
     * @access protected
     * @return string $string
     */
    static protected function _getMethodInfo(ReflectionMethod $method)
    {
        $string = '';
        $string .= "defined in class {$method->getDeclaringClass()->getName()}" . DAHBUG_EOL;
        $string .= "  file {$method->getFileName()}:{$method->getStartLine()}" . DAHBUG_EOL;
        $string .= "    {$method->getDocComment()}" . DAHBUG_EOL;
        $file = file($method->getFileName());
        $i = 0;
        $start = $method->getStartLine();

        /**
         * Search line by line backwards to find the word "function"
         */
        while (($start > 0) && (strpos($file[$start--], 'fuction') === null));

        $end = $method->getEndLine();
        for ($i = $start; $i < $end; $i++) {
            $string .= $file[$i];
        }

        return $string;
    }

    /**
     * Generates an array with class names as keys and an array of methods as values.
     *
     * @param string $className
     * @static
     * @access protected
     * @return array $classes
     */
    static protected function _getClassMethods($className)
    {
        $classes = array();
        if ($className) {
            $classes[$className] = get_class_methods($className);
            asort($classes[$className]);
            while ($parentClass = get_parent_class($className)) {
                $classes[$parentClass] = get_class_methods($parentClass);
                asort($classes[$parentClass]);
                $classes[$className] = array_diff($classes[$className], $classes[$parentClass]);
                $className = $parentClass;
            }
        }

        return $classes;
    }

    /**
     * Generates a string of method parameter declarations.
     *
     * @param ReflectionMethod $ref
     * @static
     * @access protected
     * @return string $params
     */
    static protected function _getMethodParams(ReflectionMethod $ref)
    {
        $params = array();
        foreach ($ref->getParameters() as $param) {
            $declaration = '';
            if ($param->isPassedByReference()) {
                $declaration .= '&';
            }
            $declaration .= '$' . $param->getName();
            $declaration = self::_colorize(
                $declaration,
                'methods_param'
            );
            if ($param->isOptional()) {
                $defaultValue = $param->getDefaultValue();
                if (is_array($defaultValue)) {
                    $value = self::_colorize(
                        'array()',
                        'dump_array'
                    );
                    $declaration .= " = {$value}";
                } else if (is_null($defaultValue)) {
                    $value = self::_colorize(
                        'null',
                        'dump_null'
                    );
                    $declaration .= " = {$value}";
                } else if (is_string($defaultValue)) {
                    $value = self::_colorize(
                        $defaultValue,
                        'dump_string'
                    );
                    $declaration .= " = '{$value}'";
                } else if (is_bool($defaultValue)) {
                    $value = self::_colorize(
                        $defaultValue ? 'true' : 'false',
                        'dump_bool'
                    );
                    $declaration .= " = {$value}";
                } else if (is_int($defaultValue)) {
                    $value = self::_colorize(
                        $defaultValue,
                        'dump_int'
                    );
                    $declaration .= " = {$value}";
                } else if (is_float($defaultValue)) {
                    $value = self::_colorize(
                        $defaultValue,
                        'dump_float'
                    );
                    $declaration .= " = {$value}";
                } else {
                    $declaration = gettype($defaultValue);
                }
            }
            $params[] = $declaration;
        }
        $params = implode(', ', $params);

        return $params;
    }

    /**
     * Prints a hex dump of a string.
     *
     * @param string $bin
     * @static
     * @access public
     * @return void
     */
    static public function hex($bin)
    {
        $string = DAHBUG_EOL;
        $header = '';
        $length = strlen($bin);
        $i = $r = 0;
        $cols = 16;
        $rows = $length / $cols;

        $string .= "Hex dump of {$length} byte:" . DAHBUG_EOL;
        $string .= DAHBUG_EOL;
        for ($i = 0; $i < $cols; $i++) {
            $header .= '0' . dechex($i);
            if ($i % 4 === 3) {
                $header .= '  ';
            } else {
                $header .= ' ';
            }
        }

        $string .= str_repeat(' ', 10) . $header . DAHBUG_EOL;

        for ($r = 0; $r < $rows; $r++) {
            $addr = dechex($r * $cols);
            $hex = '';
            $ascii = '';
            for ($i = 0; $i < $cols; $i++) {
                $offset = $r * $cols + $i;
                if ($offset >= $length) {
                    break;
                }
                $hex .= dechex(ord($bin[$offset]));
                if ($i % 4 === 3) {
                    $hex .= '  ';
                } else {
                    $hex .= ' ';
                }
            }
            for ($i = 0; $i < $cols; $i++) {
                $offset = $r * $cols + $i;
                if ($offset >= $length) {
                    break;
                }
                $ord = ord($bin[$offset]);
                if (($ord < 126) && ($ord > 31)) {
                    $ascii .= $bin[$offset];
                } else {
                    $ascii .= '.';
                }
                if ($i % 4 === 3) {
                    $ascii .= ' ';
                }
            }
            $string .= str_pad($addr, 8, '0', STR_PAD_LEFT) . '  ';
            $string .= str_pad($hex, $cols * 3 + $cols / 4);
            $string .= $ascii . DAHBUG_EOL;
        }

        self::_write($string);
    }

    /**
     * Dump function
     *
     * @param mixed $var        The variable to dump.
     * @param string $label     An optional label.
     * @param int $maxLevels    Max recursion depth when printing arrays.
     * @static
     * @access public
     * @return void
     */
    static public function dump($var, $label = null, $maxDepth = null)
    {
        self::$_backtrace = debug_backtrace();

        if (!is_int($maxDepth)) {
            $maxDepth = self::getData('max_depth');
        }

        if (self::getData('print_filename')) {
            self::_printFilename();
        }

        $label = self::_prepareLabel($label, 'label');
        $string = self::_formatVar($var, 0, $maxDepth);
        $string .= DAHBUG_EOL;
        self::_write($label . ' = ' . $string);

        return $var;
    }

    /**
     * Formats var and generates a string.
     *
     * @param mixed $var
     * @param int $recursion
     * @param int $maxDepth
     * @static
     * @access protected
     * @return string
     */
    static protected function _formatVar($var, $recursion = 0, $maxDepth = null)
    {
        if ($recursion === 0 && is_callable($var)) {
            $type = 'callable';
        } else {
            $type = gettype($var);
        }

        switch ($type) {
            case 'object':
                $string = sprintf('(object:%d) ', count((array)$var));
                $string .= self::_colorize(get_class($var), 'dump_classname');
                if ($recursion < 1) {
                    foreach ((array)$var as $key => $value) {
                        $string .= DAHBUG_EOL;
                        if (self::getData('print_filename')) {
                            $string .= '  ';
                        }
                        $string .= str_repeat(' ', ($recursion + 1) * self::getData('indent'));
                        $string .= self::_prepareLabel($key, 'key_property') . ' => ';
                        $string .= self::_formatVar($value, $recursion + 1, $maxDepth);
                    }
                }

                return $string;

            case 'boolean':
                $string = '(boolean) ';
                $string .= self::_colorize(
                    $var ? 'TRUE' : 'FALSE',
                    'dump_bool'
                );

                return $string;

            case 'string':
                $enc        = mb_detect_encoding($var, mb_list_encodings(), false);
                $length     = strlen($var);
                $stringCap  = self::getData('string_cap');
                $outEnc     = self::getData('output_encoding');

                if ($stringCap && $recursion === 0) {
                    if ($maxDepth > $stringCap) {
                        $stringCap = $maxDepth;
                    } elseif ($maxDepth === 0) {
                        $stringCap = 0;
                    }
                }


                $i = $len = 0;
                $string = '';
                for ($i = 0; $i < $length; $i++) {
                    $ord = ord($var[$i]);
                    if ($ord < 32) {
                        /**
                         * Get printable representation of ascii cc.
                         */
                        $string .= self::_underline(
                            self::_getAsciiCC($var[$i])
                        );
                        $len += 2;
                    } else {
                        $string .= $var[$i];
                        $len++;
                    }

                    if ($stringCap && $len > $stringCap) {
                        $string .= '...';
                        break;
                    }
                }

                $var = mb_convert_encoding($var, $outEnc, $enc);
                $string = self::_colorize(
                    $string,
                    'dump_string'
                );

                $length = mb_strlen($var, $enc);
                $label  = sprintf('(string:%d:%s) ', $length, $enc);
                $string = $label . sprintf(self::getData('string_format'), $string);

                return $string;

            case 'array':
                $string = self::_colorize(
                    $string = sprintf('(array:%d) ', count($var)),
                    'dump_array'
                );

                if ($recursion < $maxDepth) {
                    foreach ($var as $key => $value) {
                        $string .= DAHBUG_EOL;
                        if (self::getData('print_filename')) {
                            $string .= '  ';
                        }
                        $string .= str_repeat(' ', ($recursion + 1) * self::getData('indent'));
                        $string .= self::_prepareLabel($key) . ' => ';
                        $string .= self::_formatVar($value, $recursion + 1, $maxDepth);
                    }
                }

                return $string;

            case 'NULL':
                $string = self::_colorize(
                    '(NULL)',
                    'dump_null'
                );
                return $string;

            case 'integer':
                $string = self::_colorize(
                    $var,
                    'dump_int'
                );

                return "(int) {$string}";

            case 'double':
                $string = self::_colorize(
                    $var,
                    'dump_float'
                );

                return "(float) {$string}";

            case 'resource':
                $string = self::_colorize(
                    get_resource_type($var),
                    'dump_value'
                );

                return '(resource) ' . $string;

            case 'callable':
                $name = '';
                is_callable($var, false, $name);
                $string = self::_colorize(
                    $name,
                    'dump_value'
                );

                return '(callable) ' . $string;

            default:
                return 'Unknown Type';
        }
    }

    /**
     * Prints filename from where the dump function was called to the log file.
     * Requires the self::$_backtrace array to be set from debug_backtrace().
     *
     * @static
     * @access protected
     * @return void
     */
    static protected function _printFilename()
    {
        $backtrace = self::$_backtrace;
        $filename = $backtrace[0]['file'];

        if ($filename != self::$_lastFilename) {
            $string = 'In file ';
            $string .= $backtrace[0]['file'];
            $string .= DAHBUG_EOL;
            $string .= DAHBUG_EOL;

            self::$_lastFilename = $filename;
            self::_write($string);
        }
    }

    /**
     * Prepares the label of the dumped variable.
     * Requires the self::$_backtrace array to be set from debug_backtrace().
     *
     * @param mixed $label
     * @static
     * @access protected
     * @return string $label
     */
    static protected function _prepareLabel($label, $type = null)
    {
        $backtrace = self::$_backtrace;

        if ($label === null) {
            $file  = file($backtrace[0]['file']);
            $label = $file[$backtrace[0]['line']-1];
            $label = trim($label);
            $label = substr($label, strpos($label, 'dahbug') + 13);
            if (strpos($label, ',')) {
                $label = substr($label, 0, strpos($label, ','));
            } else {
                $label = substr($label, 0, strpos($label, ');'));
            }
        }

        if ($type) {
            $labelType = $type;
        } elseif (is_int($label)) {
            $labelType = 'key_int';
        } else {
            $labelType = 'key_string';
        }

        $label = self::_colorize(
            $label,
            $labelType
        );
        $label = sprintf(self::getData('label_format'), $label);

        if ($type == 'label' && self::getData('print_filename')) {
            $label = str_pad($backtrace[0]['line'], 4) . $label;
        }

        return $label;
    }

    /**
     * Formats a time given in micro seconds.
     *
     * @param float $value
     * @static
     * @access protected
     * @return string
     */
    static protected function _formatTime($value)
    {
        if ($value > 1) {
            $value = number_format($value, 2);
            $prefix = 's';
        } else if ($value > 0.001) {
            $value = number_format($value * 1000, 2);
            $prefix = 'ms';
        } else {
            $value = number_format($value * 1000000, 2);
            $prefix = 'µs';
        };

        return $value . ' ' . $prefix;
    }

    /**
     * Prints a string to the log file without formatting. If an object is passed
     * the __toString function will be called and the result used for printing.
     *
     * @param mixed  $var
     * @param string $encoding
     * @static
     * @access public
     * @return void
     */
    static public function write($var, $encoding = null)
    {
        $lineEndings        = strtoupper(self::getData('line_endings'));
        $outputEncoding     = self::getData('output_encoding');
        $encodings          = mb_list_encodings();
        $outEnc             = self::getData('output_encoding');

        if ($encoding && in_array($encoding, $encodings)) {
            $enc = $encoding;
        } else {
            $enc = mb_detect_encoding($var, $encodings, false);
        }

        if (is_object($var) && method_exists($var, '__toString')) {
            $var = $var->__toString();
        }

        switch ($lineEndings) {
            case 'LF':
                $var = str_replace(array("\r\n", "\r"), "\n", $var);
                break;
            case 'CR':
                $var = str_replace(array("\r\n", "\n"), "\r", $var);
                break;
            case 'CRLF':
                $var = str_replace(array("\r\n", "\n", "\r"), "\r\n", $var);
                break;
        }

        if ($enc != $outputEncoding) {
            $var = mb_convert_encoding($var, $outEnc, $enc);
        }

        self::_write($var . DAHBUG_EOL);
    }

    /**
     * Adds ANSI escape codes to colorize a string.
     *
     * @param string $string
     * @param string $color
     * @static
     * @access protected
     * @return string
     */
    static protected function _colorize($string, $color = null)
    {
        if (!self::getData('use_colors')) {
            return $string;
        }
        $color = self::getData('theme_colors/' . $color);

        if (preg_match('/^[0-2]\d\d$/', $color)) {
            $colorNumber = '38;5;' . $color;
        } else {
            $colorNumber = explode(';', self::getData('color/' . $color));
            $colorNumber[1] = '3' . $colorNumber[1];
            $colorNumber = implode(';', $colorNumber);
        }

        if (!$colorNumber) {
            return $string;
        }

        $string = "\033[{$colorNumber}m" . $string . "\033[39m";

        return $string;
    }

    /**
     * Adds ANSI escape codes to format a string with underline.
     *
     * @param string $string
     * @static
     * @access protected
     * @return string $string
     */
    static protected function _underline($string)
    {
        if (!self::getData('use_colors')) {
            return $string;
        }

        $string = "\033[4m" . $string . "\033[24m";

        return $string;
    }

    /**
     * Returns a printable ascii control character representation.
     *
     * @param string $chr
     * @static
     * @access protected
     * @return string
     */
    static protected function _getAsciiCC($chr)
    {
        $notation = self::getData('ascii_notation');
        $ord = ord($chr);
        switch ($notation) {
            case 'caret':
                $chr = '^' . chr($ord + 64);
                break;

            case 'escape':
                $charMap = self::getData('escape_chars');
                if (isset($charMap[$ord])) {
                    $chr = $charMap[$ord];
                } else {
                    $chr = '\?';
                }

                break;
        }

        return $chr;
    }

    /**
     * Writes a string to a stream opened with fopen.
     *
     * @param string $string
     * @static
     * @access protected
     * @return void
     */
    static protected function _write($string)
    {
        if (self::getData('output') == 'print') {
            echo $string;
            return;
        }

        $logFile = self::$_logFile;
        fwrite($logFile, $string);
    }

    /**
     * INSTANCE (NON-STATIC) DECLARATIONS
     */

    /**
     * The time when the request was started.
     *
     * @var float
     * @access protected
     */
    protected $_requestTime;

    /**
     * Class constructor.
     *
     * @access public
     * @return void
     */
    public function __construct()
    {
        if (isset($_SERVER['REQUEST_METHOD'])) {
            $string = $this->_getHttpDumpHeader();
        } else {
            $string = $this->_getCliDumpHeader();
        }
        $bg = self::getData('background');
        if ($bg) {
            if (preg_match('/^[0-2]\d\d$/', $bg)) {
                $bg = '48;5;' . $bg;
            } else {
                $bg = self::getData('color/' . $bg);
                $bg = explode(';', $bg);
                $bg = '4' . $bg[1];
            }
            self::write("\033[{$bg}m");
        }
        $string = self::_colorize($string, 'header');

        if (self::getData('print_timestamp')) {
            $timestampFormat  = self::getData('timestamp_format');
            $timestamp        = self::_colorize(date($timestampFormat), 'timestamp');
            $string           = "{$timestamp} {$string}";
        }

        self::_write($string . DAHBUG_EOL . DAHBUG_EOL);
        $this->_requestTime = microtime(true);
    }

    /**
     * Returns a string containing log header information to print when the script
     * is initiated by an HTTP request.
     *
     * @access protected
     * @return string
     */
    protected function _getHttpDumpHeader()
    {
        $string = '';
        if (isset($_SERVER['REMOTE_ADDR'])) {
            $string .= $_SERVER['REMOTE_ADDR'] . ' ';
        }
        if (isset($_SERVER['HTTP_HOST'])) {
            $string .= $_SERVER['HTTP_HOST'] . ' ';
        }
        if (isset($_SERVER['REQUEST_METHOD'])) {
            $string .= $_SERVER['REQUEST_METHOD'] . ' ';
        }
        if (isset($_SERVER['REQUEST_URI'])) {
            $string .= $_SERVER['REQUEST_URI'] . ' ';
        }

        return $string;
    }

    /**
     * Returns a string containing log header information when the script
     * is initiated by the cli.
     *
     * @access protected
     * @return string
     */
    protected function _getCliDumpHeader()
    {
        $string = $_SERVER['SCRIPT_NAME'];

        return $string;
    }

    /**
     * Returns a string with log footer information to print at the end of
     * the script's execution.
     *
     * @access protected
     * @return string
     */
    protected function _getRequestFooter()
    {
        $time = microtime(true) - $this->_requestTime;
        $string = DAHBUG_EOL;
        $string .= sprintf('Request processing time: %s   Memory Usage: %d Mb',
            self::_formatTime($time),
            memory_get_peak_usage(true) / (1024 * 1024)
        );

        $string = self::_colorize($string, 'footer');
        $string .= DAHBUG_EOL;
        $string .= DAHBUG_EOL;
        $string .= "\033[0m";

        return $string;
    }

    /**
     * Instance destructor. Prints log footer and other pending information.
     *
     * @access public
     * @return void
     */
    public function __destruct()
    {
        $string = $this->_getRequestFooter();
        self::_write($string . DAHBUG_EOL);

        if (self::$_logFile) {
            fclose(self::$_logFile);
        }
    }
}

dahbug::init();
