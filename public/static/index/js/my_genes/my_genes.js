$(function(){

    /*
    * 滚动楼层导航
    */
    $(window).on('scroll',function(){
        var $scroll=$(this).scrollTop();
        //1.拖动滚轮，对应的楼梯样式进行匹配
        $('.content-wrap').each(function(){
            var $scrollTop = $('.content-wrap').eq($(this).index()).offset().top + 100;
            if($scrollTop > $scroll){//楼层的top大于滚动条的距离
                $('.siderNav-list li').removeClass('active');
                $('.siderNav-list li').eq($(this).index()).addClass('active');
                return false;//中断循环
            }
        });
    });

    //2.获取每个楼梯的offset().top,点击楼梯让对应的内容模块移动到对应的位置
    $(".siderNav-list li").click(function(){
        $(this).addClass('active').siblings('li').removeClass('active');
        var $top = $('.content-wrap').eq($(this).index()).offset().top;
        //获取每个楼梯的offsetTop值
        $('html,body').animate({
            scrollTop:$top
        })
    });
    
})