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
        if (preg_match_all($config['format'], $row, $matches)) {
            $output($matches);
        }
    }
};
