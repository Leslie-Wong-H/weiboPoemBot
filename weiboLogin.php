<?php
  
  /**
    设置cookie文件
  */

  function CookieSet($cookie,$time){
    $newConfig = '<?php 
    $config = array(
      "cookie" => "'.$cookie.'",
      "time" => "'.$time.'",
    );';
    @file_put_contents('wbcookie.php', $newConfig);
  }


   if (!is_file('wbcookie.php')) {
     CookieSet('SUB;','0');
     require 'wbcookie.php';
   }
   else{
    require 'wbcookie.php';
   }

  
  require_once './weiboAccount.php';

  if (time() - $config['time'] >20*3600||$config['cookie']=='SUB;') {
    $cookie = login($sinauser,$sinapwd);
    if($cookie&&$cookie!='SUB;')
    {
      CookieSet($cookie,$time = time());
    }
    else
    {
      return error('203','获取cookie出现错误，请检查账号状态或者重新获取cookie');
    }
  }
  
  /**
       * 新浪微博登录(无加密接口版本)
       * @param  string $u 用户名
       * @param  string $p 密码
       * @return string    返回最有用最精简的cookie
       */
  function login($u,$p){
    $loginUrl = 'https://login.sina.com.cn/sso/login.php?client=ssologin.js(v1.4.15)&_=1403138799543';
    $loginData['entry'] = 'sso';
    $loginData['gateway'] = '1';
    $loginData['from'] = 'null';
    $loginData['savestate'] = '30';
    $loginData['useticket'] = '0';
    $loginData['pagerefer'] = '';
    $loginData['vsnf'] = '1';
    $loginData['su'] = base64_encode($u);
    $loginData['service'] = 'sso';
    $loginData['sp'] = $p;
    $loginData['sr'] = '1920*1080';
    $loginData['encoding'] = 'UTF-8';
    $loginData['cdult'] = '3';
    $loginData['domain'] = 'sina.com.cn';
    $loginData['prelt'] = '0';
    $loginData['returntype'] = 'TEXT';
    return loginPost($loginUrl,$loginData); 
  }

  /**
       * 发送微博登录请求
       * @param  string $url  接口地址
       * @param  array  $data 数据
       * @return json         算了，还是返回cookie吧//返回登录成功后的用户信息json
       */
  function loginPost($url,$data){
    $tmp = '';
    if(is_array($data)){
      foreach($data as $key =>$value){
        $tmp .= $key."=".$value."&";
      }
      $post = trim($tmp,"&");
    }else{
      $post = $data;
    }
    $ch = curl_init();
    curl_setopt($ch,CURLOPT_URL,$url); 
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,1); 
    curl_setopt($ch,CURLOPT_HEADER,1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch,CURLOPT_POST,1);
    curl_setopt($ch,CURLOPT_POSTFIELDS,$post);
    $return = curl_exec($ch);
    curl_close($ch);
    return 'SUB' . getSubstr($return,"Set-Cookie: SUB",'; ') . ';';
  }

  /**
   * 取本文中间
   */
  function getSubstr($str,$leftStr,$rightStr){
    $left = strpos($str, $leftStr);
    echo '左边:'.$left;
    $right = strpos($str, $rightStr,$left);
    echo '<br>右边:'.$right;
    if($left <= 0 or $right < $left) return '';
    return substr($str, $left + strlen($leftStr), $right-$left-strlen($leftStr));
  }

  /**
    错误反馈
  */

  function error($code,$msg){
    $arr = array('code'=>$code,'msg'=>$msg);
    echo json_encode($arr);
  }
  
