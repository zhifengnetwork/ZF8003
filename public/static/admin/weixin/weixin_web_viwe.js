











$('.menu_type').on('change',function(){
    let menu_type = $('.menu_type:checked').val();
    $('.conf_url_content').css('display','none');
    $('.conf_source_content').css('display','none');

    if(menu_type == 1){
        $('.conf_url_content').css('display','block');

    }else if(menu_type == 2){

        $('.conf_source_content').css('display','block');
    }

});
