// 订单确认
$(function(){
     //页面加载调用函数求总价
     sumTotal()
     
     $('.discount_num').html($('.coupon_list').length)
     //使用余额
    $('.difference').click(function(){
        // console.log(12312)
        var acc = $(this)
        if(acc.children().hasClass('active')){
            acc.children().attr('src','../../img/purchase/Button-box@2x.png').removeClass('active')
            acc.next().hide()
            acc.parent().next().hide()
        }else{
            acc.children().attr('src','../../img/purchase/button@2x.png').addClass('active');
            acc.next().show()
            acc.parent().next().show()
        }
    })

    //加
    $('.add').click(function(){
       var $sub = $(this)
       var num = $(this).prev().prop('value')
       var s = parseInt(num)+1
       if(s == 0){return;}
       $sub.prev().attr('value',s)
       //单行数量
       $sub.parent().parent().prev().children(':last-child').children('.num').html(`x${s}`)
       //调用求和
       sumTotal()
    })

    //减
    $('.subtract').click(function(){
        var $sub = $(this)
        var num = $(this).next().prop('value')
        var s = parseInt(num)-1
        if(s == 0){return;}
        $sub.next().attr('value',s)
        //单行数量
        $sub.parent().parent().prev().children(':last-child').children('.num').html(`x${s}`)
        //调用求和
        sumTotal()
    })

    //总价求和函数封装
    function sumTotal(){
        var total=0;  //总和
        //邮费 优惠 余额
        var postage = parseInt($('.postage').html())
        var coupon = parseInt($('.coupon_discount').html())
        var remaining = parseInt($('.balance_text').val().slice(6))    
        $(".price").each((i,elem)=>{
            total+=parseInt($(elem).html().slice(1))*parseInt($(elem).next().html().slice(1))
        })
        $(".price_red").html(`¥${total.toFixed(2)-postage-coupon}`)  //应付金额
        $('#total').html(`${total.toFixed(2)}`)      //订单总和
        $('.remaining_discount').html(`${remaining.toFixed(2)}`) //余额抵扣
    }

    //修改地址
//  $('.item-img').click(function(){
//      $(window).attr('location','/html/my/my_site.html');
//  })

    //点击input输入框时被手机键盘遮挡住解决方法
    $('.remark').focus(function(){
            //$('.footer').hide();  //获得焦点 结算栏隐藏
            var u = navigator.userAgent;
            var isAndroid = u.indexOf('Android') > -1 || u.indexOf('Adr') > -1; //android终端
            if(isAndroid){
            $('.wrap_frame').height( $('.wrap_frame').height()+200)
            $('.wrap_frame').scrollTop(200)
            }
    })
    .blur(function(){
            //$('.footer').show();  //失去焦点 结算栏显示
            var u = navigator.userAgent; //失去焦点时重新回到原来的状态
            var isAndroid = u.indexOf('Android') > -1 || u.indexOf('Adr') > -1; //android终端
            if(isAndroid){
            $('.wrap_frame').height( $('.wrap_frame').height()-200)
            $('.wrap_frame').scrollTop(0)
            }
    })

    //领取优惠券
    $('.mode-yhq').click(function(){
        var add = $(this)
        $('.receive_coupon').show()  //遮罩层
        $('.receive_box').show()     //优惠券
        thisScrollNum = $(document).scrollTop();
				$('.wrap_frame').css({
					'position': 'fixed',
					'top': -thisScrollNum,
					'left': 0,
                    'height': '100%',
                    'width':'100%',
                    'touch-action':'pan-y'
				});
				$('html').css({
					'height': '100%'
				});
    })

    //隐藏优惠券
    $('.receive_coupon').click(coupon) //遮罩层
    $('.achieve').click(coupon)        //完成


    function coupon(){
        $('.receive_coupon').hide()  //遮罩层
        $('.receive_box').hide()     //优惠券
         /*恢复滑动*/
         $('.wrap_frame').css({
            'position': '',
            'top': '',
            'left': '',
            'height': '',
            'width':'',
            'touch-action':''
        });
        $('html').css({
            'height': ''
        });
        /*恢复当前用户滚动的位置！*/
        $(document).scrollTop(thisScrollNum);
        $("receive_coupon,body").unbind("touchmove");   
        //调用总和
        sumTotal()
    }

    //使用优惠券
    $('.use').click(function(){
         var employ = $(this)
         var length = $('.coupon_list').length  //优惠券长度
         var lengthh = $('.coupon_list').children('.employ').length  //使用优惠券的长度
         var html = employ.parent().prev().find('.original').html()  
         var pri = employ.parent().prev().find('.price').html()
         $('.man').show().find('.discount_num').html(html)
         $('.coupon_discount').html(pri)
         var img = `<img src="../../img/purchase/logo@2x.png" alt="" class="been">`
         if(lengthh>=1){  //使用优惠券的长度大于或等于一
            alert("只能使用一张优惠卷哦~")
            return
        }
         if(employ.parent().hasClass('coupon_use')){
         employ.parent().addClass('employ').removeClass('coupon_use')
         employ.parent().append(img)
         length--;   //优惠券长度-1
         $('.numm').find('.discount_num').html(length)
         $('.popup').show()  //使用成功
         $('.popup').animate({
             opactiy:1
         }
         ,1000,function(){
            $('.popup').hide()
         })
         }
    })

})