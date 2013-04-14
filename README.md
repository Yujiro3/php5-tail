php5-tail PHPのTailデーモン
======================
php5-tail is a daemon implementation of "tail-F".

利用方法
------

### Inotifyモジュールのインストール ###
    
    $ wget http://pecl.php.net/get/inotify-0.1.6.tgz 
    $ tar xzvf inotify-0.1.6.tgz
    $ cd inotify-0.1.6
    $ phpize
    $ ./configure
    $ make
    $ sudo -s
    # make install
    # cd /etc/php5/mods-available
    # echo extension=inotify.so > inotify.ini
    # cd /etc/php5/conf.d
    # ln -s ../mods-available/inotify.ini ./20-inotify.ini
    

### クイックスタート ###
    
    $ sudo install
    $ sudo setup
    $ sudo service php5-tail start
    

### 設定ファイル ###

/etc/php5-tail/config.php

```php
<?php
return array(
    'path'       => 'tail 対象ファイル名',
    'pos_file'   => '最終読み込み位置ようファイル名',
    'format'     => 'ログの正規表現フォーマット',
    'log'        => 'ログファイル',
    'initialize' => '初期動作関数フィアル名',
    'action'     => '実動作関数フィアル名',
    'output'     => '出力関数ファイル名'
);
```

```php
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
```
    
### 出力関数 ###

/etc/php5-tail/method/stdout.php

```php
<?php
return function ($values) {
    print_r($values);
};
```

### 出力結果 ###
    
    php5-tail -c /etc/php5-tail/config.php
    
    Array
    (
        [host] => 192.168.196.1
        [time] => 14/Apr/2013:10:41:54 -0700
        [path] => /
        [referer] => -
        [agent] => Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.31 (KHTML, like Gecko) Chrome/26.0.1410.43 Safari/537.31
    )
    
    

ライセンス
----------
Copyright &copy; 2013 Yujiro Takahashi  
Licensed under the [MIT License][MIT].  
Distributed under the [MIT License][MIT].  

[MIT]: http://www.opensource.org/licenses/mit-license.php