<?php
/**
 * フォーマット
 *
 * @package         php5-tail
 * @subpackage      Method
 * @author          Yujiro Takahashi <yujiro3@gamil.com>
 */
return function ($config) {
    $config['format'] = str_replace('(?<', '(?P<', $config['format']);

    return $config;
};
