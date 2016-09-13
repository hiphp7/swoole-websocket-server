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
namespace WebSocketSwoole;

class WebSocketService {

    public    $server    = null;
    public    $timer_arr = [];    //该变量保存所有的定时器任务ID，每一个客户端可以通过$timer_arr[客户端ID]得到该客户端建立的所有定时器
    public $conf      = [
        'host'            => '0.0.0.0',
        'port'            => 9999,                                  //服务使用端口
        'worker_num'      => 2,                                     //启动worker进程数
        'task_worker_num' => 8,                                     //启动task进程数
        'is_daemonize'    => 0,                                     //是否后台运行：0-否，1-是
        'log_file'        => '/tmp/swoole_webSocket_server.log',    //日志文件路径
    ];

    public function __construct($config = []) {
        $this->conf = array_merge($this->conf, (array)$config);
    }

    /**
     * 服务器端运行webSocket的入口
     */
    public function run() {
        try {
            $this->server = new \swoole_websocket_server($this->conf['host'], $this->conf['port']);
            $this->server->set(
                [
                    'worker_num'      => $this->conf['worker_num'],
                    'task_worker_num' => $this->conf['task_worker_num'],
                    'daemonize'       => $this->conf['is_daemonize'],
                    'log_file'        => $this->conf['log_file'],
                ]
            );

            //注册方法列表
            $this->server->on('open', [$this, 'open']);
            $this->server->on('message', [$this, 'message']);
            $this->server->on('task', [$this, 'task']);
            $this->server->on('finish', [$this, 'finish']);
            $this->server->on('close', [$this, 'close']);

            $this->server->start();
        } catch (\Exception $e) {
            var_dump($e->getCode() . ':' . $e->getMessage());
        }

    }

    /**
     * 建立socket链接时，执行方法
     * @param $server
     * @param $request
     */
    public function open($server, $request) {
        $data = [
            'client_id' => $request->fd,
            'request'   => $request
        ];
        $this->doOpen($data);
    }

    /**
     * 发送消息时，执行方法
     * @param $server
     * @param $frame
     */
    public function message($server, $frame) {
        $data = [
            'client_id' => $frame->fd,
            'data'      => $frame->data,
            'frame'     => $frame,
        ];
        $this->doMessage($data);
    }

    /**
     * 执行具体任务
     * @param $server
     * @param $task_id
     * @param $from_id
     * @param $data
     */
    public function task($server, $task_id, $from_id, $data) {
        $data['task_id'] = $task_id;
        $data['from_id'] = $from_id;
        $this->doTask($data);
    }

    /**
     * 任务结果处理
     * @param $server    服务器对象
     * @param $taskId    任务进程ID
     * @param $data
     */
    public function finish($server, $taskId, $data) {
        $data['task_id'] = $taskId;
        $this->doFinish($data);
    }

    /**
     * 关闭链接
     * @param $server        服务器对象
     * @param $client_id     客户端唯一标识
     */
    public function close($server, $client_id) {
        $data = [
            'client_id' => $client_id
        ];
        $this->doClose($data);
    }

    /**
     * 客户端成功连接到服务器时，会触发该方法
     * 子类根据需求重写该方法
     * @param $data
     * [
     *    'client_id',    //客户端唯一标识
     *    'data',        //一些请求数据
     *    'frame',        //swoole的数据
     * ];
     */
    public function doOpen($data) {
    }

    /**
     * 收到客户端发来的消息，会触发该方法
     * 子类根据需求重写该方法
     * @param $data
     * [
     *    'client_id',    //客户端唯一标识
     *    'data',        //客户端发送过来的消息(数据)
     *    'frame',        //swoole的数据
     * ];
     */
    public function doMessage($data) {
    }

    /**
     * 具体的工作任务。需要通过 $this->doJob()来触发
     * 子类根据需求重写该方法
     * @param $data
     * [
     *    'client_id',    //客户端唯一标识
     * ];
     */
    public function doTask($data) {
    }

    /**
     * 工作的结果处理。
     * 子类根据需求重写该方法
     * @param $data
     * [
     *    'client_id',    //客户端唯一标识
     * ];
     */
    public function doFinish($data) {
    }

    /**
     * 客户端断开时会自动触发该方法
     * 子类根据需求重写该方法
     * @param $data
     * [
     *    'client_id',    //客户端唯一标识
     * ];
     */
    public function doClose($data) {
    }


    /**
     * 发送任务
     * @param $data    必须是数组，且要有$data['client_id']
     */
    public function doJob($data) {
        $this->server->task($data);
    }

    public function finishJob($data) {
        $this->server->finish($data);
    }

    /**
     * 发送消息到客户端
     * @param $client_id
     * @param $msg
     */
    public function sendMsgToClient($client_id, $msg) {
        echo "发送信息给客户端：{$client_id} | {$msg['action']['name']} | " . date('Y-m-d H:i:s') . "\r\n";
        $this->server->push($client_id, json_encode($msg));
    }

    /**
     * 关闭客户端链接
     * @param $client_id
     */
    public function closeClient($client_id) {
        $this->server->close($client_id);
    }

    /**
     * 添加定时器
     * @param $client_id
     * @param $tick_time
     */
    public function addTimer($client_id, $tick_time) {
        //暂未实现,先使用swoole原生写法
    }

    /**
     * 清除定时器
     * @param $client_id
     * @param $arr
     */
    public function clearTimer($client_id, &$arr) {
        if (is_array($arr)) {
            foreach ($arr[$client_id] as $val) {
                if (is_array($val)) {
                    foreach ($val as $v) {
                        swoole_timer_clear($v);
                    }
                } else {
                    swoole_timer_clear($val);
                }
            }
        }
        unset($arr[$client_id]);
    }

}