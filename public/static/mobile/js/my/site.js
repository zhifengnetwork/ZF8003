$(function(){	
				//input获取焦点
				$("input").focus(function(){
				  $(".saveBtn").hide();
				});
				$("input").blur(function(){
				  $(".saveBtn").show();
				});
				//设置默认地址
				$(".default").click(function(){
					if($(this).hasClass('active')){
						$(this).removeClass('active')						
						$(this).find('img').attr('src','../../img/purchase/Button-box@2x.png');
					}else{
						$(this).addClass('active');
						$(this).find('img').attr('src','../../img/purchase/button@2x.png');
					}
				})
				//点击保存按钮获取页面数据
				$(".saveBtn").click(function(){
					let name=$("#name").val();
					let phone=$("#phone").val();
					let myAddrs=$("#myAddrs").val();
					let myAdd=$("#myAddrs").attr('data-key');
					let site=$("#site").val();
					let flag=null;
						if($(".default").hasClass('active')){
							flag=true
						}else{
							flag=false
						}
					console.log("收货人----"+name+"\n电话----"+phone+"\n地区----"+myAddrs+"\n地区所需key值----"+myAdd+"\n地址----"+site+"\n默认地址----"+flag)
				})
			})