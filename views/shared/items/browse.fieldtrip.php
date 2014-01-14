<?php
// Extend SimpleXMLElement to more easily use CDATA
// http://stackoverflow.com/questions/6260224/how-to-write-cdata-using-simplexmlelement
class SimpleXMLExtended extends SimpleXMLElement {
  public function addCData($cdata_text) {
    $node = dom_import_simplexml($this); 
    $no   = $node->ownerDocument; 
    $node->appendChild($no->createCDATASection($cdata_text)); 
  } 
}

// create simplexml object
$xml = new SimpleXMLExtended('<rss version="2.0" xmlns:fieldtrip="http://www.fieldtripper.com/fieldtrip_rss" xmlns:georss="http://www.georss.org/georss"></rss>');

// add channel information
$xml->addChild('channel');
$xml->channel->addChild('title', option('site_title'));
$xml->channel->addChild('link', WEB_ROOT);
$desc=get_option('srss_about_text') ? get_option('srss_about_text') : (option('description') ? option('description') : "Description is unavailable.");
$xml->channel->addChild('description', $desc );
if(get_option('srss_image_url')!=null){
	$xml->channel->addChild('image',get_option('srss_image_url'));
}
$xml->channel->addChild('pubDate', date(DateTime::RSS));
if(option('administrator_email')!=null){
	$xml->channel->addChild('managingEditor',option('administrator_email').' ('.option('site_title').')');
}

// get feed item data
foreach( loop( 'items' ) as $omeka_item ) {
	// add item element for each article
	$feed_item = $xml->channel->addChild('item');

	// Get the entry data
	$title=  metadata( $omeka_item, array( 'Dublin Core', 'Title' ) ) ?
		metadata( $omeka_item, array( 'Dublin Core', 'Title' ) ) :
		'No title';

	$url = WEB_ROOT.'/items/show/'.$omeka_item->id;

	$srss_media_info=srss_media_info($omeka_item);
	
	$continue_link='<p><em>'.__('For more%s, view the original article.',$srss_media_info['stats_link']).'</em></p>';

	$content='';
	$content .= metadata( $omeka_item, array( 'Dublin Core', 'Description' )) ?
		metadata( $omeka_item, array( 'Dublin Core', 'Description' )) :
		'No content';
	$content=srss_br2p($content).$continue_link;

	// Build the feed item
	$feed_item->title=null;
	$feed_item->title->addCData($title);
	$feed_item->addChild('guid',WEB_ROOT.'items/show/'.$omeka_item->id);
	$feed_item->description=null;
	$feed_item->description->addCData($content);
	$feed_item->addChild('link', $url);
	$feed_item->addChild('pubDate', gmdate(DATE_RSS, strtotime($omeka_item->added )) );
	if($point=srss_GeoRSSPoint($omeka_item)){
		$feed_item->addChild('point', $point, 'http://www.georss.org/georss');
	}
	if($img_src=$srss_media_info['hero_img']['src']){

		$feed_item_image = $feed_item->addChild('image', '', 'http://www.fieldtripper.com/fieldtrip_rss');

		// fieldtrip:image requires non-namespaced children
		// the leaves a non-validating xmlns="" for W3
		$url=$feed_item_image->addChild('url',$img_src,''); 


		if($img_caption=strip_tags($srss_media_info['hero_img']['title'])){
			$feed_item_image->addChild('title',$img_caption,'');
		}
	}

}

// output xml
header('Content-Type: application/xhtml+xml');
echo $xml->asXML();