<?php
require_once './php5-tail/php5-tail';

/**
 * Tailテストクラス
 *
 * @package         Tail
 * @subpackage      Library
 * @author          Yujiro Takahashi <yujiro3@gamil.com>
 */
class TailTest extends PHPUnit_Framework_TestCase {
    /**
     * Tail
     * @var object
     */
    protected $tail;

    /**
     * コンストラクタ
     *
     * @access public
     * @return void
     */
    public function __construct() {
        $this->tail = new Tail(9, array(
            '--daemon', '/var/run/php5-tail.pid',
            '--user',   'root',
            '--group',  'root',
            '--config', '/etc/php5-tail/config.php',
        ));
    }

    /**
     * watchPut
     *
     * @access public
     * @return void
     */
    public function watchPut() {
        $tail->watch();
    }
} // class TailTest extends PHPUnit_Framework_TestCase
