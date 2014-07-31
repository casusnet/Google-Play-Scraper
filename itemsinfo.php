<?

echo '/* SCRAPER GOOGLE'.PHP_EOL.' 
---------------------------------*/'.PHP_EOL;
date_default_timezone_set('Europe/Dublin');
	ini_set('memory_limit', '1024M');
	include_once(dirname(__FILE__).'/google-playstore-api/playStoreApi.php'); // including class file		
	$filename = dirname(__FILE__)."/listafinal.txt";
	$handle = fopen($filename,'r');
	$c = fread($handle,filesize($filename));
	$a = explode("\n",$c);
	$a = array_unique($a);
	fclose($handle);

	// Output


	$log = fopen(dirname(__FILE__)."/robot.log",'a');
	
	if (!isset($argv[1]) or !isset($argv[2])) die("Introduce Rango".PHP_EOL);
	$ini = $argv[1];
	$tongada = $argv[2];
	$end = $ini + $tongada;
	
	

	if ($end > count($a)) $end = count($a);
	
	$log_txt = Date("H:i:s d/m/Y")." Total: ".count($a)." => Scraping ".$ini." - ".$end;
	fwrite($log,$log_txt.PHP_EOL );
	
	$class_init = new PlayStoreApi();	// initiating class
$errors = 0;
	while ($ini <= $end):
		if ($a[$ini] != ''):
		$aux = explode("?id=",$a[$ini]);

		$item_id = trim($aux[1]);

		$o = $class_init->itemInfo($item_id);		
		$log_txt = '';
		echo $o['app_id'].PHP_EOL;
		if (isset($o['app_title'])){
			$errors = 0;
			$log_txt = "OK ".Date("H:i:s d/m/Y")." Getting ".$aux[1]." ".$ini;
				$filename = dirname(__FILE__)."/OUTBOX/".$o['category'].".txt";
				$handle = fopen($filename,'a');	
				fwrite($handle, json_encode($o).PHP_EOL);
				fclose($handle);
		}	else{
			$errors++;
			$log_txt = "ERROR ".Date("H:i:s d/m/Y")." ".$a[$ini]." ".$ini;
			$errors_file = fopen(dirname(__FILE__)."/errors.log",'a');
			fwrite($errors_file, $a[$ini].PHP_EOL);
			fclose($errors_file);
		}
		
		if ($errors > 5) {
			fwrite($log,"Cancelado demasiados errores".PHP_EOL );
			fclose($log);
			die("Too much errors");
		}
		
		fwrite($log,$log_txt.PHP_EOL );
	

		flush();
		endif;
		$ini++;
		
		
	endwhile;



	
fclose($log);
