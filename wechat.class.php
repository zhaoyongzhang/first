<?php

//引入配置文件
require './wechat.cfg.php';

//定义一个wechat类
//用来存储微信的调用接口方法
class Wechat
{

    //封装
    // public  //公共 公有  全部可以访问
    // protected //受保护的  继承类可以访问
    // private  //私有的   只有本类内可以访问
    private $appid;
    private $appsecret;
    private $token;

    //构造方法
    //在类被实列化，触发 进行属性的赋值和初始化操作
    public function __construct()
    {
        $this->appid = APPID;
        $this->appsecret = APPSECRET;
        $this->token = TOKEN;
        $this->textTpl = "<xml>
              <ToUserName><![CDATA[%s]]></ToUserName>
              <FromUserName><![CDATA[%s]]></FromUserName>
              <CreateTime>%s</CreateTime>
              <MsgType><![CDATA[%s]]></MsgType>
              <Content><![CDATA[%s]]></Content>
              <FuncFlag>0</FuncFlag>
              </xml>";
        $this->itemTpl = "<item>
              <Title><![CDATA[%s]]></Title>
              <Description><![CDATA[%s]]></Description>
              <PicUrl><![CDATA[%s]]></PicUrl>
              <Url><![CDATA[%s]]></Url>
              </item>";
        $this->newsTpl = "<xml>
              <ToUserName><![CDATA[%s]]></ToUserName>
              <FromUserName><![CDATA[%s]]></FromUserName>
              <CreateTime>%s</CreateTime>
              <MsgType><![CDATA[news]]></MsgType>
              <ArticleCount>%s</ArticleCount>
              <Articles>%s
              </Articles>
              </xml>";
        $this->imagetpl = "<xml>
            <ToUserName><![CDATA[%s]]></ToUserName>
            <FromUserName><![CDATA[%s]]></FromUserName>
            <CreateTime>%s</CreateTime>
            <MsgType><![CDATA[image]]></MsgType>
            <Image>
            <MediaId><![CDATA[%s]]></MediaId>
            </Image>
            </xml>";
        $this->musicTpl = "<xml>
            <ToUserName><![CDATA[%s]]></ToUserName>
            <FromUserName><![CDATA[%s]]></FromUserName>
            <CreateTime>%s</CreateTime>
            <MsgType><![CDATA[music]]></MsgType>
            <Music>
            <Title><![CDATA[%s]]></Title>
            <Description><![CDATA[%s]]></Description>
            <MusicUrl><![CDATA[%s]]></MusicUrl>
            <HQMusicUrl><![CDATA[%s]]></HQMusicUrl>
            <ThumbMediaId><![CDATA[%s]]></ThumbMediaId>
            </Music>
            </xml>";
    }

    //校验方法
    public function valid()
    {
        $echoStr = $_GET["echostr"];

        //valid signature , option
        if ($this->checkSignature()) {
            echo $echoStr;
            exit;
        }
    }

    //消息管理方法
    public function responseMsg()
    {
        //get post data, May be due to the different environments
        $postStr = $GLOBALS["HTTP_RAW_POST_DATA"];
        // file_put_contents('./doUnsubscribe', $postStr);
        //extract post data
        if (!empty($postStr)) {
            /* libxml_disable_entity_loader is to prevent XML eXternal Entity Injection,
              the best way is to check the validity of xml by yourself */
            libxml_disable_entity_loader(true);
            //把xml解析称为对象
            $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
            //对应的消息类型，通过不同的方法处理
            switch ($postObj->MsgType) {
                case 'text':
                    $this->doText($postObj);  //处理文本消息
                    break;
                case 'image':
                    $this->doImage($postObj);  //处理图片消息
                    break;
                case 'location':
                    $this->doLocation($postObj);  //处理地理位置消息
                    break;
                case 'event':
                    $this->doEvent($postObj);   //处理事件消息
                default:
                    # code...
                    break;
            }
        }
    }
    //处理文本消息方法
    private function doText($postObj)
    {
        $keyword = trim($postObj->Content);
        if (!empty($keyword)) {
            switch ($keyword) {
                case '歌曲':
                    $this->sendMusic($postObj);
                    exit();
                    break;
                case '二维码':
                    $this->sendImg($postObj);
                    exit();
                    break;
                default:
                    # code...
                    break;
            }
            $contentStr = "Welcome to wechat world!";
            $url = 'http://api.qingyunke.com/api.php?key=free&appid=0&msg=' . $keyword;
            $content = $this->request($url, false);
            $content = json_decode($content);
            $contentStr = $content->content;
            $contentStr = str_replace('{br}', "\r", $contentStr);
            $resultStr = sprintf($this->textTpl, $postObj->FromUserName, $postObj->ToUserName, time(), 'text', $contentStr);
            echo $resultStr;
        }
    }
    //处理图片消息方法
    private function doImage($postObj)
    {
        $picUrl = $postObj->PicUrl;
        $resultStr = sprintf($this->textTpl, $postObj->FromUserName, $postObj->ToUserName, time(), 'text', $picUrl);
        file_put_contents('./imagetpl',$resultStr);
        echo $resultStr;
    }
    //处理地址位置消息方法
    private function doLocation($postObj)
    {
        $contentStr = '您当前所在的位置,纬度：'.$postObj->Location_X.' 经度'.$postObj->Location_Y;
        $resultStr = sprintf($this->textTpl, $postObj->FromUserName, $postObj->ToUserName, time(), 'text', $contentStr);
        echo $resultStr;
    }
    //处理事件消息方法
    private function doEvent($postObj)
    {
        //根据不同的事件类型，进行不同的方法处理
        switch ($postObj->Event) {
            case 'subscribe':
                $this->doSubscribe($postObj);  //关注事件处理
                break;
            case 'unsubscribe':
                $this->doUnsubscribe($postObj);  //关注事件处理
                break;
            case 'SCAN':
                $this->doScan($postObj); //关注了的用户扫描二维码触发的事件
                break;
            case 'CLICK':
                $this->doClick($postObj); //关注了的用户扫描二维码触发的事件
                break;
            default:
                # code...
                break;
        }
    }
    //关注事件处理
    private function doSubscribe($postObj)
    {
        $contentStr = '欢迎关注php57的测试公众号！';
        $resultStr = sprintf($this->textTpl, $postObj->FromUserName, $postObj->ToUserName, time(), 'text', $contentStr);
        echo $resultStr;
    }
    //取消关注事件处理
    private function dounsubscribe($postObj)
    {
        //触发写一个文件
        $userName = $postObj->FromUserName;
        file_put_contents('./dounsubscribe',$userName.'跑了！时间:'.date('Y-m-d H:i:s',"$postObj->CreateTime"));
    }
    //关注后扫描二维码事件
    private function doScan($postObj)
    {
        $contentStr = '您触发的场景值id为:'.$postObj->EventKey;
        $resultStr = sprintf($this->textTpl,$postObj->FromUserName,$postObj->ToUserName,time(),'text',$contentStr);
        echo $resultStr;
    }
    //处理自定义菜单点击事件
    private function doClick($postObj)
    {
        //通过判断不同的key，实现不同的方法
        switch ($postObj->EventKey) {
            case 'news':
                $this->sendNews($postObj);
                break;
            default:
                # code...
                break;
        }
    }
    //发送新闻方法
    private function sendNews($postObj)
    {
        $itemsArray = array(
            array(
                'Title' => 'Web服务API简介',
                'Description' => '高德Web服务API向开发者提供HTTP接口，开发者可通过这些接口使用各类型的地理数据服务，返回结果支持JSON和XML格式',
                'PicUrl' => 'http://a.amap.com/lbs/static/img/summary_web_banner.png',
                'Url' => 'http://lbs.amap.com/api/webservice/summary/',
            ),
            array(
                'Title' => 'Web服务API简介',
                'Description' => '高德Web服务API向开发者提供HTTP接口，开发者可通过这些接口使用各类型的地理数据服务，返回结果支持JSON和XML格式',
                'PicUrl' => 'http://a.amap.com/lbs/static/img/summary_web_banner.png',
                'Url' => 'http://lbs.amap.com/api/webservice/summary/',
            ),
            array(
                'Title' => 'Web服务API简介',
                'Description' => '高德Web服务API向开发者提供HTTP接口，开发者可通过这些接口使用各类型的地理数据服务，返回结果支持JSON和XML格式',
                'PicUrl' => 'http://a.amap.com/lbs/static/img/summary_web_banner.png',
                'Url' => 'http://lbs.amap.com/api/webservice/summary/',
            ),
            array(
                'Title' => 'Web服务API简介',
                'Description' => '高德Web服务API向开发者提供HTTP接口，开发者可通过这些接口使用各类型的地理数据服务，返回结果支持JSON和XML格式',
                'PicUrl' => 'http://a.amap.com/lbs/static/img/summary_web_banner.png',
                'Url' => 'http://lbs.amap.com/api/webservice/summary/',
            ),
            array(
                'Title' => 'Web服务API简介',
                'Description' => '高德Web服务API向开发者提供HTTP接口，开发者可通过这些接口使用各类型的地理数据服务，返回结果支持JSON和XML格式',
                'PicUrl' => 'http://a.amap.com/lbs/static/img/summary_web_banner.png',
                'Url' => 'http://lbs.amap.com/api/webservice/summary/',
            ),
        );
        //循环遍历拼接items模板
        $items = '';
        foreach ($itemsArray as $key => $value) {
            $items .= sprintf($this->itemTpl,$value['Title'],$value['Description'],$value['PicUrl'],$value['Url']);
        }
        //拼接图文模板
        $resultStr = sprintf($this->newsTpl,$postObj->FromUserName,$postObj->ToUserName,time(),count($itemsArray),$items);
        echo $resultStr;
    }
    //发送图片消息
    private function sendImg($postObj)
    {
        $mediaID = 'pybLN1E4fYSlhtexATCzsZjDVwPh6k1WGrxFuRLP0zsAfPuyLQUN8P1_0H58Zd1H';
        $resultStr = sprintf($this->imagetpl,$postObj->FromUserName,$this->ToUserName,time(),$mediaID);
        echo $resultStr;
    }
    //发送音乐消息
    private function sendMusic($postObj)
    {
        $Title = '义勇军进行曲';
        $Description = '中国人民共和国国歌';
        $MusicUrl = 'http://wx.baibiannijiang.com/song.mp3';
        $HQMusicUrl = $MusicUrl;
        $MediaId = 'pybLN1E4fYSlhtexATCzsZjDVwPh6k1WGrxFuRLP0zsAfPuyLQUN8P1_0H58Zd1H';
        $resultStr = sprintf($this->musicTpl,$postObj->FromUserName,$postObj->ToUserName,time(),$Title,$Description,$MusicUrl,$HQMusicUrl,$MediaId);
        // file_put_contents('musictest', $resultStr);
        echo $resultStr;
    }
    //检查签名方法
    private function checkSignature()
    {
        // you must define TOKEN by yourself
        if (!defined("TOKEN")) {
            throw new Exception('TOKEN is not defined!');
        }

        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];

        $token = $this->token;
        $tmpArr = array($token, $timestamp, $nonce);
        // use SORT_STRING rule
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode($tmpArr);
        $tmpStr = sha1($tmpStr);

        if ($tmpStr == $signature) {
            return true;
        } else {
            return false;
        }
    }

    //发送请求的方法
    public function request($url, $https = true, $method = 'get', $data = null)
    {
        //1.初始化url
        $ch = curl_init($url);
        //2. 配置请求参数
        //返回数据为数据流，不直接输出到页面
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        //判断协议https
        if ($https === true) {
            //需要绕过ssl证书
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }
        //判断是否为post
        if ($method === 'post') {
            //开启post支持
            curl_setopt($ch, CURLOPT_POST, true);
            //传输post数据
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
        //3.发送请求
        $content = curl_exec($ch);
        //4.关闭资源
        curl_close($ch);
        return $content;
    }

    //获取accesstoken
    public function getAccessToken()
    {
        //判断是否有缓存access_token
        //判断是否过期
        //实列化
        // $mem = new Memcache();
        $redis = new Redis();
        // $mem->connect('127.0.0.1',11211);
        $redis->connect('127.0.0.1', 6379);
        $access_token = $redis->get('access_token');
        //过期或者没有设置返回的false
        if ($access_token === false) {
            //1.url
            $url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=' . $this->appid . '&secret=' . $this->appsecret;
            //2.判断请求方式
            //3.发送请求
            $content = $this->request($url);
            //4.处理返回值
            $content = json_decode($content);
            $access_token = $content->access_token;
            // file_put_contents('./accesstoken', $access_token);
            //存储access_token到memacche
            $redis->set('access_token', $access_token);
            // $redis->setTimeout('x', 3); // x will disappear in 3 seconds.
            $redis->setTimeout('access_token', 7100);
        }
        return $access_token;
    }

    public function getAccessToken1()
    {
        //判断是否有缓存access_token
        //判断是否过期
        //获取文件信息
        $filename = './accesstoken';
        //过期或者不存在才获取新数据
        if (!file_exists($filename) || time() - filemtime($filename) > 5) {
            //1.url
            $url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=' . $this->appid . '&secret=' . $this->appsecret;
            //2.判断请求方式
            //3.发送请求
            $content = $this->request($url);
            //4.处理返回值
            $content = json_decode($content);
            $access_token = $content->access_token;
            file_put_contents('./accesstoken', $access_token);
        } else {
            $access_token = file_get_contents($filename);
        }
        echo $access_token;
    }

    //获取ticket
    public function getTicket($scene_id, $tmp = true, $expire_seconds = 604800)
    {
        //1.url
        $url = 'https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token=' . $this->getAccessToken();
        //2.请求方式
        if ($tmp === true) {
            //临时
            $data = '{"expire_seconds": ' . $expire_seconds . ', "action_name": "QR_SCENE", "action_info": {"scene": {"scene_id": ' . $scene_id . '}}}';
        } else {
            //永久
            $data = '{"action_name": "QR_LIMIT_SCENE", "action_info": {"scene": {"scene_id": ' . $scene_id . '}}}';
        }
        //3.发送请求
        $content = $this->request($url, true, 'post', $data);
        //4.处理返回数据
        $content = json_decode($content);
        echo $content->ticket;
    }

    //通过ticket获取二维码
    public function getQRCode()
    {
        $ticket = 'gQHC7zwAAAAAAAAAAS5odHRwOi8vd2VpeGluLnFxLmNvbS9xLzAyNllMVU1mUl85aVQxbXBwak5wMWgAAgQZH0pZAwSAOgkA';
        //1.url
        $url = 'https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=' . $ticket;
        //2.请求方式
        //3.发送请求
        $content = $this->request($url);
        echo file_put_contents('./qrcode.jpg', $content);
        // header('Content-Type:image/jpg');
        // //4.处理返回值
        // echo $content;
    }

    //创建菜单
    public function createMenu()
    {
        //1.url
        $url = 'https://api.weixin.qq.com/cgi-bin/menu/create?access_token=' . $this->getAccessToken();
        //2.请求方式
        $data = '{
                "button":[
                {
                     "type":"click",
                     "name":"新闻资讯",
                     "key":"news"
                 },
                 {
                      "name":"php57",
                      "sub_button":[
                      {
                          "type":"view",
                          "name":"百度",
                          "url":"http://www.baidu.com/"
                       },
                       {
                          "type":"view",
                          "name":"H5",
                          "url":"https://panteng.github.io/wechat-h5-boilerplate/"
                      },
                       {
                          "name": "发送位置",
                          "type": "location_select",
                          "key": "rselfmenu_2_0"
                       }]
                  }]
            }';
        //3.发送请求
        $content = $this->request($url, true, 'post', $data);
        //4.处理返回值
        // {"errcode":0,"errmsg":"ok"}
        $content = json_decode($content);
        if ($content->errmsg == 'ok') {
            echo '创建成功！';
        } else {
            echo '创建失败！' . '<br />';
            echo '错误信息' . $content->errmsg . '<br />';
            echo '错误代码' . $content->errcode;
        }
    }

    //查询菜单
    public function showMenu()
    {
        //1.url
        $url = 'https://api.weixin.qq.com/cgi-bin/menu/get?access_token=' . $this->getAccessToken();
        //2.判断请求
        //3.发送请求
        $content = $this->request($url);
        //4.处理返回值
        var_dump($content);
    }

    //删除菜单
    public function delMenu()
    {
        //1.url
        $url = 'https://api.weixin.qq.com/cgi-bin/menu/delete?access_token=' . $this->getAccessToken();
        //2.判断请求
        //3.发送请求
        $content = $this->request($url);
        //4.处理返回值
        // var_dump($content);
        $content = json_decode($content);
        if ($content->errmsg == 'ok') {
            echo '删除成功！';
        } else {
            echo '删除失败！' . '<br />';
            echo '错误信息' . $content->errmsg . '<br />';
            echo '错误代码' . $content->errcode;
        }
    }

    //获取用户的openID列表
    public function getUserList()
    {
        //1.url
        $url = 'https://api.weixin.qq.com/cgi-bin/user/get?access_token=' . $this->getAccessToken();
        //2.判断请求
        //3.发送请求
        $content = $this->request($url);
        //4.处理返回值
        $content = json_decode($content);
        // var_dump($content);
        echo '用户数:'.$content->total.'<br />';
        echo '本次获取:'.$content->count.'<br />';
        foreach ($content->data->openid as $key => $value) {
            echo ($key+1).'###<a href ="http://localhost/wechat57/do2.php?openid='.$value.'">'.$value.'</a><br />';
            // var_dump($value);
        }
    }
    //通过openID获取用户基本信息
    public function getUserInfo($openid)
    {
        // $openid = $_GET['openid'];
        //1.url
        $url = 'https://api.weixin.qq.com/cgi-bin/user/info?access_token='.$this->getAccessToken().'&openid='.$openid.'&lang=zh_CN ';
        //2.请求方式
        //3.发送请求
        $content = $this->request($url);
        //4.处理返回值
        $content = json_decode($content);
        switch ($content->sex) {
            case '1':
                $sex = '男';
                break;
            case '2':
                $sex = '女';
                break;
            default:
                $sex = '未知';
                break;
        }
        echo '昵称:'.$content->nickname.'<br/>';
        echo '省份:'.$content->province.'<br/>';
        echo '性别:'.$sex.'<br />';
        echo '<img src="'.$content->headimgurl.'" style="width:100px;" />';
    }
    //上传临时素材
    public function uploadMedia($type='image')
    {
        //1.url
        $url = 'https://api.weixin.qq.com/cgi-bin/media/upload?access_token='.$this->getAccessToken().'&type='.$type;
        //2.判断请求
        $data['media'] = '@'.dirname(__FILE__).'/qrcode.jpg';
        //3.发送请求
        $content = $this->request($url,true,'post',$data);
        //4.处理返回值
        var_dump($content);

    }
    //获取临时素材
    public function getMedia()
    {
        $mediaID = 'pybLN1E4fYSlhtexATCzsZjDVwPh6k1WGrxFuRLP0zsAfPuyLQUN8P1_0H58Zd1H';
        //1.url
        $url = 'https://api.weixin.qq.com/cgi-bin/media/get?access_token='.$this->getAccessToken().'&media_id='.$mediaID;
        //2.判断请求
        //3.发送请求
        $content = $this->request($url);
        //4.处理返回值
        echo file_put_contents('./'.time().'.jpg',$content);
    }
}
