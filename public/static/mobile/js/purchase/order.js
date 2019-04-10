// 订单确认
$(function(){
     //页面加载调用函数求总价
     sumTotal()

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
        $(".price_red").html(`¥${total.toFixed(2)-postage}`)  //应付金额
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

})