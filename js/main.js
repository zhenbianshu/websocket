//websocket简单聊天室前端部分vue实现
var app = new Vue({
  el: '#app',
  data: {
  	ws: null,
    hist: [
       'hello world!'
    ],
    message: '',
	user_id:null,
  user_list: [
    null
  ],
  user_num:0
  },
  methods:{
  	//init websocket
  	init:function()
  	{
  		this.ws = new WebSocket("ws://127.0.0.1:8080");
  		this.ws.onopen = this.wsonopen;
　　　　this.ws.onerror = this.wsonerror;
　　　　this.ws.onmessage = this.wsonmessage; 
　　　　this.ws.onclose = this.wsclose;
  	},
    //生成用户随机id
  	rand_id:function()
  	{
  		let id = Math.random()*100000;
  		id = Math.ceil(id);
  		return id;
  	},
    //ws functions
  	wsonopen:function()
  	{
  		console.log('websocket connected');
  	},
  	wsonerror:function()
  	{
  		console.log('websocket error');
  	},
  	wsonmessage:function(e)
  	{  		
  		let data = JSON.parse(e.data);
  		//console.log(data); 

  		switch(data.type){
  			case 'handshake':
  			let user_info = {'type': 'login', 'content': this.user_id};
  			//console.log(user_info);
  			this.send_msg(user_info);
  			break;
  			case 'user':
  			let new_msg = data.from+':'+data.content;
  			this.hist.push(new_msg);
  			break;
  			case 'login':
  			let login_msg = data.content+':log in';
  			this.hist.push(login_msg);
        //更新在线人数和在线用户列表，下次优化
        this.user_list = data.user_list;
        this.user_num = this.user_list.length;
  			break;
  			case 'logout':
  			let logout_msg = data.content+':log out';
  			this.hist.push(logout_msg);
        this.user_list = data.user_list;
        this.user_num = this.user_list.length;
  			break;
  		}

  		
  		//console.log(this.hist);
  	},
    //发送自己的消息
  	send_my_msg:function()
  	{
  		let my_msg = {'content': this.message, 'type': 'user'};
		//console.log(my_msg);  		
  		this.send_msg(my_msg);
  		this.message = '';
  	},
    //发送系统消息
  	send_msg:function(data)
  	{
  		// let send_msg = {'content': this.message, 'type': 'user'};
  		// console.log(send_msg);
  		//console.log(data);
  		data = JSON.stringify(data);
  		this.ws.send(data);
  	},
  	wsclose:function()
  	{
  		console.log("connection closed (" + e.code + ")"); 
  	},
	test:function()
	{

	
	}

  },
  //初始化
  created:function(){
  	this.user_id = this.rand_id();
  	console.log('vue works your id is:'+this.user_id);
  	this.init();
  }
})
