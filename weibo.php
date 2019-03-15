<?php
require_once './weiboLogin.php';
header("Content-type: text/html; charset=utf-8");
header("Access-Control-Allow-Origin:*");
header('Content-type: application/json');
error_reporting(0);

/**
发送微博
**/
function curl($url,$post=0,$header=0,$cookie=0,$referer=0,$ua=0,$nobody=0){
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,$url);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		$httpheader[] = "Accept:*/*";
		$httpheader[] = "Accept-Encoding:gzip,deflate,sdch";
		$httpheader[] = "Accept-Language:zh-CN,zh;q=0.8";
		$httpheader[] = "Connection:close";
		curl_setopt($ch, CURLOPT_HTTPHEADER, $httpheader);
		if($post){
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
		}
		if($header){
			curl_setopt($ch, CURLOPT_HEADER, TRUE);
		}
		if($cookie){
			curl_setopt($ch, CURLOPT_COOKIE, $cookie);
		}
		if($referer){
			curl_setopt($ch, CURLOPT_REFERER, $referer);
		}
		if($ua){
			curl_setopt($ch, CURLOPT_USERAGENT,$ua);
		}else{
			curl_setopt($ch, CURLOPT_USERAGENT,'Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/64.0.3282.167 Safari/537.36');
		}
		if($nobody){
			curl_setopt($ch, CURLOPT_NOBODY,1);
		}
		curl_setopt($ch, CURLOPT_ENCODING, "gzip");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
		$ret = curl_exec($ch);
		curl_close($ch);
		return $ret;
	}




/**
 * 上传图片到微博图床
 * @author mengkun http://mkblog.cn
 * @param $file 图片文件/图片url
 * @param $multipart 是否采用multipart方式上传
 * return 返回的json数据
 */

function upload($file, $cookie, $multipart = true){
	$url = 'http://picupload.service.weibo.com/interface/pic_upload.php'.'?mime=image%2Fjpeg&data=base64&url=0&markpos=1&logo=&nick=0&marks=1&app=miniblog';
	if($multipart){
		$url .= '&cb=http://weibo.com/aj/static/upimgback.html?_wv=5&callback=STK_ijax_'.time();
		if(class_exists('CURLFile')){	//php 5.5
			$post['pic1'] = new CURLFile(realpath($file));
		}
		else {
			$post['pic1'] = '@'.realpath($file);
		}
	}
	else {
		$post['b64_data'] = base64_encode(file_get_contents($file));
	}

	// echo $post['b64_data'];

	//Curl 提交
	$ch = curl_init($url);
	curl_setopt_array($ch, array(
		CURLOPT_POST => true,
		CURLOPT_VERBOSE => true,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_HTTPHEADER => array("Cookie: $cookie"),
		CURLOPT_POSTFIELDS => $post,
	));

	$output = curl_exec($ch);
	curl_close($ch);
	// 正则表达式提取返回结果中的json数据

	preg_match('/({.*)/i', $output, $match);
	if(!isset($match[1])) return '';
	return $match[1];
}

 /**
  通过今日诗词API获取诗词内容
**/

function jinrishici(){

    $opts = array(
        'http'=>array(
        'method'=>"GET",
        'header'=>"Accept-language: en\r\n"."X-User-Token: k4z4CMgTyl3JN6s+y2iWWiHN6we+0J9V\r\n"
        )
    );
    $context = stream_context_create($opts);
    // Open the file using the HTTP headers set above
    $tangshi_pailie = json_decode(file_get_contents('https://v2.jinrishici.com/one.json', false, $context),true);          //今日诗词API，带token版本

    // $tangshi_pailie = json_decode(file_get_contents('https://v2.jinrishici.com/one.json'), true);                  //今日诗词api,不带token版本

    $tangshi_title = $tangshi_pailie['data']['origin']['title'];              //标题
    $tangshi_dynasty = $tangshi_pailie['data']['origin']['dynasty'];          //朝代
    $tangshi_author = $tangshi_pailie['data']['origin']['author'];            //诗人

    $tangshi_line_numbers = count($tangshi_pailie['data']['origin']['content']);
    $tangshi_content = $tangshi_pailie['data']['origin']['content'][0];
    for ($i=1; $i < $tangshi_line_numbers; $i++) {
      $tangshi_temp_line = $tangshi_pailie['data']['origin']['content'][$i];
        $tangshi_content = $tangshi_content."\n".$tangshi_temp_line;
    }                                 //拼接全诗

    $post_Poem = "《".$tangshi_title."》"."\n".$tangshi_dynasty."·".$tangshi_author."\n"."\n".$tangshi_content;

    return "$post_Poem";
}

    include './wbcookie.php';
    $cookie = $config['cookie'];

    //通过必应首页图片api获取图片，并转存微博图床
    $bing_img = json_decode(upload('https://www.yuluoge.com/api/index.php?cid=5', $cookie, false),true);
    $bing_img_pid = $bing_img['data']['pics']['pic_1']['pid'];

    echo "$bing_img_pid\n";

    $tangshi = jinrishici();

    echo "$tangshi\n";

    $post=[
    'title' =>'今日要说什么？',
    'location' => 'v6_content_home',
    'text' => "#诗词[超话]# #中华好诗词# #中国诗词大会#"."\n".$tangshi."\n"."\n",//需要发送微博的内容
    'pic_id' =>  "$bing_img_pid",
    // '007CcEyfly1g042kquhztj31ns0u0tdu',//微博图片id，需事先上传好
    'isReEdit' => false,
    'pub_source' => 'page_2',
    'topic_id' => '1022%3A',
    'pub_type' => 'dialog',
    '_t' => 0,
    'style_type' => 1,
    ];
    $url='https://weibo.com/aj/mblog/add?ajwvr=6&__rnd=2918942797035';//不需要改变
    $referer='https://weibo.com/liufengshishe/home?topnav=1&wvr=6';//你的微博用户名(首页链接)

    $response = curl($url,$post,'',$cookie,$referer);


    echo "$response\n发送成功";
