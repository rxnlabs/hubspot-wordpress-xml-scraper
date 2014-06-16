<?php
require_once 'vendor/autoload.php';
date_default_timezone_set('America/New_York');

//wordpress xml example: https://wpcom-themes.svn.automattic.com/demo/theme-unit-test-data.xml
$client = new Artax\Client;
// paste in link where we can see all blog posts
$response = $client->request('http://blog.patriot-tech.com/blog/ctl/all-posts/');
phpQuery::newDocument($response->getBody());
$wordpress_xml = array();
$xml = new DOMDocument('1.0', 'utf-8');
$xml->formatOutput = true;
$root = $xml->createElement('rss');
$root->setAttribute('version','2.0');
$root->setAttributeNS('http://www.w3.org/2000/xmlns/','xmlns:excerpt','http://wordpress.org/export/1.2/excerpt/');
$root->setAttributeNS('http://www.w3.org/2000/xmlns/','xmlns:content','http://purl.org/rss/1.0/modules/content/');
$root->setAttributeNS('http://www.w3.org/2000/xmlns/','xmlns:wfw','http://wellformedweb.org/CommentAPI/');
$root->setAttributeNS('http://www.w3.org/2000/xmlns/','xmlns:dc','http://purl.org/dc/elements/1.1/');
$root->setAttributeNS('http://www.w3.org/2000/xmlns/','xmlns:wp','http://wordpress.org/export/1.2/');
$channel = $xml->createElement('channel');
$version = $xml->createElementNS('http://wordpress.org/export/1.2/','wp:wxr_version','1.2');
$author = $xml->createElementNS('http://wordpress.org/export/1.2/','wp:author','');
$channel->appendChild($version);
$channel->appendChild($author);
foreach(pq('div.item') as $post){
	$link_obj = pq($post)->find('h3 a');
	$url = $link_obj->attr('href');

	// go to the blog post URL and grab the content
	$blog_post = new Artax\Client;
	$blog_post_response = $blog_post->request($url);
	phpQuery::newDocument($blog_post_response->getBody());
	// grab the blog post title
	$title = pq('h3.title');
	$content = pq('div.post');
	$item = $xml->createElement('item');
	// get tags
	foreach(pq('a[rel="tag"]') as $tag){
		$create_tag = $xml->createElement('category');
		$create_tag->setAttribute('domain','post_tag');
		$create_tag->setAttribute('nicename',$tag->nodeValue);
		$tag_value = $xml->createCDATASection($tag->nodeValue);
		$create_tag->appendChild($tag_value);
		$item->appendChild($create_tag);
	}
	// date
	$date = pq('.byline');
	$date = str_replace('Posted on ','',$date->html());
	$date = preg_replace('/ @/', '', $date);
	$date = date("Y-m-d H:i", strtotime($date));
	$post_date = $xml->createElementNS('http://wordpress.org/export/1.2/','wp:post_date',$date);
	$date_gmt = $xml->createElementNS('http://wordpress.org/export/1.2/','wp:post_date_gmt',$date);
	
	// remove content
	$content->find('.byline')->remove();
	$content->find('.submissions')->remove();
	$content->find('.buttons')->remove();
	$content->find('.tags')->remove();
	$content->find('.title')->remove();
	
	// create the necessary xml nodes
	$title = $xml->createElement('title',$title->html());
	$status = $xml->createElementNS('http://wordpress.org/export/1.2/','description','');
	$content_value = $xml->createCDATASection(remove_html_comments($content->html()));
	$content = $xml->createElementNS('http://purl.org/rss/1.0/modules/content/','content:encoded','');
	$content->appendChild($content_value);
  // set the status of the blog posts
	$status = $xml->createElementNS('http://wordpress.org/export/1.2/','wp:status','publish');
	$post_type = $xml->createElementNS('http://wordpress.org/export/1.2/','wp:post_type','post');
	$item->appendChild($title);
	$item->appendChild($content);
	$item->appendChild($status);
	$item->appendChild($post_type);
	$item->appendChild($post_date);
	$item->appendChild($date_gmt);

	$channel->appendChild($item);
}
$root->appendChild($channel);
$xml->appendChild($root);
$xml->save('patriot-tech.xml');

// Remove unwanted HTML comments
function remove_html_comments($content = '') {
	return preg_replace('/<!--(.|\s)*?-->/', '', $content);
}
