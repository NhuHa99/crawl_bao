<?php 

$arr = array('sugar', 'gazar');
echo json_encode($arr);
//exit;


$string = '["https:\/\/scontent.faly1-2.fna.fbcdn.net\/v\/t1.0-9\/118431011_3753749087972882_1159013930715757834_n.png?_nc_cat=110&_nc_sid=730e14&_nc_ohc=uuR8MeEDDz0AX9q7p3a&_nc_oc=AQkzcn-0by5XNrN6LojfWBQSUflen0ui8UfGGYqwTugJwgrR_rf7WuKQ5TGhgmIDoLI&_nc_ht=scontent.faly1-2.fna&oh=df3095e44d4e1c738732fd3616623b14&oe=5F844B69", "https:\/\/scontent.faly1-2.fna.fbcdn.net\/v\/t1.0-9\/118431011_3753749087972882_1159013930715757834_n.png?_nc_cat=110&_nc_sid=730e14&_nc_ohc=uuR8MeEDDz0AX9q7p3a&_nc_oc=AQkzcn-0by5XNrN6LojfWBQSUflen0ui8UfGGYqwTugJwgrR_rf7WuKQ5TGhgmIDoLI&_nc_ht=scontent.faly1-2.fna&oh=df3095e44d4e1c738732fd3616623b14&oe=5F844B69" ]';

		print_r(json_decode($string));
exit;

$wp_automatic_fb_xs = "30:d6YMoY1x0m181Q:2:1579014395:-1:-1::AcVFHgISyus5UBtCjfXjh1zWl116zHkD7vUdDnb2OA";
$wp_automatic_fb_cuser = "100024318878767";

//curl ini
$ch = curl_init();
curl_setopt($ch, CURLOPT_HEADER,0);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
curl_setopt($ch, CURLOPT_TIMEOUT,20);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/83.0.4103.116 Safari/537.36');
curl_setopt($ch, CURLOPT_MAXREDIRS, 5); // Good leeway for redirections.
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); // Many login forms redirect at least once.

//$headers[] = "Authority: www.facebook.com";
//$headers[] = "Upgrade-Insecure-Requests: 1";
//$headers[] = "User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/85.0.4183.102 Safari/537.36";
$headers[] = "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9";
$headers[] = "Sec-Fetch-User: ?1";
//$headers[] = "Sec-Fetch-Site: same-origin";
//$headers[] = "Sec-Fetch-Mode: navigate";
//$headers[] = "Sec-Fetch-Dest: document";
//$headers[] = "Accept-Language: en-US,en;q=0.9,ar;q=0.8";
//$headers[] = "Cookie: sb=EQ4cXBTYxWz42BOz9wcRBk3Y; datr=tA4cXFLTAFhYujkzNdMZCi4O; _fbp=fb.1.1597929730796.250015084; locale=en_US; wd=1280x649; c_user=1475120237; spin=r.1002657250_b.trunk_t.1599946542_s.1_v.2_; presence=C%7B%22t3%22%3A%5B%5D%2C%22utc3%22%3A1599990967479%2C%22v%22%3A1%7D; xs=2%3A2CFvwwPh6OYlMw%3A2%3A1599946542%3A20445%3A6570%3A%3AAcWHKVwZMsqJiWEcStOvD2eCwHMnVihxfTXoR6ZHIA; fr=0FzSLpwkcTm4xV4v6.AWV9AsSUDyFPUI5qSD3ut8xyUHc.BY8phD.Ft.F9c.0.0.BfXjrr.AWXsDbEu";


curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

//curl get
$x='error';
$url='https://www.facebook.com/294240703923755/posts/3753754574639000';
curl_setopt($ch, CURLOPT_HTTPGET, 1);
curl_setopt($ch, CURLOPT_URL, trim($url));
curl_setopt ( $ch, CURLOPT_COOKIE, 'xs=' . $wp_automatic_fb_xs . ';c_user=' . $wp_automatic_fb_cuser );
$exec=curl_exec($ch);
$x=curl_error($ch);

echo $exec.$x;



$result = curl_exec($ch);
if (curl_errno($ch)) {
	echo 'Error:' . curl_error($ch);
}
curl_close ($ch);

echo $result;


?>