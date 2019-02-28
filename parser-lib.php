<?php

// Performance detection. 'mt' mean 'microtime'
$mt = 0; function mt(){$m = microtime();list($mc, $s) = explode(' ', $m); $n = $s + $mc; $ret = $n - $GLOBALS['last']; $GLOBALS['last'] = $n; return $ret;} mt();

// Memory usage detection
$mu = 0; function mu(){$m = memory_get_usage(); $ret = $m - $GLOBALS['mu']; $GLOBALS['mu'] = $m; return number_format($ret);} mu();

// Memory peak usage detection
$mpu = 0; function mpu(){$m = memory_get_peak_usage(); $ret = $m - $GLOBALS['mpu']; $GLOBALS['mpu'] = $m; return number_format($ret);} mpu();

/**
 * Flush the json-encoded message, containing `status` property, and other optional properties
 *
 * @param $success
 * @param mixed $msg1
 * @param mixed $msg2
 * @param bool $die
 */
function jflush($success, $msg1 = null, $msg2 = null, $die = true) {

    // Start building data for flushing
    $flush = is_array($success) && array_key_exists('success', $success) ? $success : array('success' => $success);

    // Deal with first data-argument
    if (func_num_args() > 1 && func_get_arg(1) != null)
        $mrg1 = is_object($msg1)
            ? (in('toArray', get_class_methods($msg1)) ? $msg1->toArray() : (array) $msg1)
            : (is_array($msg1) ? $msg1 : array('msg' => $msg1));

    // Deal with second data-argument
    if (func_num_args() > 2 && func_get_arg(2) != null)
        $mrg2 = is_object($msg2)
            ? (in('toArray', get_class_methods($msg2)) ? $msg2->toArray() : (array) $msg2)
            : (is_array($msg2) ? $msg2 : array('msg' => $msg2));

    // Merge the additional data to the $flush array
    if ($mrg1) $flush = array_merge($flush, $mrg1);
    if ($mrg2) $flush = array_merge($flush, $mrg2);

    // Send headers
    if (!headers_sent()) {

        // Send '400 Bad Request' status code if user agent is not IE
        if ($flush['success'] === false) header('HTTP/1.1 400 Bad Request');

        // Send '200 OK' status code
        if ($flush['success'] === true) header('HTTP/1.1 200 OK');

        // Send content type
        header('Content-Type: application/json');
    }

    // Replace 'success' key with 'status' key
    $flush = ['status' => $flush['success']] + $flush; unset($flush['success']);

    // Flush json
    echo json_encode($flush);

    // Exit if need
    if ($die) iexit();
}

/**
 * @param $msg
 */
function iexit($msg = null) {

    // Exit
    exit($msg);
}

/**
 * Parser function. Get array of content between strings, specified by $since arg and strings specified by $until arg
 * Both $since and until args can be regular expressions
 *
 * @param string $since
 * @param string $until
 * @param string $html
 * @return array
 */
function between($since, $until, $html) {

    // Regular expression to detect regulat expression
    $rex = '/^(\/|#|\+|%|~)[^\1]*\1[imsxeu]*$/';

    // Detect whether $since and/or $until args are regular expressions
    $splitFn_since = preg_match($rex, $since) ? 'preg_split' : 'explode';
    $splitFn_until = preg_match($rex, $until) ? 'preg_split' : 'explode';

    // Collect items
    $itemA = array();
    foreach ($splitFn_since($since, $html) as $i => $_)
        if ($i) $itemA []= array_shift($splitFn_until($until, $_));

    // Return collected
    return $itemA;
}

/**
 * Parser function. Get inner html for each node that match regular expression given in $node arg.
 * If aim is to pick inner html of multiple nodes, all those nodes should be located at same level
 * of nesting within html-tags tree, given by $html arg.
 *
 * Note: this function rely on that all pair tags are closed, for example each '<p>' tag should closed, e.g have '</p>'
 *
 * @param string $node Regular expression. For example: '~<div id="results">~' or '~<span class="[^"]*item-info[^"]*">~'
 * @param string $html Raw html to search in
 * @return array
 */
function innerHtml($node, $html) {

    // Split
    $chunkA = preg_split($node, $html);

    // If nothing found - return
    if (($chunkQty = count($chunkA)) < 2) return;

    // Ignore non-pair tags while watching on tag nesting levels
    $ignore = array_flip(array(
        'img', 'link', 'meta', 'input', 'br', 'hr', 'base', 'basefont', 'source', 'col', 'embed', 'area', 'param', 'track'
    ));

    // Regular expression for searching tags (opening an closing)
    $rex = '~(</?[a-zA-Z-0-9-:]+(?(?= ) [^>]*|)>)~';

    // Initial nesting level
    $level = 0;

    // Find tags before target node
    if (!preg_match_all($rex, $chunkA[0], $m)) return;

    // Foreach tag, found before target node
    foreach (array_shift($m) as $idx => $tag) {

        // If it's non-pair tag - skip
        if (isset($ignore[$m[1][$idx]])) continue;

        // Current level
        $level += substr($tag, 1, 1) == '/' ? -1 : 1;
    }

    // Increment current level to respect target node
    $level ++;

    // Remember target level
    $targetLevel = $level;

    // Array for inner html of found nodes
    $innerHtml = array();

    // Foreach chunk since 2nd
    for ($i = 1; $i < $chunkQty; $i++) {

        // Reset level, and increment it level because we'll be processing inner html
        $level = $targetLevel + 1; unset($prevDir);

        // Split html, that appear after target node's opening tag, and capture tags and offsets
        foreach(preg_split($rex, $chunkA[$i], -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_OFFSET_CAPTURE) as $chunk) {

            // If chunk is not a tag - skip
            if (!preg_match('~^</?([a-zA-Z-0-9-]+)~', $chunk[0], $m)) continue;

            // If it is a tag, but is a non-pair tag - skip
            if (isset($ignore[$m[1]])) continue;

            // Setup level change direction
            $dir = substr($chunk[0], 1, 1) == '/' ? -1 : 1;

            // If direction equal to previous one - apply it
            if (isset($prevDir) && $prevDir == $dir) $level += $dir;

            // If we finally went back to target level
            if ($level == $targetLevel) {

                // Get offset
                $pos = $chunk[1];

                // Stop loop
                break;
            }

            // Debug
            // echo str_pad($level, '3', '0', STR_PAD_LEFT) . str_pad('', $level - $targetLevel, ' ', STR_PAD_LEFT) . $chunk[0] . "\n";

            // Set previous direction
            $prevDir = $dir;
        }

        // Return target node inner html
        $innerHtml []= mb_substr($chunkA[$i], 0, $pos, 'utf-8');
    }

    // Return inner html of all matched nodes
    return $innerHtml;
}

/**
 * Get array, containing outer html of root nodes, found within raw html, given by $innerHtml arg
 *
 * @param string $innerHtml
 * @param bool $debug
 * @return array
 */
function rootNodes($innerHtml, $debug = false) {

    // Ignore non-pair tags while watching on tag nesting levels
    $ignoreRex = '~^<(' . implode('|', array(
        'img', 'link', 'meta', 'input', 'br', 'hr', 'base', 'basefont', 'source', 'col', 'embed', 'area', 'param', 'track'
    )) . ')~';

    // Regular expression for searching tags (opening an closing)
    $rex = '~(</?[a-zA-Z-0-9-:]+(?(?= ) [^>]*|)>)~u';

    // Remove raw javascript
    $innerHtml = str_replace(between('~<script[^>]*>~', '</script>', $innerHtml), '', $innerHtml);

    // Remove raw css
    $innerHtml = str_replace(between('~<style[^>]*>~', '</style>', $innerHtml), '', $innerHtml);

    // Initial nesting level
    $level = 0;

    // Root nodes array
    $rootNodes = array();

    // Split html, that appear after target node's opening tag, and capture tags and offsets
    foreach($s = preg_split($rex, $innerHtml, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_OFFSET_CAPTURE) as $chunk) {

        // If chunk is not a tag, or is a non-pair tag - skip
        if (!preg_match($rex, $chunk[0], $m) || preg_match($ignoreRex, $chunk[0])) {

            // Remember last chunk's offset
            $until = $chunk[1];

            // Goto next chunk
            continue;
        }

        // Setup level change direction
        $dir = substr($chunk[0], 1, 1) == '/' ? -1 : 1;

        // If direction equal to previous one - apply it
        if (isset($prevDir) && $prevDir === $dir) $level += $dir;

        // Debug
        if ($debug) str_pad($level, '3', '0', STR_PAD_LEFT) . str_pad('', $level, ' ', STR_PAD_LEFT) . $chunk[0] . "\n";

        // If we finally went back to target level
        if ($level == 0) {

            // If current chunk is a closing tag - get root node outer html
            if ($dir == -1) $rootNodes []= substr($innerHtml, $since, $until - $since) . $chunk[0];

            // Else if current chunk is an opening tag - remember offset
            else $since = $chunk[1];
        }

        // Remember last chunk's offset
        $until = $chunk[1];

        // Set previous direction
        $prevDir = $dir;
    }

    // Return root nodes
    return $rootNodes;
}

/**
 * Call preg_match() using given $rex pattern and $subject
 *
 * @static
 * @param $rex
 * @param $subject
 * @param null $sub If regular expression contains submask(s), $sub arg can be used as
 *                  a way to specify a submask index, that you need to pick the value at
 * @return array|null|string
 */
function rexm($rex, $subject, $sub = null){

    // Match
    preg_match($rex, $subject, $found);

    // Return
    return $found ? (func_num_args() == 3 ? $found[$sub] : $found) : $found;
}

/**
 * Custom handler for php errors, except E_NOTICE and E_DEPRECATED
 *
 * @param null $type
 * @param null $message
 * @param null $file
 * @param null $line
 * @return mixed
 */
function ehandler($type = null, $message = null, $file = null, $line = null) {

    // If arguments are given, we assume that we are here because of
    // a set_error_handler() usage, e.g current error is not a fatal error
    if (func_num_args()) {

        // If current error is not in a list of ignored errors - return
        if(!(error_reporting() & $type)) return;

        // Else if argument are not given, we assume that we are here because
        // of a register_shutdown_function() usage, e.g current error is a fatal error
    } else {

        // Get the fatal error
        $error = error_get_last();

        //if ($error !== null && $error["type"] != E_NOTICE && $error["type"] != E_DEPRECATED) extract($error);
        if ($error === null || in($error['type'], array(E_NOTICE, E_DEPRECATED))) return;

        // Extract error info
        extract($error);
    }

    // Flush json-encoded error info, wrapped by <error> tag
    echo jerror($type, $message, $file, $line);
}

/**
 * Build and return a string, containing json-encoded error info, wrapped with
 * '<error>' tag, for error to be easy pickable with javascript
 *
 * @param $errno
 * @param $errstr
 * @param $errfile
 * @param $errline
 * @return string
 */
function jerror($errno, $errstr, $errfile, $errline) {

    // Build an array, containing error information
    $error = array(
        'code' => $errno,
        'text' => $errstr,
        'file' => $errfile,
        'line' => $errline,
        'trace' => array_slice(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS), 2)
    );

    // Send HTTP 500 code
    if (!headers_sent()) header('HTTP/1.1 500 Internal Server Error');

    // Return that info via json encode, wrapped with '<error>' tag, for error to be easy pickable with javascript
    jflush(false, ['error' => $error]);
}

/**
 * Shortcut for in_array() function, but takes $array argument not only as array, but as a string also.
 * In that case $array argument will be converted to array by splitting by comma.
 *
 * @param $item
 * @param $array
 * @return boolean
 */
function in($item, $array) {

    // If $array arg is bool or is null, or $item arg is bool - set $strict flag as true
    $strict = is_bool($array) || is_null($array) || is_bool($item);

    // Normalize $array arg
    $array = ar($array);

    // Return
    return in_array($item, $array, $strict);
}

/**
 * Comma-separated values to array converter
 *
 * @param $items
 * @param $allowEmpty - If $items arg is an empty string, function will return an array containing that empty string
 *                      as a first item, rather than returning empty array
 * @return array
 */
function ar($items, $allowEmpty = false) {

    // If $items arg is already an array - return it as is
    if (is_array($items)) return $items;

    // Else if $items arg is strict null - return array containing that null as a first item
    if ($items === null) return $allowEmpty ? array(null) : array();

    // Else if $items arg is a boolean value - return array containing that boolean value as a first item
    if (is_bool($items)) return array($items);

    // Else if $items arg is an object we either return result of toArray() call on that object,
    // or return result, got by php's native '(array)' cast-prefix expression, depending whether
    // or not $items object has 'toArray()' method
    if (is_object($items)) return in_array('toArray', get_class_methods($items)) ? $items->toArray(): (array) $items;

    // Else we assume $items is a string and return an array by comma-exploding $items arg
    if (is_string($items)) {

        // If $items is an empty string - return empty array
        if (!strlen($items) && !$allowEmpty) return array();

        // Explode $items arg by comma
        foreach ($items = explode(',', $items) as $i => $item) {

            // Convert strings 'null', 'true' and 'false' items to their proper types
            if ($item == 'null') $items[$i] = null;
            if ($item == 'true') $items[$i] = true;
            if ($item == 'false') $items[$i] = false;
        }

        // Return normalized $items
        return $items;
    }

    // Else return array, containing $items arg as a single item
    return array($items);
}

// Set up error handlers for fatal errors, and other errors
register_shutdown_function('ehandler');
set_error_handler('ehandler');