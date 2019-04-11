
$(function(){
				
	//input获取焦点
	$("input").focus(function(){
		$(".saveBtn").hide();
	});
	$("input").blur(function(){
		$(".saveBtn").show();
	});
	/**
	 * 全局变量
	 * **/
	/*开关按钮的状态（后台）*/
	var switchJudge = null;
	/*获取-是否设置-默认*/
	if($('.switchBoxC').css('left') != '0px'){
		/*开启状态*/
		switchJudge = true;
		
	} else {
		/*未-开启状态*/
		switchJudge = false;
	}
	console.log(switchJudge);
	/*'开关'-切换按钮*/
	$('.switchWrapC').on('click',function(){
		console.log('点击-开关');
		/*console.log($('.switchBoxC').css('left')); //0px*/
		/*逆思维*/
		if($('.switchBoxC').css('left') == '0px'){
			/*开关按钮=>滑动*/
			$('.switchBoxC').animate({
				'left': '.32rem',
			}, 300);
			/*开关按钮 wrap => css样式（未开启=>开启）*/
			$('.switchWrapC').removeClass('switchWrapOneC');
			$('.switchWrapC').addClass('switchWrapTwoC');
			/*开关按钮box=>css样式（未开启=>开启）*/
			$('.switchBoxC').removeClass('switchBoxOneC');
			$('.switchBoxC').addClass('switchBoxTwoC');
			/*开启状态*/
			switchJudge = true;
			// console.log('开启',switchJudge);
			return false;
			
		} else {
			/*开关按钮=>滑动*/
			$('.switchBoxC').animate({
				'left': 0,
			}, 300);
			/*开关按钮 wrap => css样式（未开启=>开启）*/
			$('.switchWrapC').removeClass('switchWrapTwoC');
			$('.switchWrapC').addClass('switchWrapOneC');
			/*开关按钮box=>css样式（未开启=>开启）*/
			$('.switchBoxC').removeClass('switchBoxTwoC');
			$('.switchBoxC').addClass('switchBoxOneC');
			
			/*未-开启状态*/
			switchJudge = false;
			console.log('关闭',switchJudge);
			return false;
			
		}
	})
	//点击保存按钮获取页面数据
	$(".saveBtn").click(function(){
		let name=$("#name").val();
		let phone=$("#phone").val();
		let myAddrs=$("#myAddrs").val();
		let myAdd=$("#myAddrs").attr('data-key');
		let site=$("#site").val();
		let is_default = switchJudge ? 1 : 0;
		let id = $("#address_id").val();
		// console.log("收货人----"+name+"\n电话----"+phone+"\n地区----"+myAddrs+"\n地区所需key值----"+myAdd+"\n地址----"+site+"\n默认地址----"+switchJudge);
		let phonestr=$("#phone").val()
		let reg="^((13[0-9])|(14[5,7])|(15[0-3,5-9])|(17[0,3,5-8])|(18[0-9])|166|198|199|(147))\\d{8}$";
		let phonereg=new RegExp(reg)
		if(!phonereg.test(phonestr)){
			alert("请输入正确的手机号码")
		}
		
		$.post('/mobile/user/handle_address',{type:'edit',consignee:name,mobile:phone,myAddrs:myAddrs,site:site,is_default:is_default,address_id:id},function(res){
			if(res.code == 1){
				alert('添加成功！');
				window.location.href = "/mobile/user/my_address";
			} else {
				alert('添加失败！');
			}
		});
		// console.log("收货人----"+name+"\n电话----"+phone+"\n地区----"+myAddrs+"\n地区所需key值----"+myAdd+"\n地址----"+site+"\n默认地址----"+switchJudge)
		//判断手机号码
	})
})