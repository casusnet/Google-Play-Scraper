<?
	set_time_limit(0);
	ini_set('memory_limit', '2048M');
	error_reporting(E_ALL);
	ini_set('display_errors', '1');
	include_once(dirname(__FILE__).'/google-playstore-api/playStoreApi.php'); // including class file		
	$class_init = new PlayStoreApi();	// initiating class
	$total_pages = 11;


	$categorys = array("ENTERTAINMENT","LIFESTYLE","PHOTOGRAPHY","TOOLS","COMMUNICATION","PERSONALIZATION","PRODUCTIVITY","FINANCE","BOOKS_AND_REFERENCE","MEDIA_AND_VIDEO","MUSIC_AND_AUDIO","MEDICAL","APP_WALLPAPER","COMICS","HEALTH_AND_FITNESS","SOCIAL","LIBRARIES_AND_DEMO","SHOPPING",'ARCADE',"SPORTS","EDUCATION","BUSINESS","NEWS_AND_MAGAZINES","WEATHER","TRANSPORTATION","TRAVEL_AND_LOCAL","APP_WIDGETS","RACING","CASUAL","SPORTS_GAMES","GAME_WALLPAPER","CARDS","BRAIN","GAME_WIDGETS");//;
	$category =	$categorys[$argv[1]];	
echo	count($categorys).'\n';
echo $category;
/* 	foreach ($categorys as $category): */

	echo '====CATEGORY '.$category.'===\n';

	for($i = 0;$i < $total_pages;$i++):
		if ($i > 0 and $i % 2 == 0) sleep(60);
		$page = $i;
		$items = $class_init->categoryFreeItems($category,$page); // paid 
	$handle = fopen(dirname(__FILE__).'/the-rest.txt', 'a')	;
		foreach ($items as  $app):
			fwrite($handle,$app->app_play_store_link.'\n');
			endforeach;
			fclose($handle);			
	

			flush();
			sleep(rand(10,20));
		endfor;
		unset($items);
		
		sleep(rand(600,1200));
		
/* 	endforeach; */

	


