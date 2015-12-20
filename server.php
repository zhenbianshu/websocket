<?php 
/*先创建一个*/

set_time_limit(0);//设置超时时间为无限，防止超时。
header("content-type:text/html;charset=utf-8");

class WebSocket
{
	protected $sockets=array();
	protected $master;
	protected $handshaked=array();

	public function __construct($host,$port)
	{
		$this->master=socket_create(AF_INET,SOCK_STREAM,SOL_TCP) or die("创建错误");

		socket_set_option($this->master,SOL_SOCKET,SO_REUSEADDR,1) or die("设置错误");//设置

		socket_bind($this->master,$host,$port) or die("绑定错误");//绑定端口

		socket_listen($this->master,3) or die("监听错误");//listen函数使用主动连接套接口变为被连接套接口，使得一个进程可以接受其它进程的请求，从而成为一个服务器进程。在TCP服务器编程中listen函数把进程变为一个服务器，并指定相应的套接字变为被动连接。其中的能存储的请求不明的socket数目。

		$this->sockets[]=$this->master;
		echo "主服务器:".$this->master."已开启，(".date("Y-m-d H:i:s",time()).")";

		while (true) {
			$write=$except=NULL;
			socket_select($this->sockets,$write,$except,NULL);
			//select作为监视函数，参数分别是（监视可读，可写，除去，超时时间）
			foreach ($this->sockets as $socket) 
			{
				if($socket==$this->master)
				{
					$client=socket_accept($this->master);
					//创建，绑定，监听后accept函数将会接受socket要来的连接，一旦有一个连接成功，将会返回一个新的socket资源用以交互，如果是一个多个连接的队列，只会处理第一个，如果没有连接的话，进程将会被阻塞，直到连接上。如果用set_socket_blocking或socket_set_noblock()设置了阻塞，会返回false;返回资源后，将会持续等待连接。
					if($client<0)
					{
						echo "接收信息出错。";
						continue;
					}else
					{
						self::connect($client);	
					}
				}else
				{
					$bytes=@socket_recv($socket, $buffer, 2048, 0);
					if($bytes==0)return;
					if(!$this->handshaked[(int) $socket])
					{
						self::handshak($socket,$buffer);
						echo "与".(int) $socket."号客户端握手成功(".date("Y-m-d H:i:s",time()).")";
					}else
					{
						$recv_msg=self::decode_msg($buffer);//调用解码函数，分析传入内容。
						$reply_msg=self::process_msg($recv_msg);//确定返回内容
						self::send_reply($socket,$reply_msg);//给要返回的socket返回相应信息。
					}
				}
			}
			sleep(1);
		}
	}

	//将客户端加入客户端列表。
	public function connect($socket)
	{
		array_push($this->sockets,$socket);
		echo "一个socket连接上\r\n";
		$this->handshaked[(int) $socket]=false;
	}

	//用公共握手算法握手
	public function handshak($socket,$buffer)
	{
	    $line_with_key  = substr($buffer,strpos($buffer,'Sec-WebSocket-Key:')+18);//获取到传过来的带有密匙的那一行
	    $key  = trim(substr($line_with_key,0,strpos($line_with_key,"\r\n")));//获取连接密匙
	    $upgrade_key = base64_encode(sha1($key."258EAFA5-E914-47DA-95CA-C5AB0DC85B11",true));//固定的升级key的算法
	    $upgrade_message = "HTTP/1.1 101 Switching Protocols\r\n";
	    $upgrade_message .= "Upgrade: websocket\r\n";
	    $upgrade_message .= "Sec-WebSocket-Version: 13\r\n";
	    $upgrade_message .= "Connection: Upgrade\r\n";
	    $upgrade_message .= "Sec-WebSocket-Accept:" . $upgrade_key . "\r\n\r\n";//固定的升级头
	    socket_write($socket,$upgrade_message,strlen($upgrade_message));//向socket里写入升级信息
	    $this->handshaked[(int) $socket]=true;
	    return true;
	}

	//分析信息
	public function decode_msg($msg)
	{
		$mask = array();  
        $data = '';  
        $msg = unpack('H*',$msg);  //用unpack函数从二进制将数据解码
        $head = substr($msg[1],0,2);  
        if (hexdec($head{1}) === 8) {  
            $data = false;  
        }else if (hexdec($head{1}) === 1){  
            $mask[] = hexdec(substr($msg[1],4,2));  
            $mask[] = hexdec(substr($msg[1],6,2));  
            $mask[] = hexdec(substr($msg[1],8,2));  
            $mask[] = hexdec(substr($msg[1],10,2));  
           
            $s = 12;  
            $e = strlen($msg[1])-2;  
            $n = 0;  
            for ($i=$s; $i<= $e; $i+= 2) {  
                $data .= chr($mask[$n%4]^hexdec(substr($msg[1],$i,2)));  
                $n++;  
            }  
        } 
        return $data; //用解码算法（不怎么明白），获取到数据。
	}

	//向目录编码并发送返回信息
	public function send_reply($socket,$reply_msg)
	{
		$msg = preg_replace(array('/\r$/','/\n$/','/\r\n$/',), '', $reply_msg);//替换掉发送内容中的换行符

	    $block = str_split($msg, 125);//如果长度大于125，则分为多块发送。

		if (count($block) == 1)
		{
			$data="\x81" . chr(strlen($block[0])) . $block[0];
			socket_write($socket,$data,strlen($data));
			return true;
		}

		$data = "";
		foreach ($block as $piece){
			$data .= "\x81" . chr(strlen($piece)) . $piece;
		}
		socket_write($socket,$data,strlen($data));  
	    return true;
	}

	//针对信息逻辑返回信息
	public function process_msg($recv_msg)
	{
		if((strpos($recv_msg,"hello")!==false)||(strpos($recv_msg,"你好")!==false)||(strpos($recv_msg,"hi")!==false))
		{
			return "您好，祝您玩得开心~~";
		}
		return "你怎么不向人家问好啊？没礼貌！不理你。";
	}

}

$ws=new WebSocket("127.0.0.1","8080");