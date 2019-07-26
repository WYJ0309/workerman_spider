<?php
/**
 * 图片下载页面逻辑
 */
require_once './vendor/autoload.php';
require_once './vendor/workerman/workerman/Autoloader.php';
use Workerman\Worker;
use Workerman\Lib\Timer;
use Workerman\MySQL\Connection;


$worker = new Worker("http://0.0.0.0:4444");
$worker->count = 8;
$worker->onWorkerStart = function ($worker){
    global $db;
    $db = new Connection('127.0.0.1', '3306', 'root', 'root', 'workman_test');
    $dom = new DOMDocument();
    Timer::add(5,function ()use($db,$dom){
        $result = $db->select('*')->from('images')->where("status=0")->row();
        if(!empty($result)){
            $name = md5(time());
            $html = file_get_contents($result['img_url']);
            @$dom->loadHTML($html);
            $dom->normalize();
            $xpath = new DOMXPath($dom);
            $picNodes = $xpath->query('//div[@class="pic-meinv"]//img');
            $pic_url = $picNodes[0]->getAttribute('src');
            $nextNodes = $xpath->query('//div[@class="pic-meinv"]//a');
            $pic_next_url = $nextNodes[0]->getAttribute('href');
            $is_exist = $db->select('id')->from('images')->where('img_url= :img_url')->bindValues(array('img_url'=>$pic_next_url))->row();
            if(empty($is_exist)){
                $nextNodesImg = $xpath->query('//div[@class="pic-meinv"]//img');
                $db->insert('images')->cols(array('img_name'=>$nextNodesImg[0]->getAttribute('title'), 'img_url'=>$pic_next_url))->query();
            }
            $pic_source = file_get_contents($pic_url);
            file_put_contents("./images/".$name.$result['id'].'.jpg',$pic_source);
            $db->update('images')->cols(array('status'=>1))->where('id='.$result['id'])->query();
        }
    });
};
$worker->onMessage = function($connection, $data) {};

Worker::runAll();