$(function() {
    /*
    * 点赞
    */
    $(".praiseBtn").toggle(function(){
        $(this).find(".praise-icon").css("background-image","url(../../img/public/praise-r@2x.png)");
        var num = $(this).find(".num").html();
        var num2 = parseInt(num) + 1;
        $(this).find(".num").html(num2).css("color","#f64242");
    },function(){
        $(this).find(".praise-icon").css("background-image","url(../../img/public/praise@2x.png)");
        var num = $(this).find(".num").html();
        var num2 = parseInt(num) - 1;
        $(this).find(".num").html(num2).css("color","#444444");
    })


    
})
