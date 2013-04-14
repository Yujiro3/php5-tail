<?php
return array(
    'path'       => '/var/log/nginx/access.log',
    'pos_file'   => '/etc/php5-tail/cache/access.pos',
    'format'     => '/^(?<host>\S*) \S* \S* \[(?<time>[^\]]*)\] "GET +(?<path>\S*) +\S*" 200 \S* "(?<referer>[^\"]*)" "(?<agent>[^\"]*)"$/',
    'log'        => '/var/log/php5-tail.log',
    'initialize' => '/etc/php5-tail/method/format.php',
    'action'     => '/etc/php5-tail/method/parse.php',
    'output'     => '/etc/php5-tail/method/stdout.php'
);
