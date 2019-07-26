<?php
require './vendor/autoload.php';
use Workerman\Worker;
use Workerman\Lib\Timer;

//定义心跳间隔60s
define('HEART_BEAT_TIME',60);

// 创建一个Worker监听2345端口，使用http协议通讯
$worker = new Worker("http://0.0.0.0:2345");
// 启动4个进程对外提供服务
$worker->count = 4;
//设置Worker子进程启动时的回调函数，每个子进程启动时都会运行一次，总共会运行$worker->count次。
$worker->onWorkerStart = function ($worker){
    echo 'starting.....';
    if($worker->id === 0){
        Timer::add(100,function (){
           echo '多个worker进程，目前只有编号为0的才有输出此内容';
        });
    }
    //如果客户端超过55秒没有发送任何数据给服务端，则服务端认为客户端已经掉线，服务端关闭连接并触发onClose。
    Timer::add(1,function ()use($worker){
       $time_now = time();
       foreach ($worker->connections as $connection){
           // 有可能该connection还没收到过消息，则lastMessageTime设置为当前时间
           if(empty($connection->lastMessageTime)){
               $connection->lastMessageTime = $time_now;
               continue;
           }
           // 上次通讯时间间隔大于心跳间隔，则认为客户端已经下线，关闭连接
           if($time_now - $connection->lastMessageTime > HEART_BEAT_TIME){
               $connection->close();
           }
       }
    });
};
/**
 * 当客户端与Workerman建立连接时(TCP三次握手完成后)触发的回调函数。每个连接只会触发一次onConnect回调。
 * 注意：onConnect事件仅仅代表客户端与Workerman完成了TCP三次握手，这时客户端还没有发来任何数据，此时除了通过$connection->getRemoteIp()获得对方ip，没有其他可以鉴别客户端的数据或者信息，
 * 所以在onConnect事件里无法确认对方是谁。要想知道对方是谁，需要客户端发送鉴权数据，例如某个token或者用户名密码之类，在onMessage回调里做鉴权。
 * 由于udp是无连接的，所以当使用udp时不会触发onConnect回调，也不会触发onClose回调。
 * @param $connection
 */
$worker->onConnect = function ($connection){
    echo $connection->id;
    echo $connection->getRemoteIp()."\n";
};
/**
 * 当客户端的连接上发生错误时触发。
 * 目前错误类型有:
 * 1、调用Connection::send由于客户端连接断开导致的失败（紧接着会触发onClose回调） (code:WORKERMAN_SEND_FAIL msg:client closed)
 * 2、在触发onBufferFull后(发送缓冲区已满)，仍然调用Connection::send，并且发送缓冲区仍然是满的状态导致发送失败(不会触发onClose回调)(code:WORKERMAN_SEND_FAIL msg:send buffer full and drop package)
 * 3、使用AsyncTcpConnection异步连接失败时(紧接着会触发onClose回调) (code:WORKERMAN_CONNECT_FAIL msg:stream_socket_client返回的错误消息)
 * @param $connection
 * @param $code
 * @param $msg
 */
$worker->onError = function ($connection,$code,$msg){
    echo $connection->id;
    echo "$code $msg \n ";
};
//当客户端通过连接发来数据时(Workerman收到数据时)触发的回调函数
$worker->onMessage = function($connection, $data)
{
    //向浏览器发送数据
    $connection->send('hello world');
    //接收到客户端发送过来的数据
    print_r($data);
    $connection->lastMessageTime = time();
};
/**
 * 每个连接都有一个单独的应用层发送缓冲区，如果客户端接收速度小于服务端发送速度，数据会在应用层缓冲区暂存，如果缓冲区满则会触发onBufferFull回调。
 * 缓冲区大为TcpConnection::$maxSendBufferSize，默认值为1MB，可以为当前连接动态设置缓冲区大小
 * @param $connection
 */
$worker->onBufferFull = function ($connection){
    echo "bufferFull and do not send again\n";
    //设置当前连接发送缓冲区，单位字节
    $connection->maxSendBufferSize = 102400;
};
/**
 * 当客户端连接与Workerman断开时触发的回调函数。不管连接是如何断开的，只要断开就会触发onClose。每个连接只会触发一次onClose。
 * 注意：如果对端是由于断网或者断电等极端情况断开的连接，这时由于无法及时发送tcp的fin包给workerman，workerman就无法得知连接已经断开，也就无法及时触发onClose。这种情况需要通过应用层心跳来解决。workerman中连接的心跳实现参见这里。如果使用的是GatewayWorker框架，则直接使用GatewayWorker框架的心跳机制即可，参见这里。
 * 由于udp是无连接的，所以当使用udp时不会触发onConnect回调，也不会触发onClose回调。
 * @param $connection
 */
$worker->onClose = function ($connection){
    echo "connection closed \n";
};
// 运行worker
Worker::runAll();