<?



$filename = dirname(__FILE__)."/result.txt";
	$handle = fopen($filename,'r');
	$c = fread($handle,filesize($filename));
	$a = explode("]}\n",$c);
	fclose($handle);
	unset($c);
	unset($handle);
	//echo count($a);
//die("wait nigger");

//$ca = stream_get_line($handle, 99999,']}');
//echo $ca;
echo "[";
$counter = 0;
$i_input = $argv[1];
$len_a = $argv[2];
for($i=$i_input;$i<$len_a;$i++):
	$line = $a[$i];
	$aux = json_decode($line.']}');
	if (isset($aux->app_title)){
		if ($counter> 0 )echo ',';
		echo $line.']}';	
		$counter++;
	}
	unset($aux);	
endfor;

echo "]";

//echo $aux->app_title.'\n';
//echo $a[1];
//echo count($a);
//var_dump($a[140000]);
/*
$filename = dirname(__FILE__)."/Igal2.txt";
	$handle = fopen($filename,'r');
	$c = fread($handle,filesize($filename));
//	$a = explode("]}",$c);
	fclose($handle);
//	unset($c);
	unset($handle);
	//echo count($a);
$aux = json_decode($c);
*/
//var_dump($aux);

//echo $aux[1]->app_title.'\n';

//$ca = stream_get_line($handle, 99999,']}');
//echo $ca;
//echo "[".implode("]},",$a)."]";
