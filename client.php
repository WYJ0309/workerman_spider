<?php
/**
 * 定时提交分页地址给服务端
 */
require_once './vendor/autoload.php';
require_once './vendor/workerman/workerman/Autoloader.php';
use Workerman\Worker;
use React\HttpClient\Client;
use React\HttpClient\Response;
use Workerman\MySQL\Connection;
use Workerman\Lib\Timer;

$worker = new Worker("http://0.0.0.0:3333");
$worker->onWorkerStart = function ($worker){
    global $db;
    $db = new Connection('127.0.0.1', '3306', 'root', 'root', 'workman_test');
    Timer::add(20,function ()use($db){
        $urlArr = $db->select('*')->from('page_url')->where("status= 0 ")->row();
        if(empty($urlArr)){
            //echo '任务结束...';
        }else{
            $json = json_encode(array('a_href'=>$urlArr['a_href']));
            $task_data = array(
                'Content-Type' => 'application/json',
                'Content-Length' => strlen($json)
            );
            $loop = Worker::getEventLoop();
            $client = new Client($loop);
            $request = $client->request('POST','http://127.0.0.1:2345',$task_data);
            $db->update('page_url')->cols(array('status'=>1))->where('id='.$urlArr['id'])->query();
            $request->on('response', function (Response $response) {
                $response->on('data', function ($chunk) {
                    echo $chunk;
                });
                $response->on('end', function () {
                    echo 'DONE' . PHP_EOL;
                });
            });
            $request->end($json);
        }
    });
};

Worker::runAll();