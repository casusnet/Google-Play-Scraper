<?

$PROXY['ready'] = -1;
$PROXY['address']=-1;
$PROXY['port']= -1;
$PROXY['external_ip']=-1;
$PROXY['ready']=-1;

function extractBody($response_str)
{
        $parts = preg_split('|(?:\r?\n){2}|m', $response_str, 2);
        if (isset($parts[1])) return $parts[1];
        return '';
}
function proxy_api($cmd)
{
        $pwd="33eef12c05db79d501d58b137058bf31";
        $uid=6990;
        
        global $PROXY;
        global $NL;
        $fp = fsockopen("www.seo-proxies.com", 80);
        if (!$fp) 
        {
                echo "Unable to connect to proxy API $NL";
                return -1; // connection not possible
        } else 
        {
                if ($cmd == "rotate")
                {
                        $PROXY['ready']=0;
                        fwrite($fp, "GET /api.php?api=1&uid=$uid&pwd=$pwd&cmd=rotate&randomness=1 HTTP/1.0\r\nHost: www.seo-proxies.com\r\nAccept: text/html, text/plain, text/*, */*;q=0.01\r\nAccept-Encoding: plain\r\nAccept-Language: en\r\n\r\n");
                        stream_set_timeout($fp, 8);
                        $res="";
                        $n=0;
                        while (!feof($fp)) 
                        {
                                if ($n++ > 4) break;
                                $res .= fread($fp, 8192);
                        }
                        $info = stream_get_meta_data($fp);
                        fclose($fp);
        
                        if ($info['timed_out']) 
                        {
                                echo 'API: Connection timed out! $NL';
                                return -2; // api timeout
                  } else 
                        {
                                if (strlen($res) > 1000) return -3; // invalid api response (check the API website for possible problems)
                                $data=extractBody($res);
                                $ar=explode(":",$data);
                                if (count($ar) < 4) return -100; // invalid api response
                                switch ($ar[0])
                                {
                                        case "ERROR":
                                                echo "API Error: $res $NL";
                                                return 0; // Error received
                                        break;
                                        case "ROTATE":
                                                $PROXY['address']=$ar[1];
                                                $PROXY['port']=$ar[2];
                                                $PROXY['external_ip']=$ar[3];
                                                $PROXY['ready']=1;
                                                return 1;
                                        break;
                                        default:
                                                echo "API Error: Received answer $ar[0], expected \"ROTATE\"";
                                                return -101; // unknown API response
                                }
                        }
                } // cmd==rotate
        }
}


/*
 * This function either returns the external IP or 0 in case of an error
 * The function: proxy_api("rotate"); will define the required $PROXY variable
 */
function getip()
{
    global $PROXY;
    if (!$PROXY['ready']) return -1; // proxy not ready
    
    $curl_handle=curl_init();
    curl_setopt($curl_handle,CURLOPT_URL,'http://squabbel.com/ipxx.php'); // this site will return the plain IP address, great for testing if a proxy is ready
    curl_setopt($curl_handle,CURLOPT_CONNECTTIMEOUT,10);
    curl_setopt($curl_handle,CURLOPT_TIMEOUT,10);
    curl_setopt($curl_handle,CURLOPT_RETURNTRANSFER,1);
    $curl_proxy = "$PROXY[address]:$PROXY[port]";
    curl_setopt($curl_handle, CURLOPT_PROXY, $curl_proxy);
    $tested_ip=curl_exec($curl_handle);

    if(preg_match("^([1-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])(\.([0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])){3}^", $tested_ip))
    {
        curl_close($curl_handle);
        return $tested_ip;
    }
    else
    {
        $info = curl_getinfo($curl_handle);
        curl_close($curl_handle);
        return 0; // possible error would be a wrong authentication IP
    }
}

		 