//点赞
function like(id){
    $.post('/mobile/article/give_a_like',{'id':id},function(data){
        console.log(data);
        if (data.code == 0) {
            alert("服务器繁忙，请联系管理员！");
            return false;
        }
    });
}