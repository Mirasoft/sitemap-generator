<?php
error_reporting(E_ALL);
//set_time_limit(0);

function is_ajax(){
	if(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest'){
		return true;
	}
	
	return false;
}

if(!is_ajax()){
	require("./layout.html");
}else{
	$status = 'false';
	$file = '';
	
	if(!empty($_GET['url'])){
		require("./sitemap.php");
		
		$sitemap = new sitemap;
		$sitemap->_limit = 20;
		
		if(isset($_GET['changefreq']) && ($_GET['changefreq'] == 'always' || $_GET['changefreq'] == 'hourly' || $_GET['changefreq'] == 'daily' || $_GET['changefreq'] == 'weekly' || $_GET['changefreq'] == 'monthly' || $_GET['changefreq'] == 'yearly' || $_GET['changefreq'] == 'never')){
			$sitemap->_changefreq = $_GET['changefreq'];
		}
		
		if(isset($_GET['modification']) && $_GET['modification'] == 'on'){
			$sitemap->_modification = true;
		}
		
		if(isset($_GET['priority']) && $_GET['priority'] == 'on'){
			$sitemap->_priority = true;
		}
		
		if(isset($_GET['compress']) && $_GET['compress'] == 'on'){
			$sitemap->_gzip = true;
		}
		
		if(false !== ($tmp = $sitemap->run($_GET['url']))){
			$status = 'true';
			$file = $tmp;
		}
	}
	
	header('Content-Type: application/json; charset=utf-8');
	echo json_encode(array('status' => $status, 'file' => $file));
}
