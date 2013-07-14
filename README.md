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

### libeventモジュールのインストール ###
    
    $ wget http://pecl.php.net/get/libevent-0.1.0.tgz
    $ tar xzvf libevent-0.1.0.tgz
    $ cd libevent-0.1.0
    $ phpize
    $ ./configure
    $ make
    $ sudo -s
    # make install
    # cd /etc/php5/mods-available
    # echo extension=libevent.so > libevent.ini
    # cd /etc/php5/conf.d
    # ln -s ../mods-available/libevent.ini ./20-libevent.ini


### クイックスタート ###
    
    $ sudo install
    $ sudo setup
    $ sudo service php5-tail start
    

### 設定ファイル ###

/etc/php5-tail/php5-tail.conf

```
;;;;;;;;;;;;;;;;;;;;;;;;;;;
; php5-tail Configuration ;
;;;;;;;;;;;;;;;;;;;;;;;;;;;

; Monitored file
path = /var/log/nginx/access.log

; Analysis format
format = '/^(?<host>\S*) \S* \S* \[(?<time>[^\]]*)\] "GET +(?<path>\S*) +\S*" 200 \S* "(?<referer>[^\"]*)" "(?<agent>[^\"]*)"$/'

; Log file path
log = /var/log/php5-tail.log

; File for saving position
pos_file = /etc/php5-tail/cache/access.pos

; The file name for the initialization function
initialize = /etc/php5-tail/method/format.php

; The file name for the action function
action = /etc/php5-tail/method/parse.php

; The file name for the output function
output = /etc/php5-tail/method/stdout.php
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
