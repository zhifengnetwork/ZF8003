







/**
 * 选中菜单
 */
$('.menu').on('click', function(){
    $('.menu').removeClass('selected_menu');
    $(this).addClass('selected_menu');
    let item = $(this).attr('item');
    $('.two_menu').css('display','none');
    $('.atm'+item).css('display','block');


});



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
