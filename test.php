<?

/*
$filename = dirname(__FILE__)."/index.txt";
	$handle = fopen($filename,'r');
	$c = fread($handle,filesize($filename));
	$a = explode("\\n",$c);
	$a = array_unique($a);
	fclose($handle);

foreach ($a as $k => $v)
echo $v."\n";
*/

$filename = dirname(__FILE__)."/FINAL-0.json";
	$handle = fopen($filename,'r');
	$c = fread($handle,filesize($filename));
//	$a = explode("]}",$c);
	fclose($handle);
//	unset($c);
	unset($handle);
	//echo count($a);
$aux = json_decode($c);
//var_dump($aux);

echo $aux[5389]->app_title.'\n';