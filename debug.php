<?php
/**
 * 测试页面逻辑
 */
$html = file_get_contents('http://www.win4000.com/wallpaper_detail_159513.html');
$dom = new DOMDocument();
//从一个字符串加载HTML
@$dom->loadHTML($html);
//使该HTML规范化
$dom->normalize();
//用DOMXpath加载DOM，用于查询
$xpath = new DOMXPath($dom);
#获取所有的a标签的地址
$nodes = $xpath->query('//div[@class="ptitle"]//em');
$images_num = $nodes[0]->nodeValue;
$picNodes = $xpath->query('//div[@class="pic-meinv"]//img');
$pic_url = $picNodes[0]->getAttribute('src');
//echo $pic_url;
$nextNodes = $xpath->query('//div[@class="pic-meinv"]//a');
$pic_next_url = $nextNodes[0]->getAttribute('href');
$nextNodes = $xpath->query('//div[@class="pic-meinv"]//img');
$pic_next_url = $nextNodes[0]->getAttribute('title');
echo $pic_next_url;