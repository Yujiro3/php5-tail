<?php
/**
 * Parse処理
 *
 * @package         php5-tail
 * @subpackage      Method
 * @author          Yujiro Takahashi <yujiro3@gamil.com>
 */
return function ($config, $buff) {
    $list    = explode("\n", $buff);
    $matches = array();
    $output  = include $config['output'];

    foreach ($list as $row) {
        if (preg_match($config['format'], $row, $matches)) {
            unset ($matches[0]);
            foreach ($matches as $key => $value) {
                $values[$config['format_keys'][($key - 1)]] = $value;
            }
            $output($values);
        }
    }
};
