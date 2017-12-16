<?php
header('Content-type:text/html;Charset=utf-8;');
include('function.php');
define("TOKEN", "mujiang");//自己定义的token 就是个通信的私钥
$wechatObj = new wechatCallbackapiTest();
// 检测token时运行该方法
// $wechatObj->valid();
// 用户消息返回时，调用该方法
$wechatObj->responseMsg();
class wechatCallbackapiTest
{
    public function valid()
    {
        $echoStr = $_GET["echostr"];
        if($this->checkSignature()){
        	header('content-type:text');
            echo $echoStr;
            exit;
        }
    }
    public function write_log($log){
        $log = date("Y/m/d H:i:s").$log."\r\n";
        file_put_contents('./log.txt',$log,FILE_APPEND);
    }
    public function responseMsg()
    {
        $postStr = $GLOBALS["HTTP_RAW_POST_DATA"];
        if (!empty($postStr)){
            $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
            $this->write_log(json_encode($postObj));
            $fromUsername = (string)$postObj->FromUserName;
            $toUserName = (string)$postObj->ToUserName;
            $keyword = (string)$postObj->Content;
            $msgtype = (string)$postObj->MsgType;
            // 收到的消息为文本
            if(strcmp($msgtype,'text') == 0){
                $this->search($fromUsername,$toUserName,$keyword);
            }
            // 为事件  这里默认处理扫描带参二维码事件
            if(strcmp($msgtype,'event') == 0){
                $EventKey = trim((string)$postObj->EventKey);
                $prefix = "qrscene_";
                // // 未关注
                if(strpos($EventKey,$prefix) === 0){
                    $scene_str = substr($EventKey,strlen($prefix));
                }else{
                    // 已关注
                    $scene_str = $EventKey;
                }
                $arr = explode("&",$scene_str);
                $media_id = $arr[0];
                $type = $arr[1];
                $this->write_log($media_id);
                $this->write_log($type);
                if(strcmp($type,'news') === 0){
                    $this->sendNewsMessage($fromUsername, $media_id);
                }else if(strcmp($type,'video') === 0){
                    $this->sendVideoMessage($fromUsername,$toUserName,$media_id);
                }
            }
        }
    }

    // // 发送视频消息
    public function sendVideoMessage($fromUsername,$toUsername,$media_id){
        $time = time();
        $videoTpl = "<xml>
        <ToUserName><![CDATA[%s]]></ToUserName>
        <FromUserName><![CDATA[%s]]></FromUserName>
        <CreateTime>%s</CreateTime>
        <MsgType><![CDATA[%s]]></MsgType>
        <Video>
            <MediaId><![CDATA[%s]]></MediaId>
            <Title><![CDATA[%s]]></Title>
            <Description><![CDATA[%s]]></Description>
        </Video> 
        </xml>";
        $msgtype = 'video';
        $title = getTitleByMediaId($media_id);
        $resultStr = sprintf($videoTpl, $fromUsername, $toUsername, $time, $msgtype, $media_id,$title,'');
        $this->write_log('要回复的内容是:'.$resultStr);
        header('content-type:text');
        echo $resultStr;
    }

    // 发送文本消息
    public function sendText($fromUsername,$toUsername,$content){
        $time = time();
        $textTpl = "<xml>
        <ToUserName><![CDATA[%s]]></ToUserName>
        <FromUserName><![CDATA[%s]]></FromUserName>
        <CreateTime>%s</CreateTime>
        <MsgType><![CDATA[%s]]></MsgType>
        <Content><![CDATA[%s]]></Content>
        <FuncFlag>0<FuncFlag>
        </xml>";
        $msgtype = 'text';
        $resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgtype, $content);
        $this->write_log('要回复的内容是:'.$resultStr);
        header('content-type:text');
        echo $resultStr;
    }

    // 向用户发送图文消息
    public function sendNewsMessage($openid,$media_id){
        $data = array(
                "touser" => $openid,
                "msgtype" => "mpnews",
                "mpnews" => 
                    array(
                         "media_id" => $media_id
                    )
            );
        $wxapiurl = "https://api.weixin.qq.com/cgi-bin/message/custom/send";
        $result = curl_send_post_to_wxapi($wxapiurl,$data);
        $this->write_log('发送返回的结果是'.$result);
        header('Content-type:text');
        echo 'success';
    }

    public function search($fromUsername,$toUserName,$keyword){
        global $m; 
        $mr = $m->query("select media_id from mp_keywords where keyword = '{$keyword}' and status = 1 limit 1");
        $data = $mr->fetch_assoc();
        if(is_array($data)){
            $this->sendNewsMessage($fromUsername,$data['media_id']);
        }else{
            // 给用户准确的关键字提示
            $mr2 = $m->query("select keyword from mp_keywords where status = 1");
            $lists = array();
            while($list = $mr2->fetch_assoc()){
                $lists[] = $list['keyword'];
            }
            $listStr = implode(',',$lists);
            $this->sendText($fromUsername,$toUserName,"您输入的关键词【{$keyword}】不符合查询要求！请回复【{$listStr}】中的任意关键字进行查询。");
        }
    }

    private function checkSignature()
    {
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];
        $token =TOKEN;
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr);
        $tmpStr = implode( $tmpArr );
        $tmpStr = sha1( $tmpStr );
        if( $tmpStr == $signature ){
            return true;
        }else{
            return false;
        }
    }
}
?>