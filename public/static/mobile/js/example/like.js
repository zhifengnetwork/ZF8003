//点赞
function like(user_id,id){
    console.log(user_id);
    user_id = parseInt(user_id);
    if (!user_id) {
        layer.msg('请登录后再点赞',{'icon':7,'time':1000});
        return false;
    } else {
        $.post('/mobile/like/give_a_like',{'id':id},function(data){
            console.log(data);
            if (data.code == 0) {
                layer.alert("服务器繁忙，请联系管理员！");
                return false;
            }
        });
    }
}