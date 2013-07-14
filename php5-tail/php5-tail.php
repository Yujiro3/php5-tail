#!/usr/bin/env php
<?php
/**
 * php5-tail本体
 *
 * PHP versions 5.3.x
 *
 *      php5-xmpp : https://github.com/Yujiro3/php5-php
 *      Copyright (c) 2011-2013 sheeps.me All Rights Reserved.
 *
 * @package         php5-tail
 * @copyright       Copyright (c) 2011-2013 sheeps.me
 * @author          Yujiro Takahashi <yujiro3@gmail.com>
 * @filesource
 */

/**
 * Tailクラス
 *
 * @package         php5-tail
 * @author          Yujiro Takahashi <yujiro3@gamil.com>
 */
class Tail {
    /**
     * ファイル監視の破棄用
     * IN_MOVE_SELF | IN_MOVED_FROM | IN_MOVED_TO | IN_DELETE | IN_DELETE_SELF
     * @const integer
     */
    const RM_WATCH = 3776;

    /**
     * 設定情報
     * @var array
     */
     public $config;

    /**
     * メソッド格納
     * @var function
     */
     protected $method;

    /**
     * 読み込み位置
     * @var integer
     */
     private $_offset;

    /**
     * I node 監視リソース
     * @var resource
     */
     private $_inotify;

    /**
     * ファイルハンドラー
     * @var resource
     */
     private $_handle;

    /**
     * プロセスファイル
     * @var string
     */
     private $_pidfile;

    /**
     * ユーザーID
     * @var integer
     */
     private $_uid;

    /**
     * グループID
     * @var integer
     */
     private $_gid;

    /**
     * 監視対象ディレクトリ
     * @var string
     */
     private $_dir;

    /**
     * 監視対象ファイル名
     * @var string
     */
     private $_name;

    /**
     * 監視ID格納
     * @var array
     */
     private $_watch;

    /**
     * ベースイベント
     * @var resource
     */
     private $_base;

    /**
     * イベント
     * @var resource
     */
     private $_event;

    /**
     * コンストラクタ
     *
     * @access public
     * @param integer 引数の数
     * @param array   引数の配列
     * @return void
     */
    public function __construct($argc, $argv) {
        $help = 'Usage: php5-tail [options]'."\n".
                '    -c, --config PATH   Config file path'."\n".
                '    -u, --user USER     Change user'."\n".
                '    -g, --group GROUP   Change group'."\n".
                '    -d, --daemo PATH    Process ID file path'."\n".
                '    -h, --help          This Help.'."\n";
        $this->_pidfile = '';
        $this->_pid = $this->_uid = $this->_gid = 0;

        if (isset($argc) && $argc > 1) {
            for ($pos=1; $pos < $argc; $pos++) {
                switch ($argv[$pos]) {
                case '-c':
                case '--config':
                    $this->_confile = $argv[++$pos];
                    break;
                case '-u':
                case '--user':
                    $user       = posix_getpwnam($argv[++$pos]);
                    $this->_uid = empty($user['uid']) ? posix_getpgrp() : $user['uid'];
                    break;
                case '-g':
                case '--group':
                    $grps        = posix_getgrnam($argv[++$pos]);
                    $this->_gid  = empty($grps['gid']) ? posix_getpgrp() : $grps['gid'];
                    break;
                case '-d':
                case '--daemon':
                    $this->_pidfile = $argv[++$pos];
                    break;
                default:
                    echo $help."\n";
                    exit(0);
                }
            }
        } else {
            echo $help."\n";
            exit(1);
        }

        $handler =  array(&$this, '_shutdown');    
        pcntl_signal(SIGINT,  $handler);
        pcntl_signal(SIGQUIT, $handler);
        pcntl_signal(SIGTERM, $handler);
        pcntl_signal(SIGTSTP, $handler);
        pcntl_signal(SIGHUP,  $handler);

        $this->_initialize();
    }

    /**
     * ファイル更新の監視
     *
     * @access public
     * @return array   設定リスト
     */
    public function watch() {
        declare(ticks = 1);

        event_add($this->_event);
        event_base_loop($this->_base);
    }

    /**
     * 初期設定、各種チェック
     *
     * @access private
     * @return array   設定リスト
     */
    private function _initialize() {
        ob_implicit_flush(true);
        gc_enable();

        if (!is_readable($this->_confile)) {
            throw new \Exception(sprintf("File '%s' does not exists or is not readable", $this->_confile));
        }
        $this->config = parse_ini_file($this->_confile);
        $this->_name  = basename($this->config['path']);
        $this->_dir   = dirname($this->config['path']);

        if (!empty($this->_pidfile)) {
            $this->_fork();

            if (!is_writable(dirname($this->_pidfile))) {
                throw new \Exception(sprintf("File '%s' does not writable", $this->_pidfile));
            }
            file_put_contents($this->_pidfile, posix_getpid());
        }
        posix_setuid($this->_uid);
        posix_setgid($this->_gid);

        if (!is_readable($this->config['initialize'])) {
            throw new \Exception(sprintf("File '%s' does not exists or is not readable", $this->config['initialize']));
        }
        if (!is_readable($this->config['action'])) {
            throw new \Exception(sprintf("File '%s' does not exists or is not readable", $this->config['action']));
        }
        
        $this->method = array(
            'initialize' => include $this->config['initialize'],
            'action'     => include $this->config['action']
        );

        if (!extension_loaded('inotify')) {
            throw new \Exception('Inotify extension not loaded !');
        }
        if (!extension_loaded('libevent')) {
            throw new \Exception('Libevent extension not loaded !');
        }

        if (!is_readable($this->_dir)) {
            throw new \Exception(sprintf("Directory '%s' does not exists", $this->_dir));
        }

        if (!is_writable(dirname($this->config['pos_file']))) {
            throw new \Exception(sprintf("Directory '%s' does not writable", dirname($this->config['pos_file'])));
        }

        if (file_exists($this->config['pos_file'])) {
            if (!is_writable($this->config['pos_file'])) {
                throw new \Exception(sprintf("File '%s' does not writable", $this->config['pos_file']));
            }
            $this->_offset = include $this->config['pos_file'];
            $this->_offset = intval($this->_offset);
        } else {
            if (!is_writable(dirname($this->config['pos_file']))) {
                throw new \Exception(sprintf("Directory '%s' does not writable", dirname($this->config['pos_file'])));
            }
            $this->_offset = 0;
        }

        if (file_exists($this->config['log'])) {
            if (!is_writable($this->config['log'])) {
                throw new \Exception(sprintf("File '%s' does not writable", $this->config['log']));
            }
        } else {
            if (!is_writable(dirname($this->config['log']))) {
                throw new \Exception(sprintf("Directory '%s' does not writable", dirname($this->config['log'])));
            }
        }

        $this->_inotify = inotify_init();
        if ($this->_inotify === false) {
            throw new \Exception('Failed to obtain an inotify instance');
        }

        $this->_watch['dir'] = inotify_add_watch($this->_inotify, $this->_dir, (IN_CREATE | IN_MOVED_TO));
        if ($this->_watch['dir'] === false) {
            throw new \Exception(sprintf("Failed to watch directory '%s'", $this->_dir));
        }

        $this->_base = event_base_new();
        if ($this->_base === false) {
            throw new \Exception('Failed to obtain an Event base instance');
        }

        $this->_event = event_new();
        if ($this->_event === false) {
            throw new \Exception('Failed to obtain an event instance');
        }

        @umask(0);

        $this->_open();
        
        $this->config = $this->method['initialize']($this->config);
    }

    /**
     * デーモン起動
     *
     * @access private
     * @return boolean 設定リスト
     */
    private function _fork() {
        $pid = pcntl_fork();
        if ($pid === -1) {
            throw new \Exception('Process could not be forked');
        } else if ($pid) {
            // Parent
            // echo 'Ending php5-tail parent process';
            exit();
        }

        // Child
        return true;
    }

    /**
     * ファイルオープン
     *
     * @access private
     * @return array   設定リスト
     */
    private function _open() {
        if (!is_readable($this->config['path']) || ($this->_handle = fopen($this->config['path'], 'r')) === false) {
            throw new \Exception(sprintf("File '%s' does not exists or is not readable", $this->config['path']));
        }

        fseek($this->_handle, $this->_offset, SEEK_SET);

        $this->_watch['file'] = inotify_add_watch($this->_inotify, $this->config['path'], (IN_MODIFY | self::RM_WATCH));
        if ($this->_watch['file'] === false) {
            throw new \Exception(sprintf("Failed to watch file '%s'", $this->config['path']));
        }
        event_set($this->_event, $this->_inotify, EV_READ | EV_PERSIST, array(&$this, '_event'));
        event_base_set($this->_event, $this->_base);

        return true;
    }

    /**
     * ファイルクローズ
     *
     * @access public
     * @return array   設定リスト
     */
    private function _close() {
        inotify_rm_watch($this->_inotify, $this->_watch['file']);
        fclose($this->_handle);
    }

    /**
     * イベント処理
     *
     * @access private
     * @param  resource $inotify  inotify インスタンス
     * @param  integer  $event    イベントID
     * @return array   設定リスト
     */
    private function _event($inotify, $event) {
        $events = inotify_read($inotify);

        foreach ($events as $row) {
            if ($row['wd'] === $this->_watch['dir']) {
                $this->_eventDir($row);
            } elseif ($row['wd'] === $this->_watch['file']) {
                $this->_eventFile($row);
            }
        }
    }

    /**
     * ディレクトリイベント処理
     *
     * @access private
     * @param  integer $event イベントID
     * @return array   設定リスト
     */
    private function _eventDir($event) {
        if ($event['name'] == $this->_name) {
            if ($event['mask'] & IN_CREATE) {
                $this->_offset = 0;
                $this->_open();
            } elseif ($event['mask'] & IN_MOVED_TO) {
                $this->_offset = filesize ($this->config['path']);
                $this->_open();
            }
        }
    }

    /**
     * ファイルイベント処理
     *
     * @access private
     * @param  integer $event イベントID
     * @return array   設定リスト
     */
    private function _eventFile($event) {
        if ($event['mask'] & IN_MODIFY) {
            $this->method['action']($this->config, stream_get_contents($this->_handle));
        } elseif ($event['mask'] & self::RM_WATCH) {
            inotify_rm_watch($this->_inotify, $this->_watch['file']);
        }
    }

    /**
     * 終了処理
     *
     * @access private
     * @param  integer $signo シグナルID
     * @return array   設定リスト
     */
    private function _shutdown($signo) {
         switch ($signo) {
         case SIGINT:   // 割り込み
         case SIGQUIT:  // 終了
         case SIGTERM:  // 正常終了
         case SIGTSTP:  // サスペンド
            event_base_loopexit($this->_base);

            if (!empty($this->_watch['file'])) {
                inotify_rm_watch($this->_inotify, $this->_watch['file']);
            }
    
            if (!empty($this->_handle)) {
                $this->_offset = ftell($this->_handle);
                fclose($this->_handle);
                file_put_contents($this->config['pos_file'], "<?php\nreturn {$this->_offset};\n");
            }
    
            if (!empty($this->_watch['dir'])) {
                inotify_rm_watch($this->_inotify, $this->_watch['dir']);
                fclose($this->_inotify);
            }
            posix_kill(posix_getpid(), SIGUSR1);
            exit();
        case SIGHUP:   // 再読み込み
            $this->config = include $this->config;
            $this->_name  = basename($this->config['path']);
            $this->_dir   = dirname($this->config['path']);
            $this->method = array(
                'initialize' => include $this->config['initialize'],
                'action'     => include $this->config['action']
            );
            $this->config = $this->method['initialize']($this->config);
            break;
        }
    }
} // class Tail

/**
 * 実行処理
 */
try {
    $tail = new Tail($argc, $argv);
    $tail->watch();
} catch (\Exception $exception) {
    if (empty($tail->config['log'])) {
        fprintf(STDERR, $exception->getMessage()."\n");
    } else {
        error_log(
            '-----------  '.date('Y-m-d H:i:s')."  --------------\n".
            $exception->getMessage()."\n".
            $exception->getTraceAsString()."\n", 
            3, 
            $tail->config['log']
        );
    }
}
