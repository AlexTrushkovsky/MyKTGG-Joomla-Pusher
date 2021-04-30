<?php
/**
 * @version		1.0.2
 * @package		myktggpush Plugin (MyKTGG push)
 * @author		Trushkovsky
 * @copyright	Copyright (c) 2020 trushkovsky.pp.ua All rights reserved.
 * @license		GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 */

// no direct access
defined('_JEXEC') or die ;

// Load the K2 Plugin API
JLoader::register('K2Plugin', JPATH_ADMINISTRATOR.'/components/com_k2/lib/k2plugin.php');

$app =JFactory::getDocument();
$app->addStyleSheet(JURI::root().'plugins/k2/myktggpush/asset/myktggpush.css' );

class plgK2myktggpush extends K2Plugin {
    public $pluginName = 'myktggpush';
    public $pluginNameHumanReadable = 'MyKTGG Push';

    public function __construct(&$subject, $params)
    {
        parent::__construct($subject, $params);
    }

    function onAfterK2Save(&$row, $isNew) {
			$k2itemId = $row->id;
			$k2item_attribs = json_decode($row->plugins);
			if (isset($k2item_attribs->myktggpushjp_send_notification) && $k2item_attribs->myktggpushjp_send_notification == 0) {
				$k2item_title= $row->title;

        if( JFile::exists( JPATH_SITE.'/media/k2/items/cache/'.md5("Image".$k2itemId).'_S.jpg' ) ){
					$k2item_image = 'https://ktgg.kiev.ua/media/k2/items/cache/'.md5("Image".$k2itemId).'_S.jpg';
				} else {
          $k2item_image = 'none';
        }
				require_once JPATH_SITE.'/components/com_k2/helpers/route.php';

				$link = K2HelperRoute::getItemRoute($row->id.':'.urlencode($row->alias), $row->catid.':'.urlencode($row->category->alias));

				$k2item_url =  JURI::root() . $link;

				$notification_msg = $row->introtext;
				$notification_msg = strip_tags(preg_replace('/<[^>]*>/','',str_replace(array("&nbsp;","\n","\r"),"",html_entity_decode($notification_msg,ENT_QUOTES,'UTF-8'))));

				$url = "https://fcm.googleapis.com/fcm/send";
				$token = "/topics/news";
				$serverKey = 'key';

                $data = array('title' =>$k2item_title , 'body' => $notification_msg, 'image_url' => $k2item_image, 'link' => $k2item_url, 'icon' => 'news');
                $arrayToSend = array('to' => $token, 'title' =>$k2item_title , 'body' => $notification_msg, 'image' => $k2item_image, 'sound' => 'default', 'badge' => '1', 'data' => $data);
                $json = json_encode($arrayToSend);
		 		$headers = array();
                $headers[] = 'Content-Type: application/json';
			 	$headers[] = 'Authorization: key='.$serverKey;
			 	$ch = curl_init();
			 	curl_setopt($ch, CURLOPT_URL, $url);
			 	curl_setopt($ch, CURLOPT_CUSTOMREQUEST,"POST");
			 	curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
				curl_setopt($ch, CURLOPT_HTTPHEADER,$headers);
				//Send the request
				$response = curl_exec($ch);
				//Close request
				if ($response === FALSE) {
					die('FCM Send Error: ' . curl_error($ch));
				}
				curl_close($ch);


				$k2item_attribs->myktggpushjp_send_notification = 0;
				$k2item_attribs->myktggpushjp_subscribers_ids = array();

				$row->plugins = json_encode($k2item_attribs);

				$object = new stdClass();

				$object->id = $row->id;
				$object->plugins = $row->plugins;

				JFactory::getDbo()->updateObject('#__k2_items', $object, 'id');
			}
      return true;
	}
}
