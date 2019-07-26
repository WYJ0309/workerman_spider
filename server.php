<?php
/**
 * 接收客户端发送过来的页面链接，解析页面内容，获取有用信息
 * 存入数据表中
 */
require_once './vendor/autoload.php';
require_once './vendor/workerman/workerman/Autoloader.php';
use Workerman\Worker;
use Workerman\MySQL\Connection;

$worker = new Worker("http://0.0.0.0:2345");
$worker->count = 4;
$worker->onWorkerStart = function ($worker){
    global $db;
    $db = new Connection('127.0.0.1', '3306', 'root', 'root', 'workman_test');
    $html = file_get_contents('http://www.win4000.com/zt/sifang.html');
    $dom = new DOMDocument();
    //从一个字符串加载HTML
    @$dom->loadHTML($html);
    //使该HTML规范化
    $dom->normalize();
    //用DOMXpath加载DOM，用于查询
    $xpath = new DOMXPath($dom);
    #获取所有的a标签的地址
    $nodes = $xpath->query('//ul[@class="clearfix"]//a');
    $pages = $xpath->query('//div[@class="pages"]//a');
    //获取当前页面的图片详情地址
    foreach($nodes as $node)
    {
        $href =  $node->getAttribute ('href');
        $is_exist = $db->select('id')->from('images')->where('img_url= :img_url')->bindValues(array('img_url'=>$href))->row();
        if(!empty($is_exist)){
            continue;
        }
        if(strpos($href,'wallpaper_detail') !==false ){
            $db->insert('images')->cols(array('img_name'=>$node->getAttribute('title'), 'img_url'=>$href,))->query();
        }
    }
    //获取分页数据地址
    foreach($pages as $page_url)
    {
        $href =  $page_url->getAttribute ('href');
        $page_url_exist = $db->select('id')->from('page_url')->where('a_href= :a_href')->bindValues(array('a_href'=>$href))->row();
        if(!empty($page_url_exist)){
            continue;
        }
        if(strpos($href,'zt/sifang') !==false ){
            $db->insert('page_url')->cols(array('a_href'=>$href))->query();
        }
    }
};
//当客户端通过连接发来数据时(Workerman收到数据时)触发的回调函数
$worker->onMessage = function($connection, $data)
{
    $url = $data['post']['a_href'];
    $connection->send($url.'process is response...');
    global $db;
    $html = file_get_contents($url);
    $dom = new DOMDocument();
    //从一个字符串加载HTML
    @$dom->loadHTML($html);
    //使该HTML规范化
    $dom->normalize();
    //用DOMXpath加载DOM，用于查询
    $xpath = new DOMXPath($dom);
    #获取所有的a标签的地址
    $nodes = $xpath->query('//ul[@class="clearfix"]//a');
    $pages = $xpath->query('//div[@class="pages"]//a');
    //获取当前页面的图片详情地址
    foreach($nodes as $node)
    {
        $href =  $node->getAttribute ('href');
        $is_exist = $db->select('id')->from('images')->where('img_url= :img_url')->bindValues(array('img_url'=>$href))->row();
        if(!empty($is_exist)){
            continue;
        }
        if(strpos($href,'wallpaper_detail') !==false ){
            $db->insert('images')->cols(array('img_name'=>$node->getAttribute('title'), 'img_url'=>$href,))->query();
        }
    }
    //获取分页数据地址
    foreach($pages as $page_url)
    {
        $href =  $page_url->getAttribute ('href');
        $page_url_exist = $db->select('id')->from('page_url')->where('a_href= :a_href')->bindValues(array('a_href'=>$href))->row();
        if(!empty($page_url_exist)){
            continue;
        }
        if(strpos($href,'zt/sifang') !==false ){
            $db->insert('page_url')->cols(array('a_href'=>$href))->query();
        }
    }
};

Worker::runAll();