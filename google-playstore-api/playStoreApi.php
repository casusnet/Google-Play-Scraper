<?php
	error_reporting(E_ALL);
	ini_set('display_errors', '1');
	include_once('Queryelements.php'); // including class file
	include_once('uagent.php'); 
	include_once('seo-proxys.php');	

	class PlayStoreApi{

		private $base_store_url = 'http://play.google.com';
		private $store;
		private $requestCounter = 1;
		
		function PlayStoreApi($store = 'us'){
		
			$this->store = $store;
			$this->rotateProxy();
		}
		function rotateProxy(){
			global $PROXY;
			while ($PROXY['ready'] < 0):
				proxy_api('rotate');			
			endwhile;

		}
		function get_fcontent( $url,  $javascript_loop = 0, $timeout = 5 ) {
			$this->requestCounter++;
			global $PROXY;
			
			if ($this->requestCounter % 5 == 0){
			
				$this->rotateProxy();
			}						
			$url = str_replace( "&amp;", "&", urldecode(trim($url)) );

			$cookie = tempnam ("/tmp", "CURLCOOKIE");
			$timeout = 10;
			$ch = curl_init();
			$agent =  'Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US; rv:1.9.1.2) Gecko/20090729 Firefox/3.5.2 GTB5';
//random_uagent();		

			curl_setopt( $ch, CURLOPT_USERAGENT, $agent );
			curl_setopt( $ch, CURLOPT_URL, $url );
			curl_setopt( $ch, CURLOPT_COOKIEJAR, $cookie );
			//curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
			curl_setopt( $ch, CURLOPT_ENCODING, "" );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
			curl_setopt( $ch, CURLOPT_AUTOREFERER, true );
			curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );    # required for https urls
			curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, $timeout );
			curl_setopt( $ch, CURLOPT_TIMEOUT, $timeout );
			curl_setopt( $ch, CURLOPT_MAXREDIRS, 10 );
			
			curl_setopt($ch, CURLOPT_PROXY,$PROXY['address'].':'.$PROXY['port']);
			curl_setopt($ch, CURLOPT_PROXYTYPE, 'HTTP');

	        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
			/*

start=240&num=60&numChildren=0&ipf=1&xhr=1&token=B0EO8lXLKr0znZtCHOB7PDfrf4s%3A1393588659805
https://play.google.com/store/apps/category/SPORTS/collection/topselling_free
	
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $params);

		start:240
num:60
numChildren:0
ipf:1
xhr:1
*/

			
			$content = curl_exec( $ch );
			//echo $content;
			$response = curl_getinfo( $ch );

			curl_close ( $ch );
	
			if ($response['http_code'] == 301 || $response['http_code'] == 302) {
				ini_set("user_agent",$agent);
	
				if ( $headers = get_headers($response['url']) ) {
					foreach( $headers as $value ) {
						if ( substr( strtolower($value), 0, 9 ) == "location:" )
							return get_url( trim( substr( $value, 9, strlen($value) ) ) );
					}
				}
			}
	
			if (( preg_match("/>[[:space:]]+window\.location\.replace\('(.*)'\)/i", $content, $value) || preg_match("/>[[:space:]]+window\.location\=\"(.*)\"/i", $content, $value) ) && $javascript_loop < 5) {
				return get_url( $value[1], $javascript_loop+1 );
			} else {
				return array( $content, $response );
			}
		}

		function test(){
			$page_url = 'https://play.google.com/store/apps/category/GAME/collection/topselling_paid?';
			$this_content = $this->get_fcontent($page_url);
			//echo $this_content[0];
		}
		
		function topPaidApps($start = 1){
			$start = $start - 1;
			$start = $start * 24;
			$page_url = 'https://play.google.com/store/apps/collection/topselling_paid?start='.$start.'&num=24';
			$elements_to_look = 'snippet-list';
			$this_content = $this->get_fcontent($page_url);
			if(isset($this_content[0])){
				phpQuery::newDocumentHTML($this_content[0]);
				$error_found = pq("#error-section")->text();
				if($error_found == "We're sorry, the requested URL was not found on this server.")
				{
					return 0;
				}

				$list_items = pq('.'.$elements_to_look.' > li');
				$break_all_items = explode('</li>',$list_items);
				$break_all_items = str_replace('<li>','',$break_all_items);
				foreach($break_all_items as $li)
				{
					phpQuery::newDocument($li);
					$icon = pq('img')->attr('src');
					$app_title = pq('.details > div > a.title')->html();
					$app_author = pq('.attribution > div > a')->html();
					$app_snippet = pq('.snippet-content')->html();
					$app_price = pq('.buy-button-price')->text();
					// external links
					$app_play_store_link = $this->base_store_url.''.pq('.details > div > a.title')->attr('href');
					$app_id_context = explode('?id=',$app_play_store_link);
					if(isset($app_id_context[1]))
					{
						$app_id_context = explode('&',$app_id_context[1]);
						$app_id = $app_id_context[0];
					}
					else
					{
						$app_id = 'Not defined';
					}
					$ratings_context = explode(' ',pq('.ratings')->attr('title'));
					if(isset($ratings_context[1])){$ratings = $ratings_context[1];}else{$ratings = 'Not defined';}
					if(!empty($app_id) && $app_id != 'Not defined')
					{
						$paid_apps[] = (object) array('app_title' => $app_title,'app_icon' => $icon,'app_author' => $app_author,'app_snippet' => $app_snippet,'app_price' => $app_price,
							'app_play_store_link' => $app_play_store_link,'app_id' => $app_id,'app_ratings' => $ratings);
					}
				}
				return $paid_apps;
			}
			else
			{
				return 0;
			}
		}

		function topFreeApps($start = 1){
			$start = $start - 1;
			$start = $start * 24;
			$page_url = 'https://play.google.com/store/apps/collection/topselling_free?start='.$start.'&num=24';
			$elements_to_look = 'snippet-list';
			$this_content = $this->get_fcontent($page_url);
			if(isset($this_content[0])){
				phpQuery::newDocumentHTML($this_content[0]);
				$error_found = pq("#error-section")->text();
				if($error_found == "We're sorry, the requested URL was not found on this server.")
				{
					return 0;
				}

				$list_items = pq('.'.$elements_to_look.' > li');
				$break_all_items = explode('</li>',$list_items);
				$break_all_items = str_replace('<li>','',$break_all_items);
				foreach($break_all_items as $li)
				{
					phpQuery::newDocument($li);
					$icon = pq('img')->attr('src');
					$app_title = pq('.details > div > a.title')->html();
					$app_author = pq('.attribution > div > a')->html();
					$app_snippet = pq('.snippet-content')->html();
					$app_price = pq('.buy-button-price')->text();
					if($app_price == 'Install') { $app_price = 'Free'; }
					// external links
					$app_play_store_link = $this->base_store_url.''.pq('.details > div > a.title')->attr('href');
					$app_id_context = explode('?id=',$app_play_store_link);
					if(isset($app_id_context[1]))
					{
						$app_id_context = explode('&',$app_id_context[1]);
						$app_id = $app_id_context[0];
					}
					else
					{
						$app_id = 'Not defined';
					}
					$ratings_context = explode(' ',pq('.ratings')->attr('title'));
					if(isset($ratings_context[1])){$ratings = $ratings_context[1];}else{$ratings = 'Not defined';}
					if(!empty($app_id) && $app_id != 'Not defined')
					{
						$free_apps[] = (object) array('app_title' => $app_title,'app_icon' => $icon,'app_author' => $app_author,'app_snippet' => $app_snippet,'app_price' => $app_price,
							'app_play_store_link' => $app_play_store_link,'app_id' => $app_id,'app_ratings' => $ratings);
					}
				}
				return $free_apps;
			}
			else
			{
				return 0;
			}
		}

		function topGrossingApps($start = 1){
			$start = $start - 1;
			$start = $start * 24;
			$page_url = 'https://play.google.com/store/apps/collection/topgrossing?start='.$start.'&num=24';
			$elements_to_look = 'snippet-list';
			$this_content = $this->get_fcontent($page_url);
			if(isset($this_content[0])){
				phpQuery::newDocumentHTML($this_content[0]);
				$error_found = pq("#error-section")->text();
				if($error_found == "We're sorry, the requested URL was not found on this server.")
				{
					return 0;
				}

				$list_items = pq('.'.$elements_to_look.' > li');
				$break_all_items = explode('</li>',$list_items);
				$break_all_items = str_replace('<li>','',$break_all_items);
				foreach($break_all_items as $li)
				{
					phpQuery::newDocument($li);
					$icon = pq('img')->attr('src');
					$app_title = pq('.details > div > a.title')->html();
					$app_author = pq('.attribution > div > a')->html();
					$app_snippet = pq('.snippet-content')->html();
					$app_price = pq('.buy-button-price')->text();
					if($app_price == 'Install') { $app_price = 'Free'; }
					// external links
					$app_play_store_link = $this->base_store_url.''.pq('.details > div > a.title')->attr('href');
					$app_id_context = explode('?id=',$app_play_store_link);
					if(isset($app_id_context[1]))
					{
						$app_id_context = explode('&',$app_id_context[1]);
						$app_id = $app_id_context[0];
					}
					else
					{
						$app_id = 'Not defined';
					}
					$ratings_context = explode(' ',pq('.ratings')->attr('title'));
					if(isset($ratings_context[1])){$ratings = $ratings_context[1];}else{$ratings = 'Not defined';}
					if(!empty($app_id) && $app_id != 'Not defined')
					{
						$grossing_apps[] = (object) array('app_title' => $app_title,'app_icon' => $icon,'app_author' => $app_author,'app_snippet' => $app_snippet,'app_price' => $app_price,
							'app_play_store_link' => $app_play_store_link,'app_id' => $app_id,'app_ratings' => $ratings);
					}
				}
				return $grossing_apps;
			}
			else
			{
				return 0;
			}
		}

		function topNewPaidApps($start = 1){
			$start = $start - 1;
			$start = $start * 24;
			$page_url = 'https://play.google.com/store/apps/collection/topselling_new_paid?start='.$start.'&num=24';
			$elements_to_look = 'snippet-list';
			$this_content = $this->get_fcontent($page_url);
			if(isset($this_content[0])){
				phpQuery::newDocumentHTML($this_content[0]);
				$error_found = pq("#error-section")->text();
				if($error_found == "We're sorry, the requested URL was not found on this server.")
				{
					return 0;
				}

				$list_items = pq('.'.$elements_to_look.' > li');
				$break_all_items = explode('</li>',$list_items);
				$break_all_items = str_replace('<li>','',$break_all_items);
				foreach($break_all_items as $li)
				{
					phpQuery::newDocument($li);
					$icon = pq('img')->attr('src');
					$app_title = pq('.details > div > a.title')->html();
					$app_author = pq('.attribution > div > a')->html();
					$app_snippet = pq('.snippet-content')->html();
					$app_price = pq('.buy-button-price')->text();
					if($app_price == 'Install') { $app_price = 'Free'; }
					// external links
					$app_play_store_link = $this->base_store_url.''.pq('.details > div > a.title')->attr('href');
					$app_id_context = explode('?id=',$app_play_store_link);
					if(isset($app_id_context[1]))
					{
						$app_id_context = explode('&',$app_id_context[1]);
						$app_id = $app_id_context[0];
					}
					else
					{
						$app_id = 'Not defined';
					}
					$ratings_context = explode(' ',pq('.ratings')->attr('title'));
					if(isset($ratings_context[1])){$ratings = $ratings_context[1];}else{$ratings = 'Not defined';}
					if(!empty($app_id) && $app_id != 'Not defined')
					{
						$new_paid_apps[] = (object) array('app_title' => $app_title,'app_icon' => $icon,'app_author' => $app_author,'app_snippet' => $app_snippet,'app_price' => $app_price,
							'app_play_store_link' => $app_play_store_link,'app_id' => $app_id,'app_ratings' => $ratings);
					}
				}
				return $new_paid_apps;
			}
			else
			{
				return 0;
			}
		}

		function topNewFreeApps($start = 1){
			$start = $start - 1;
			$start = $start * 24;
			$page_url = 'https://play.google.com/store/apps/collection/topselling_new_free?start='.$start.'&num=24';
			$elements_to_look = 'snippet-list';
			$this_content = $this->get_fcontent($page_url);
			if(isset($this_content[0])){
				phpQuery::newDocumentHTML($this_content[0]);
				$error_found = pq("#error-section")->text();
				if($error_found == "We're sorry, the requested URL was not found on this server.")
				{
					return 0;
				}

				$list_items = pq('.'.$elements_to_look.' > li');
				$break_all_items = explode('</li>',$list_items);
				$break_all_items = str_replace('<li>','',$break_all_items);
				foreach($break_all_items as $li)
				{
					phpQuery::newDocument($li);
					$icon = pq('img')->attr('src');
					$app_title = pq('.details > div > a.title')->html();
					$app_author = pq('.attribution > div > a')->html();
					$app_snippet = pq('.snippet-content')->html();
					$app_price = pq('.buy-button-price')->text();
					if($app_price == 'Install') { $app_price = 'Free'; }
					// external links
					$app_play_store_link = $this->base_store_url.''.pq('.details > div > a.title')->attr('href');
					$app_id_context = explode('?id=',$app_play_store_link);
					if(isset($app_id_context[1]))
					{
						$app_id_context = explode('&',$app_id_context[1]);
						$app_id = $app_id_context[0];
					}
					else
					{
						$app_id = 'Not defined';
					}
					$ratings_context = explode(' ',pq('.ratings')->attr('title'));
					if(isset($ratings_context[1])){$ratings = $ratings_context[1];}else{$ratings = 'Not defined';}
					if(!empty($app_id) && $app_id != 'Not defined')
					{
						$new_free_apps[] = (object) array('app_title' => $app_title,'app_icon' => $icon,'app_author' => $app_author,'app_snippet' => $app_snippet,'app_price' => $app_price,
							'app_play_store_link' => $app_play_store_link,'app_id' => $app_id,'app_ratings' => $ratings);
					}
				}
				return $new_free_apps;
			}
			else
			{
				return 0;
			}
		}

		function topPaidGames($start = 1){
			$start = $start - 1;
			$start = $start * 24;
			$page_url = 'https://play.google.com/store/apps/collection/topselling_paid_game?start='.$start.'&num=24';
			$elements_to_look = 'snippet-list';
			$this_content = $this->get_fcontent($page_url);
			if(isset($this_content[0])){
				phpQuery::newDocumentHTML($this_content[0]);
				$error_found = pq("#error-section")->text();
				if($error_found == "We're sorry, the requested URL was not found on this server.")
				{
					return 0;
				}

				$list_items = pq('.'.$elements_to_look.' > li');
				$break_all_items = explode('</li>',$list_items);
				$break_all_items = str_replace('<li>','',$break_all_items);
				foreach($break_all_items as $li)
				{
					phpQuery::newDocument($li);
					$icon = pq('img')->attr('src');
					$app_title = pq('.details > div > a.title')->html();
					$app_author = pq('.attribution > div > a')->html();
					$app_snippet = pq('.snippet-content')->html();
					$app_price = pq('.buy-button-price')->text();
					if($app_price == 'Install') { $app_price = 'Free'; }
					// external links
					$app_play_store_link = $this->base_store_url.''.pq('.details > div > a.title')->attr('href');
					$app_id_context = explode('?id=',$app_play_store_link);
					if(isset($app_id_context[1]))
					{
						$app_id_context = explode('&',$app_id_context[1]);
						$app_id = $app_id_context[0];
					}
					else
					{
						$app_id = 'Not defined';
					}
					$ratings_context = explode(' ',pq('.ratings')->attr('title'));
					if(isset($ratings_context[1])){$ratings = $ratings_context[1];}else{$ratings = 'Not defined';}
					if(!empty($app_id) && $app_id != 'Not defined')
					{
						$paid_games[] = (object) array('app_title' => $app_title,'app_icon' => $icon,'app_author' => $app_author,'app_snippet' => $app_snippet,'app_price' => $app_price,
							'app_play_store_link' => $app_play_store_link,'app_id' => $app_id,'app_ratings' => $ratings);
					}
				}
				return $paid_games;
			}
			else
			{
				return 0;
			}
		}

		function topFreeGames($start = 1){
			$start = $start - 1;
			$start = $start * 24;
			$page_url = 'https://play.google.com/store/apps/collection/topselling_free_game?start='.$start.'&num=24';
			$elements_to_look = 'snippet-list';
			$this_content = $this->get_fcontent($page_url);
			phpQuery::newDocumentHTML($this_content[0]);
				$error_found = pq("#error-section")->text();
				if($error_found == "We're sorry, the requested URL was not found on this server.")
				{
					return 0;
				}
			if(isset($this_content[0])){
				$list_items = pq('.'.$elements_to_look.' > li');
				$break_all_items = explode('</li>',$list_items);
				$break_all_items = str_replace('<li>','',$break_all_items);
				foreach($break_all_items as $li)
				{
					phpQuery::newDocument($li);
					$icon = pq('img')->attr('src');
					$app_title = pq('.details > div > a.title')->html();
					$app_author = pq('.attribution > div > a')->html();
					$app_snippet = pq('.snippet-content')->html();
					$app_price = pq('.buy-button-price')->text();
					if($app_price == 'Install') { $app_price = 'Free'; }
					// external links
					$app_play_store_link = $this->base_store_url.''.pq('.details > div > a.title')->attr('href');
					$app_id_context = explode('?id=',$app_play_store_link);
					if(isset($app_id_context[1]))
					{
						$app_id_context = explode('&',$app_id_context[1]);
						$app_id = $app_id_context[0];
					}
					else
					{
						$app_id = 'Not defined';
					}
					$ratings_context = explode(' ',pq('.ratings')->attr('title'));
					if(isset($ratings_context[1])){$ratings = $ratings_context[1];}else{$ratings = 'Not defined';}
					if(!empty($app_id) && $app_id != 'Not defined')
					{
						$free_games[] = (object) array('app_title' => $app_title,'app_icon' => $icon,'app_author' => $app_author,'app_snippet' => $app_snippet,'app_price' => $app_price,
							'app_play_store_link' => $app_play_store_link,'app_id' => $app_id,'app_ratings' => $ratings);
					}
				}
				return $free_games;
			}
			else
			{
				return 0;
			}
		}

		function topTrendingApps($start = 1){
			$start = $start - 1;
			$start = $start * 24;
			$page_url = 'https://play.google.com/store/apps/collection/movers_shakers?start='.$start.'&num=24';
			$elements_to_look = 'snippet-list';
			$this_content = $this->get_fcontent($page_url);
			phpQuery::newDocumentHTML($this_content[0]);
				$error_found = pq("#error-section")->text();
				if($error_found == "We're sorry, the requested URL was not found on this server.")
				{
					return 0;
				}
			if(isset($this_content[0])){
				$list_items = pq('.'.$elements_to_look.' > li');
				$break_all_items = explode('</li>',$list_items);
				$break_all_items = str_replace('<li>','',$break_all_items);
				foreach($break_all_items as $li)
				{
					phpQuery::newDocument($li);
					$icon = pq('img')->attr('src');
					$app_title = pq('.details > div > a.title')->html();
					$app_author = pq('.attribution > div > a')->html();
					$app_snippet = pq('.snippet-content')->html();
					$app_price = pq('.buy-button-price')->text();
					if($app_price == 'Install') { $app_price = 'Free'; }
					// external links
					$app_play_store_link = $this->base_store_url.''.pq('.details > div > a.title')->attr('href');
					$app_id_context = explode('?id=',$app_play_store_link);
					if(isset($app_id_context[1]))
					{
						$app_id_context = explode('&',$app_id_context[1]);
						$app_id = $app_id_context[0];
					}
					else
					{
						$app_id = 'Not defined';
					}
					$ratings_context = explode(' ',pq('.ratings')->attr('title'));
					if(isset($ratings_context[1])){$ratings = $ratings_context[1];}else{$ratings = 'Not defined';}
					if(!empty($app_id) && $app_id != 'Not defined')
					{
						$trending_apps[] = (object) array('app_title' => $app_title,'app_icon' => $icon,'app_author' => $app_author,'app_snippet' => $app_snippet,'app_price' => $app_price,
							'app_play_store_link' => $app_play_store_link,'app_id' => $app_id,'app_ratings' => $ratings);
					}
				}
				return $trending_apps;
			}
			else
			{
				return 0;
			}
		}

		function staffPicks($start = 1){
			$start = $start - 1;
			$start = $start * 24;
			$page_url = 'https://play.google.com/store/apps/collection/featured?start='.$start.'&num=24';
			$elements_to_look = 'snippet-list';
			$this_content = $this->get_fcontent($page_url);
			phpQuery::newDocumentHTML($this_content[0]);
				$error_found = pq("#error-section")->text();
				if($error_found == "We're sorry, the requested URL was not found on this server.")
				{
					return 0;
				}
			if(isset($this_content[0])){
				$list_items = pq('.'.$elements_to_look.' > li');
				$break_all_items = explode('</li>',$list_items);
				$break_all_items = str_replace('<li>','',$break_all_items);
				foreach($break_all_items as $li)
				{
					phpQuery::newDocument($li);
					$icon = pq('img')->attr('src');
					$app_title = pq('.details > div > a.title')->html();
					$app_author = pq('.attribution > div > a')->html();
					$app_snippet = pq('.snippet-content')->html();
					$app_price = pq('.buy-button-price')->text();
					if($app_price == 'Install') { $app_price = 'Free'; }
					// external links
					$app_play_store_link = $this->base_store_url.''.pq('.details > div > a.title')->attr('href');
					$app_id_context = explode('?id=',$app_play_store_link);
					if(isset($app_id_context[1]))
					{
						$app_id_context = explode('&',$app_id_context[1]);
						$app_id = $app_id_context[0];
					}
					else
					{
						$app_id = 'Not defined';
					}
					$ratings_context = explode(' ',pq('.ratings')->attr('title'));
					if(isset($ratings_context[1])){$ratings = $ratings_context[1];}else{$ratings = 'Not defined';}
					if(!empty($app_id) && $app_id != 'Not defined')
					{
						$staff_picks[] = (object) array('app_title' => $app_title,'app_icon' => $icon,'app_author' => $app_author,'app_snippet' => $app_snippet,'app_price' => $app_price,
							'app_play_store_link' => $app_play_store_link,'app_id' => $app_id,'app_ratings' => $ratings);
					}
				}
				return $staff_picks;
			}
			else
			{
				return 0;
			}
		}


		function staffPicksForTablet($start = 1){
			$start = $start - 1;
			$start = $start * 24;
			$page_url = 'https://play.google.com/store/apps/collection/tablet_featured?start='.$start.'&num=24';
			$elements_to_look = 'snippet-list';
			$this_content = $this->get_fcontent($page_url);
			phpQuery::newDocumentHTML($this_content[0]);
				$error_found = pq("#error-section")->text();
				if($error_found == "We're sorry, the requested URL was not found on this server.")
				{
					return 0;
				}
			if(isset($this_content[0])){
				$list_items = pq('.'.$elements_to_look.' > li');
				$break_all_items = explode('</li>',$list_items);
				$break_all_items = str_replace('<li>','',$break_all_items);
				foreach($break_all_items as $li)
				{
					phpQuery::newDocument($li);
					$icon = pq('img')->attr('src');
					$app_title = pq('.details > div > a.title')->html();
					$app_author = pq('.attribution > div > a')->html();
					$app_snippet = pq('.snippet-content')->html();
					$app_price = pq('.buy-button-price')->text();
					if($app_price == 'Install') { $app_price = 'Free'; }
					// external links
					$app_play_store_link = $this->base_store_url.''.pq('.details > div > a.title')->attr('href');
					$app_id_context = explode('?id=',$app_play_store_link);
					if(isset($app_id_context[1]))
					{
						$app_id_context = explode('&',$app_id_context[1]);
						$app_id = $app_id_context[0];
					}
					else
					{
						$app_id = 'Not defined';
					}
					$ratings_context = explode(' ',pq('.ratings')->attr('title'));
					if(isset($ratings_context[1])){$ratings = $ratings_context[1];}else{$ratings = 'Not defined';}
					if(!empty($app_id) && $app_id != 'Not defined')
					{
						$staff_picks_for_tablet[] = (object) array('app_title' => $app_title,'app_icon' => $icon,'app_author' => $app_author,'app_snippet' => $app_snippet,'app_price' => $app_price,
							'app_play_store_link' => $app_play_store_link,'app_id' => $app_id,'app_ratings' => $ratings);
					}
				}
				return $staff_picks_for_tablet;
			}
			else
			{
				return 0;
			}
		}

		function listCategories(){
			$page_url = 'https://play.google.com/store/apps/category/GAME';
			$this_content = $this->get_fcontent($page_url);
			if(isset($this_content[0])){
				phpQuery::newDocumentHTML($this_content[0]);
				$error_found = pq("#error-section")->text();
				if($error_found == "We're sorry, the requested URL was not found on this server.")
				{
					return 0;
				}

				$initial_load = explode('</ul>',pq('#tab-body-categories > div > .padded-content3')->html());
				foreach($initial_load as $filtering_elements){
					phpQuery::newDocumentHTML($filtering_elements);
					$heading_element = pq('h2 > a')->text();
					$list_items = pq('li');
					$str_replace = array('<li>','<ul>');
					$break_all_items = explode('</li>',$list_items);
					$break_all_items = str_replace($str_replace,'',$break_all_items);
					if(!empty($heading_element)){ $categories_array[$heading_element] = ''; }
					foreach($break_all_items as $li)
					{
						phpQuery::newDocument($li);
						$category_name = pq('a:first-child')->text();
						$category_url = pq('a:first-child')->attr('href');
						$category_id_context = explode('/',$category_url);
						$category_url = $this->base_store_url.''.$category_url;
						if(isset($category_id_context[4])) { $category_id = str_replace('?feature=category-nav','',$category_id_context[4]); } else { $category_id = 'Not defined'; }
						if(!empty($category_id) && $category_id !== 'Not defined'){
							$categories_array[$heading_element][] = (object) array('category_name' => $category_name,'category_url' => $category_url, 'category_id' => $category_id);
						}
					}
				}
				return $categories_array;
			}
			else
			{
				return 0;
			}
		}
		
		function categoryFreeItems($category,$start = 1){
			//$start = $start - 1;
			if ($start < 0)$start = 0;
			$per_page = 100;
			$start = $start * $per_page;
			//echo 'NEW CALL=> start:'.$start;
			$page_url = 'https://play.google.com/store/apps/category/'.$category.'/collection/topselling_paid?start='.$start.'&num='.$per_page.'&hl='.$this->store;
			$elements_to_look = 'card-list';
			$this_content = $this->get_fcontent($page_url);
			if(isset($this_content[0])){
			
				phpQuery::newDocumentHTML($this_content[0]);
				$error_found = pq("#error-section")->text();
				if($error_found == "We're sorry, the requested URL was not found on this server.")
				{
					return 0;
				}
				$list_items = pq('.'.$elements_to_look.' > .card');
				$break_all_items = explode('</div>',$list_items);
				$break_all_items = str_replace('<div>','',$break_all_items);
				$category_paid_apps = Array();

				foreach($break_all_items as $li)
				{
					phpQuery::newDocument($li);
					$icon = pq('img.cover-image')->attr('src');
					$app_title = pq('.details > h2 > a.title')->html();
					$app_author = pq('.subtitle-container > a.subtitle')->html();
					$app_snippet = pq('.card-content')->html();
					$app_price = pq('.price.buy')->text();
					if($app_price == 'Install') { $app_price = 'Free'; }
					// external links
					$app_play_store_link = $this->base_store_url.''.pq('.details > h2 > a.title')->attr('href');
					$app_id_context = explode('?id=',$app_play_store_link);
					if(isset($app_id_context[1]))
					{
						$app_id_context = explode('&',$app_id_context[1]);
						$app_id = $app_id_context[0];
					}
					else
					{
						$app_id = 'Not defined';
					}
					$ratings_context = explode(' ',pq('.ratings')->attr('title'));
					if(isset($ratings_context[1])){$ratings = $ratings_context[1];}else{$ratings = 'Not defined';}
					if(!empty($app_id) && $app_id != 'Not defined')
					{
						$category_paid_apps[] = (object) array('app_title' => $app_title,'app_icon' => $icon,'app_author' => $app_author,'app_snippet' => $app_snippet,'app_price' => $app_price,
							'app_play_store_link' => $app_play_store_link,'app_id' => $app_id,'app_ratings' => $ratings);
					}
				}
				return $category_paid_apps;
			}
			else
			{
				return 0;
			}
		}

		function categoryPaidItems($category,$start = 0){
			$start = $start - 1;
			if ($start < 0) $start = 0;
			$start = $start * 24;
			$page_url = 'https://play.google.com/store/apps/category/'.$category.'/collection/topselling_paid?start='.$start.'&num=24&hl='.$this->store;
			$elements_to_look = 'snippet-list';
			$this_content = $this->get_fcontent($page_url);
			if(isset($this_content[0])){
				phpQuery::newDocumentHTML($this_content[0]);
				$error_found = pq("#error-section")->text();
				if($error_found == "We're sorry, the requested URL was not found on this server.")
				{
					return 0;
				}

				$list_items = pq('.'.$elements_to_look.' > li');
				$break_all_items = explode('</li>',$list_items);
				$break_all_items = str_replace('<li>','',$break_all_items);
				$category_free_apps = Array();
				foreach($break_all_items as $li)
				{
					phpQuery::newDocument($li);
					$icon = pq('img')->attr('src');
					$app_title = pq('.details > div > a.title')->html();
					$app_author = pq('.attribution > div > a')->html();
					$app_snippet = pq('.snippet-content')->html();
					$app_price = pq('.buy-button-price')->text();
					if($app_price == 'Install') { $app_price = 'Free'; }
					// external links
					$app_play_store_link = $this->base_store_url.''.pq('.details > div > a.title')->attr('href');
					$app_id_context = explode('?id=',$app_play_store_link);
					if(isset($app_id_context[1]))
					{
						$app_id_context = explode('&',$app_id_context[1]);
						$app_id = $app_id_context[0];
					}
					else
					{
						$app_id = 'Not defined';
					}
					$ratings_context = explode(' ',pq('.ratings')->attr('title'));
					if(isset($ratings_context[1])){$ratings = $ratings_context[1];}else{$ratings = 'Not defined';}
					if(!empty($app_id) && $app_id != 'Not defined')
					{
						$category_free_apps[] = (object) array('app_title' => $app_title,'app_icon' => $icon,'app_author' => $app_author,'app_snippet' => $app_snippet,'app_price' => $app_price,
							'app_play_store_link' => $app_play_store_link,'app_id' => $app_id,'app_ratings' => $ratings);
					}
				}
				return $category_free_apps;
			}
			else
			{
				return 0;
			}
		}

		function developerItems($developer_id,$start = 1){
			$start = $start - 1;
			$start = $start * 12;
			$page_url = 'https://play.google.com/store/apps/developer?id='.$developer_id.'&start='.$start.'&num=12';
			$elements_to_look = 'snippet-list';
			$this_content = $this->get_fcontent($page_url);
			if(isset($this_content[0])){
				phpQuery::newDocumentHTML($this_content[0]);
				$error_found = pq("#error-section")->text();
				if($error_found == "We're sorry, the requested URL was not found on this server.")
				{
					return 0;
				}

				$list_items = pq('.'.$elements_to_look.' > li');
				$break_all_items = explode('</li>',$list_items);
				$break_all_items = str_replace('<li>','',$break_all_items);
				foreach($break_all_items as $li)
				{
					phpQuery::newDocument($li);
					$icon = pq('img')->attr('src');
					$app_title = pq('.details > div > a.title')->html();
					$app_author = pq('.attribution > div > a')->html();
					$app_snippet = pq('.snippet-content')->html();
					$app_price = pq('.buy-button-price')->text();
					if($app_price == 'Install') { $app_price = 'Free'; }
					// external links
					$app_play_store_link = $this->base_store_url.''.pq('.details > div > a.title')->attr('href');
					$app_id_context = explode('?id=',$app_play_store_link);
					if(isset($app_id_context[1]))
					{
						$app_id_context = explode('&',$app_id_context[1]);
						$app_id = $app_id_context[0];
					}
					else
					{
						$app_id = 'Not defined';
					}
					$ratings_context = explode(' ',pq('.ratings')->attr('title'));
					if(isset($ratings_context[1])){$ratings = $ratings_context[1];}else{$ratings = 'Not defined';}
					if(!empty($app_id) && $app_id != 'Not defined')
					{
						$developer_apps[] = (object) array('app_title' => $app_title,'app_icon' => $icon,'app_author' => $app_author,'app_snippet' => $app_snippet,'app_price' => $app_price,
							'app_play_store_link' => $app_play_store_link,'app_id' => $app_id,'app_ratings' => $ratings);
					}
				}
				return $developer_apps;
			}
			else
			{
				return 0;
			}
		}

		function searchStore($search_query,$sort='Popularity',$price='All',$safe_search = 'Off',$start=1){
			$start = $start - 1;
			$start = $start * 24;
			// Required search parameters array ( ** CAUTION --- DO NOT EDIT --- ** )
			$sort_array = array('Popularity' => 0, 'Relevance' => 1);
			$price_array = array('All' => 0, 'Paid' => 2,'Free' => 1);
			$safe_search_array = array('Off' => 1, 'Low' => 1, 'Moderate' => 2, 'Strict' => 3);

			if(in_array($sort,$sort_array)) { $sort_term = $sort_array[$sort]; } else { $sort_term = 0; }
			if(in_array($price,$price_array)) { $price_term = $price_array[$price]; } else { $price_term = 0; }
			if(in_array($safe_search,$safe_search_array)) { $safe_search_term = $safe_search_array[$safe_search]; } else { $safe_search_term = 0; }

			$page_url = 'https://play.google.com/store/search?q='.$search_query.'&c=apps&price='.$price_term.'&safe='.$safe_search_term.'&sort='.$sort_term.'&start='.$start.'&num=24';
			$elements_to_look = 'search-results-list';
			$this_content = $this->get_fcontent($page_url);
			if(isset($this_content[0])){
				phpQuery::newDocumentHTML($this_content[0]);
				$error_found = pq("#error-section")->text();
				if($error_found == "We're sorry, the requested URL was not found on this server.")
				{
					return 0;
				}

				$list_items = pq('.'.$elements_to_look.' > li');
				$break_all_items = explode('</li>',$list_items);
				$break_all_items = str_replace('<li>','',$break_all_items);
				foreach($break_all_items as $li)
				{
					phpQuery::newDocument($li);
					$icon = pq('img')->attr('src');
					$app_title = pq('.details > a.title')->html();
					$app_author = pq('.attribution > div > a')->html();
					$app_snippet = pq('.description')->html();
					$app_price = pq('.buy-button-price')->text();
					if($app_price == 'Install') { $app_price = 'Free'; }
					// external links
					$app_play_store_link = $this->base_store_url.''.pq('.details > a.title')->attr('href');
					$app_id_context = explode('?id=',$app_play_store_link);
					if(isset($app_id_context[1]))
					{
						$app_id_context = explode('&',$app_id_context[1]);
						$app_id = $app_id_context[0];
					}
					else
					{
						$app_id = 'Not defined';
					}
					$ratings_context = explode(' ',pq('.ratings')->attr('title'));
					if(isset($ratings_context[1])){$ratings = $ratings_context[1];}else{$ratings = 'Not defined';}
					if(!empty($app_id) && $app_id != 'Not defined')
					{
						$searched_apps[] = (object) array('app_title' => $app_title,'app_icon' => $icon,'app_author' => $app_author,'app_snippet' => $app_snippet,'app_price' => $app_price,
							'app_play_store_link' => $app_play_store_link,'app_id' => $app_id,'app_ratings' => $ratings);
					}
				}
				return $searched_apps;
			}
			else
			{
				return 0;
			}
		}
		function itemInfo($item_id){
				  		 
			$page_url = 'https://play.google.com/store/apps/details?id='.$item_id.'&hl='.$this->store;
			//echo $page_url;
			$this_content = $this->get_fcontent($page_url);
			if(isset($this_content[0])){
			phpQuery::newDocumentHTML($this_content[0]);
				$error_found = pq("#error-section")->text();
				if($error_found == "We're sorry, the requested URL was not found on this server.")
				{
					return 0;
				}
				$body = pq('div#body-content');
				//echo $li;
					phpQuery::newDocument($body);
				$app_info = Array();
				$banner_image = pq('.cover-container .cover-image')->attr('src');
				$banner_icon = pq('.doc-banner-icon > img')->attr('src');
				$app_genre = pq('span[itemprop="genre"]')->html();
				$ratings_context = explode(' ',pq('.ratings')->attr('title'));
				if(isset($ratings_context[1])){$ratings = $ratings_context[1];}else{$ratings = 'Not defined';}
				$app_title = pq('div.document-title > div')->html();
				$app_author = pq('div[itemprop="author"] > a.document-subtitle > span')->html();
				$app_reviews = pq('.reviews-stats > .reviews-num')->html();
				$app_rating = pq('.score')->html();
				$author_store_url = $this->base_store_url.''.pq('div[itemprop="author"] > a.document-subtitle')->attr('href');
				$app_price = pq('*[itemprop="price"]')->attr("content");
				if ($app_price == "" or empty($app_price)) $app_price = pq('.price.buy')->text();
				if(strstr($app_price, 'Install')) { $app_price = 'Free'; }
				$html_app_description = nl2br(pq('div[itemprop="description"]')->html());
				$text_plain_app_description = strip_tags($html_app_description);
				$screenshots = array();
				foreach(pq('div.thumbnails > img.screenshot') as $appshots){
					$app_screen_shots = pq($appshots)->attr('src');
					$screenshots[] =  $app_screen_shots;
				}
				$app_last_updated = pq('div[itemprop="datePublished"]')->html();
				$software_version = pq('div[itemprop="softwareVersion"]')->html();
				$os_required = 'Android '.pq('div[itemprop="operatingSystems"]')->html();
				$downloads = pq('div[itemprop="numDownloads"]')->html();
				$content_rating = pq('div[itemprop="contentRating"]')->html();
				$file_size = strip_tags(pq('div[itemprop="fileSize"]')->html());
				$developer_website = pq("a:contains('Visit Developer's Website')")->attr('href');
				$developer_email = str_replace("mailto:","",pq("a:contains('Email Developer')")->attr('href'));
				$permission_header = pq('.doc-permissions-header')->html();
				$app_store_url = $page_url;
				if($permission_header == 'This application requires no special permission to run.')
				{
					$app_permission_html = $permission_header;
					$app_permission_text_plain = $permission_header;
				}
				else
				{
					$app_permission_html = pq('.doc-permissions-list')->html();
					$app_permission_text_plain = strip_tags($app_permission_html);
				}
				$whats_new_html = pq('.doc-whatsnew-container')->html();
				$whats_new_text_plain = strip_tags($whats_new_html);
	
				/* Related */
				$first_rec_cluster = pq('.rec-cluster:first');
				phpQuery::newDocument($first_rec_cluster);
				$list_items = pq('.card-content.id-track-click.id-track-impression');
				$break_all_items = explode('</div> </div> </div>',$list_items);
				$break_all_items = str_replace('<div class="card-content id-track-click id-track-impression">','',$break_all_items);
				$related_viewed= Array();
				foreach($break_all_items as $li)
				{
					phpQuery::newDocument($li);
				//echo $li;
				//echo '<hr>';
					$related_thumbnail = pq('img.cover-image')->attr('src');
					$related_app_title = pq('img.cover-image')->attr('alt');
					$related_app_store_url = $this->base_store_url.''.pq('a.card-click-target')->attr('href');
					$app_id_context = explode('?id=',$related_app_store_url);
					$related_app_id;
					if(isset($app_id_context[1]))
					{
						$app_id_context = explode('&',$app_id_context[1]);
						$related_app_id = $app_id_context[0];
					}
					else
					{
						$related_app_id = 'Not defined';
					}
					
					if ($related_app_title != '' and $related_app_id != '')
						$related_viewed[] = array('related_app_title' => $related_app_title,'related_app_thumbnail' => $related_thumbnail,'related_app_store_url' => $related_app_store_url,
							'related_app_id' => $related_app_id						);
					
				}
				
						/* More from developer*/
				phpQuery::newDocument($body);
				$rec_cluster = pq('.rec-cluster:nth-child(2)');
				phpQuery::newDocument($rec_cluster);
				$list_items = pq('.card-content.id-track-click.id-track-impression');
				$break_all_items = explode('</div> </div> </div>',$list_items);
				$break_all_items = str_replace('<div class="card-content id-track-click id-track-impression">','',$break_all_items);
				$more_from_developer= Array();
				foreach($break_all_items as $li)
				{
					phpQuery::newDocument($li);
				//echo $li;
				//echo '<hr>';
					$related_thumbnail = pq('img.cover-image')->attr('src');
					$related_app_title = pq('img.cover-image')->attr('alt');
					$related_app_store_url = $this->base_store_url.''.pq('a.card-click-target')->attr('href');
					$app_id_context = explode('?id=',$related_app_store_url);
					$related_app_id;
					if(isset($app_id_context[1]))
					{
						$app_id_context = explode('&',$app_id_context[1]);
						$related_app_id = $app_id_context[0];
					}
					else
					{
						$related_app_id = 'Not defined';
					}
					if ($related_app_id != '' and !empty($related_app_id))
						$more_from_developer[] = array('related_app_title' => $related_app_title,'related_app_thumbnail' => $related_thumbnail,'related_app_store_url' => $related_app_store_url,
							'related_app_id' => $related_app_id						);
					
				}
				
					/* Fin related */
				$app_info = array('app_store_url' => $app_store_url,'banner_image' => $banner_image,'app_title' => $app_title, 'app_id' => $item_id,'app_author' => $app_author,'author_store_url' => $author_store_url,'category' => $app_genre,
					'app_price' => $app_price,'reviews' => $app_reviews, 'rating' => $app_rating,
					'content_rating' => $content_rating,
					'downloads' => $downloads,
					'html_app_description' => $html_app_description,'text_plain_app_description' => $text_plain_app_description,
					'app_last_updated' => $app_last_updated,'software_version' => $software_version,'os_required' => $os_required,
					'file_size' => $file_size,'developer_website' => $developer_website,'developer_email' => $developer_email,
					
					'related' => $related_viewed, 'related_developer' => $more_from_developer,'screenshots' => $screenshots
				);
				
				return $app_info;
				
			}
			else
			{
				return 0;
			}
		}
		
		function related($item_id){

			$page_url = 'https://play.google.com/store/apps/details?id='.$item_id;
			$this_content = $this->get_fcontent($page_url);
			if(isset($this_content[0])){
				phpQuery::newDocumentHTML($this_content[0]);
				$error_found = pq("#error-section")->text();
				if($error_found == "We're sorry, the requested URL was not found on this server.")
				{
					return 0;
				}
				$app_reviews = pq('.reviews-stats > .reviews-num')->html();

				
				$list_items = pq('.card-content.id-track-click.id-track-impression');
				$break_all_items = explode('</div> </div> </div>',$list_items);
				$break_all_items = str_replace('<div class="card-content id-track-click id-track-impression">','',$break_all_items);
				$related_viewed= Array();
				foreach($break_all_items as $li)
				{
					phpQuery::newDocument($li);
				echo $li;
				echo '<hr>';
					$related_thumbnail = pq('img.cover-image')->attr('src');
					$related_app_title = pq('img.cover-image')->attr('alt');
					$related_app_store_url = $this->base_store_url.''.pq('a.card-click-target')->attr('href');
					$app_id_context = explode('?id=',$related_app_store_url);
					if(isset($app_id_context[1]))
					{
						$app_id_context = explode('&',$app_id_context[1]);
						$related_app_id = $app_id_context[0];
					}
					else
					{
						$related_app_id = 'Not defined';
					}
					
					$related_app_developer = pq('.attribution > div > a')->text();
					$related_app_developer_store_url = pq('.attribution > div > a')->attr('href');
	
					$related_app_rating_context = explode(' ',pq('.app-snippet-ratings > div > div.ratings')->attr('title'));
					if(isset($related_app_rating_context[1])){$related_rating = $related_app_rating_context[1];}else{$related_rating = 'Not defined';}
	
					if(!empty($related_app_id) && $related_app_id !== 'Not defined')
					{
						$related_viewed[] = array('related_app_title' => $related_app_title,'related_app_thumbnail' => $related_thumbnail,'related_app_store_url' => $related_app_store_url,
							'related_app_id' => $related_app_id,'related_app_developer' => $related_app_developer,'related_app_developer_store_url' => $related_app_developer_store_url,
							'related_app_rating' => $related_rating
						);
					}
				}
				return Array("views" => $app_reviews , "related" => $related_viewed);
			}
			else
			{
				return 0;
			}
		}
/*

		function relatedInstalled($item_id){
			$page_url = 'https://play.google.com/store/apps/details?id='.$item_id;
			$this_content = $this->get_fcontent($page_url);
			if(isset($this_content[0])){
				phpQuery::newDocumentHTML($this_content[0]);
				$error_found = pq("#error-section")->text();
				if($error_found == "We're sorry, the requested URL was not found on this server.")
				{
					return 0;
				}

				$list_items = pq('div[data-analyticsid="users-also-installed"] > .snippet-list li');
				$break_all_items = explode('</li>',$list_items);
				$break_all_items = str_replace('<li>','',$break_all_items);
				foreach($break_all_items as $li)
				{
					phpQuery::newDocument($li);
					$related_thumbnail = pq('img.app-snippet-thumbnail')->attr('src');
					$related_app_title = pq('.common-snippet-title')->html();
					$related_app_store_url = $this->base_store_url.''.pq('a.app-snippet-thumbnail')->attr('href');
					$app_id_context = explode('?id=',$related_app_store_url);
					if(isset($app_id_context[1]))
					{
						$app_id_context = explode('&',$app_id_context[1]);
						$related_app_id = $app_id_context[0];
					}
					else
					{
						$related_app_id = 'Not defined';
					}
					$related_app_developer = pq('.attribution > div > a')->text();
					$related_app_developer_store_url = pq('.attribution > div > a')->attr('href');
	
					$related_app_rating_context = explode(' ',pq('.app-snippet-ratings > div > div.ratings')->attr('title'));
					if(isset($related_app_rating_context[1])){$related_rating = $related_app_rating_context[1];}else{$related_rating = 'Not defined';}
	
					if(!empty($related_app_id) && $related_app_id !== 'Not defined')
					{
						$related_installed[] = (object) array('related_app_title' => $related_app_title,'related_app_thumbnail' => $related_thumbnail,'related_app_store_url' => $related_app_store_url,
							'related_app_id' => $related_app_id,'related_app_developer' => $related_app_developer,'related_app_developer_store_url' => $related_app_developer_store_url,
							'related_app_rating' => $related_rating
						);
					}
				}
				return $related_installed;
			}
			else
			{
				return 0;
			}
		}
*/
	}
?>