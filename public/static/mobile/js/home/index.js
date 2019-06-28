$(function() {
    /*
    * 点赞
    */
   $(".praiseBtn").click(function(){
        var that = $(this);
        if(that.hasClass("praised")){
            $(this).removeClass("praised");
            var num = $(this).find(".num").html();
            num -=1;
            $(this).find(".num").html(num); 
        }else{
            $(this).addClass("praised");
            var num = parseInt($(this).find(".num").html());
            num += 1;
            $(this).find(".num").html(num);
        }
    
    })
    
})
