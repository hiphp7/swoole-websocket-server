<?php
/**
 * websocket基础服务
 * 继承该类后，根据自己的业务需要重写部分或者全部的以下方法：
 *    doOpen       ：    建立链接执行方法
 *    doMessage    ：    发送消息执行方法
 *    doTask       ：    执行具体任务执行方法
 *    doFinish     ：    处理任务结果执行方法
 *    doClose      ：    关闭链接执行方法
 * 运行方法          ：    Cli下执行run()方法
 * ！！该服务依赖于swoole扩展！！
 * @author xuanskyer | <furthestworld@icloud.com>
 * @time   2016-9-13 10:56:41
 */

use Xuanskyer\SwooleWebSocket\WebSocketService;

class DemoService extends WebSocketService {

    public    $server    = null;
    public    $timer_arr = [];    //该变量保存所有的定时器任务ID，每一个客户端可以通过$timer_arr[客户端ID]得到该客户端建立的所有定时器
    protected $setting   = [
        'host'            => '0.0.0.0',
        'port'            => 9999,                                  //服务使用端口
        'worker_num'      => 2,                                     //启动worker进程数
        'task_worker_num' => 8,                                     //启动task进程数
        'is_daemonize'    => 0,                                     //是否后台运行：0-否，1-是
        'log_file'        => '/tmp/swoole_webSocket_server.log',    //日志文件路径
    ];

    public function __construct() {
        parent::__construct($this->setting);
    }

    /**
     * 客户端成功连接到服务器时，会触发该方法
     * 子类根据需求重写该方法
     * @param $data
     *    [
     *    'client_id',    //客户端唯一标识
     *    'data',        //一些请求数据
     *    'frame',        //swoole的数据
     *    ];
     */
    public function doOpen($data) {
    }

    /**
     * 收到客户端发来的消息，会触发该方法
     * 子类根据需求重写该方法
     * @param $data
     *    [
     *    'client_id',    //客户端唯一标识
     *    'data',        //客户端发送过来的消息(数据)
     *    'frame',        //swoole的数据
     *    ];
     */
    public function doMessage($data) {
    }

    /**
     * 具体的工作任务。需要通过 $this->doJob()来触发
     * 子类根据需求重写该方法
     * @param $data
     *    [
     *    'client_id',    //客户端唯一标识
     *    ];
     */
    public function doTask($data) {
    }

    /**
     * 工作的结果处理。
     * 子类根据需求重写该方法
     * @param $data
     *    [
     *    'client_id',    //客户端唯一标识
     *    ];
     */
    public function doFinish($data) {
    }

    /**
     * 客户端断开时会自动触发该方法
     * 子类根据需求重写该方法
     * @param $data
     *    [
     *    'client_id',    //客户端唯一标识
     *    ];
     */
    public function doClose($data) {
    }


}