// 订单确认
$(function() {
	//页面加载调用函数求总价
	//  sumTotal()

	$('.discount_num').html($('.coupon_list').length)
	//使用余额
	$('.difference').click(function() {
		// console.log(12312)
		var acc = $(this)
		if(acc.children().hasClass('active')) {
			$('#balance').val(0);
			acc.children().attr('src', '/public/static/mobile/img/purchase/Button-box@2x.png').removeClass('active')
			acc.next().hide()
			acc.parent().next().hide()
		} else {
			$('#balance').val(1);
			acc.children().attr('src', '/public/static/mobile/img/purchase/button@2x.png').addClass('active');
			acc.next().show()
			acc.parent().next().show()
		}
	})

	//加
	$('.add').click(function() {
		var $sub = $(this)
		var num = $(this).prev().prop('value')
		var s = parseInt(num) + 1
		if(s == 0) {
			return;
		}
		$sub.prev().attr('value', s)
		//单行数量
		$sub.parent().parent().prev().children(':last-child').children('.num').html(`x${s}`)
		//调用求和
		//    sumTotal()
		amount_payable()
	})

	//减
	$('.subtract').click(function() {
		var $sub = $(this)
		var num = $(this).next().prop('value')
		var s = parseInt(num) - 1
		if(s == 0) {
			return;
		}
		$sub.next().attr('value', s)
		//单行数量
		$sub.parent().parent().prev().children(':last-child').children('.num').html(`x${s}`)
		//调用求和
		// sumTotal()
		amount_payable()
	})

	// //总价求和函数封装
	// function sumTotal(){
	//     var total=0;  //总和
	//     //邮费 优惠 余额
	//     var postage = parseInt($('.postage').html())
	//     var coupon = parseInt($('.coupon_discount').html())
	//     var remaining = parseInt($('.balance_text').val().slice(6))   
	//     $(".item-price .price").each((i,elem)=>{
	//         total+=parseInt($(elem).html().slice(1))*parseInt($(elem).next().html().slice(1))
	//     })
	//     $(".price_red").html(`${(total+postage-coupon).toFixed(2)}`)  //应付金额
	//     $("#order_amount").val(`${(total + postage-coupon).toFixed(2)}`);
	//     $('#total').html(`${(total + postage).toFixed(2)}`)      //订单总和
	//     $("#total_amount").val(`${(total+postage).toFixed(2)}`);
	//     $('.remaining_discount').html(`${(total+postage-coupon).toFixed(2)}`) //余额抵扣
	//     $("#coupon_price").val(`${(coupon).toFixed(2)}`);
	// }
	//  计算应付金额
	var jiayoufei = 0;
	var yuer = 0;

	function amount_payable() {
		//单品数量 
		console.log($(".num2").val())
		//单品价格
		console.log($(".danjia").html());
		//计算价格=单品*数量
		var sum = $(".num2").val() * $(".danjia").html()
		//商品邮费
		var youfei = $(".postage2").html();
		if(youfei!=undefined){

			jiayoufei = Number(sum) + Number(youfei)
		}else{
			jiayoufei = Number(sum);
		}
		console.log(jiayoufei)
		//赋值订单总和的内容
		$("#total").html(jiayoufei)

		//if (jiayoufei>)
		//获取余钱包的钱
		yuer = $(".remaining_discount").html()
		var zongmoney = $(".zongqian").html()
		//      222222222222

		if($('.coupon_use2').hasClass("employ")) {

			var man = $("#quoto").val()
			var jian = $("#money").val()
			if(jiayoufei >= man) {

				$('.coupon_discount').html(jian)

			} else {

				$('.coupon_discount').html("0.00")

			}

		}

		var xixi = parseFloat(jiayoufei) - parseFloat($(".coupon_discount2").html())
		if($('.yuer').is(".active")) {

			//上面的余额
			var qian = $("#my_money").val();
			if(qian > xixi) {

				$(".remaining_discount").html(xixi)
				$(".zongqian").html("0.00")

			}
			//下面的余额抵扣
			else {
				$(".remaining_discount").html("0.00")
			}

			if(jiayoufei > qian) {

				alert("余额不足")

			} else {

				var ccc = qian - jiayoufei

			}

		} else {

			$(".remaining_discount").html("0.00")

		}

		var youhuiquan = $(".coupon_discount2").html()
		var zongqian = parseFloat(jiayoufei) - parseFloat(youhuiquan) - parseFloat($(".remaining_discount").html())
		//计算总应该付的钱
		if(zongqian > 0) {

			$(".zongqian").html(zongqian)
			//       	if($(".remaining_discount").html()>)

		} else {

			$(".zongqian").html("0.00")

		}
		$(".zongqian").html(jiayoufei)

	}

	//修改地址
	//  $('.item-img').click(function(){
	//      $(window).attr('location','/html/my/my_site.html');
	//  })

	//点击input输入框时被手机键盘遮挡住解决方法
	$('.remark').focus(function() {
			//$('.footer').hide();  //获得焦点 结算栏隐藏
			var u = navigator.userAgent;
			var isAndroid = u.indexOf('Android') > -1 || u.indexOf('Adr') > -1; //android终端
			if(isAndroid) {
				$('.wrap_frame').height($('.wrap_frame').height() + 200)
				$('.wrap_frame').scrollTop(200)
			}
		})
		.blur(function() {
			//$('.footer').show();  //失去焦点 结算栏显示
			var u = navigator.userAgent; //失去焦点时重新回到原来的状态
			var isAndroid = u.indexOf('Android') > -1 || u.indexOf('Adr') > -1; //android终端
			if(isAndroid) {
				$('.wrap_frame').height($('.wrap_frame').height() - 200)
				$('.wrap_frame').scrollTop(0)
			}
		})

	//领取优惠券
	$('.mode-yhq').click(function() {
		var add = $(this)
		$('.receive_coupon').show() //遮罩层
		$('.receive_box').show() //优惠券
		// thisScrollNum = $(document).scrollTop();
		$('.wrap_frame').css({
			'position': 'fixed',
			'top': 0,
			'left': 0,
			'height': '100%',
			'width': '100%',
			'touch-action': 'pan-y'
		});
		$('html').css({
			'height': '100%'
		});
	})

	//隐藏优惠券
	$('.receive_coupon').click(coupon) //遮罩层
	$('.achieve').click(coupon) //完成

	function coupon() {
		$('.receive_coupon').hide() //遮罩层
		$('.receive_box').hide() //优惠券
		/*恢复滑动*/
		$('.wrap_frame').css({
			'position': '',
			'top': '',
			'left': '',
			'height': '',
			'width': '',
			'touch-action': ''
		});
		$('html').css({
			'height': ''
		});
		/*恢复当前用户滚动的位置！*/
		// $(document).scrollTop(thisScrollNum);
		$("receive_coupon,body").unbind("touchmove");
		//调用总和
		// sumTotal()
	}

	//使用优惠券
	$('.use').click(function() {
		var goods_id = $('#goods_id').val()
		var coupon = $('.coupon_use').data('id')
		var employ = $(this)
		var length = $('.coupon_list').length //优惠券长度
		var lengthh = $('.coupon_list').children('.employ').length //使用优惠券的长度
		var html = employ.parent().prev().find('.original').html()
		//  获取优惠券的金额 
		//  var pri = employ.parent().prev().find('.price').html()

		$('.man').show().find('.discount_num').html(html)

		if($('.coupon_use2').hasClass("coupon_use")) {
			var man = $("#quoto").val()
			var jian = $("#money").val()
			if(jiayoufei >= man) {

				$('.coupon_discount').html(jian)

			} else {

				$('.coupon_discount').html("0.00")

			}
		} else {

			$('.coupon_discount').html("0.00")

		}

		var youhuiquan = $(".coupon_discount2").html()
		var xixi = parseFloat(jiayoufei) - parseFloat($(".coupon_discount2").html())
		//     console.log("xixi"+parseFloat(xixi))
		$(".remaining_discount").html(xixi)
		$(".zongqian").html("0.00")

		var img = `<img src="/public/static/mobile/img/purchase/logo@2x.png" alt="" class="been">`
		if(employ.parent().hasClass('coupon_use')) {
			employ.parent().addClass('employ').addClass('add').removeClass('coupon_use').parent().siblings().find('.employ').removeClass('employ').addClass('coupon_use').find('.been').remove()
			employ.parent().append(img)
			//  employ.addClass('use1').removeClass('use')
			length--; //优惠券长度-1
			$('.numm').find('.discount_num').html(length)
			$('.popup').fadeIn(500) //使用成功
			$('.popup').animate({
				opactiy: 1
			}, 1000, function() {
				$('.popup').fadeOut(500)
			})
		} else if(employ.parent().hasClass('add')) {
			$('.numm').find('.discount_num').html(length)
			employ.parent().addClass('coupon_use').removeClass('employ').children('.been').remove();
			console.log(employ.parent().children('.been'))
		}
	})
	//点击使用余额 
	$(".difference").click(function() {

		//       console.log($('.yuer').hasClass("active"))
		//       if ($('.yuer').is(".active")){
		//
		//          var qian=$("#my_money").val();
		//          $(".remaining_discount").html(qian)
		//           if (jiayoufei>qian){
		//
		//              alert("余额不足")
		//
		//           }else{
		//
		//               var ccc=qian-jiayoufei
		//               console.log("ccddd"+ccc)
		//
		//
		//           }
		//
		//
		//       }else{
		//
		//           $(".remaining_discount").html("0.00")
		//
		//       }

		amount_payable()
		//      var youhuiquan=$(".coupon_discount2").html()
		//     console.log(jiayoufei)  
		//     console.log(youhuiquan)
		//     var zongqian = parseFloat(jiayoufei)-parseFloat(youhuiquan)-parseFloat(yuer)
		//     console.log( "zongqian"+zongqian) 
		//       //计算总应该付的钱
		//     $(".zongqian").html(zongqian)

	})

})