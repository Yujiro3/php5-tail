<?php
/**
 * フォーマット
 *
 * @package         php5-tail
 * @subpackage      Method
 * @author          Yujiro Takahashi <yujiro3@gamil.com>
 */
return function ($config) {
    $keys = array();

    $config['format'] = preg_replace_callback (
        '/\(\?<([^>]+)>/i', 
        function ($matches) use (&$keys) {
            $keys[] = $matches[1];
            return '(';
        }, 
        $config['format']
    );
    $config['format_keys'] = $keys;

    return $config;
};
