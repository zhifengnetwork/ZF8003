// 头部title
var NavTitle = [
    "首页",
    "我的基因",
    "购买",
    "基因介绍",
    "我的订单",
    "关于我们"
];

// 导航url
var NavUrl = [
    "/index/index/index",
    "/index/gene/index",
    "/index/buy/buy",
    "/index/referral/referral",
    "/index/order/index",
    "/index/about/index",
];

// 导航title循环
var NavdStr = '';
for(var i = 0; i < NavTitle.length; i++) {
    NavdStr += `
    <li><a href="${NavUrl[i]}">${NavTitle[i]}</a></li>
    `
}

var head_check = $('#head_check').val()
if (head_check == 1){
    var header = (
        `
    <div class="inner-wrap">
        <div class="logo">
            <a href="index.html"></a>
        </div>
        <div class="nav">
            <ul>`+ NavdStr + `</ul>
        </div>
        <div class="login">
            <!-- 登录状态 -->
            <div class="login-in">
                <span class="fl"><img src="/public/static/index/images/public/user-avatar.png"></span>
                <a class="outBtn fl" href="javascript:void(0)" onclick="logout()">退出</a>
            </div>
        </div>
    </div>
`);
}else{
    var header = (
        `
    <div class="inner-wrap">
        <div class="logo">
            <a href="index.html"></a>
        </div>
        <div class="nav">
            <ul>`+ NavdStr + `</ul>
        </div>
        <div class="login">
            <!-- 未登录状态 -->
            <div class="login-in">
                <a href="/index/login/login">登录</a>
                <a href="/index/login/register">注册</a>
            </div>

        </div>
    </div>
`);    
}
// 渲染页面
// var header = (
//     `
//     <div class="inner-wrap">
//         <div class="logo">
//             <a href="index.html"></a>
//         </div>
//         <div class="nav">
//             <ul>`+ NavdStr +`</ul>
//         </div>
//         <div class="login">
//             <!-- 未登录状态 -->
//             <div class="login-out">
//                 <a href="/index/login/login">登录</a>
//                 <a href="/index/login/register">注册</a>
//             </div>
//             <!-- 登录状态 -->
//             <div class="login-in">
//                 <span class="fl"><img src="/public/static/index/images/public/user-avatar.png"></span>
//                 <a class="outBtn fl" href="javascript:void(0)">退出</a>
//             </div>
//         </div>
//     </div>
// `);
$(".header").html(header)

// 更换当前状态
var thisInd = Number($.trim($('.pageTopTitle').attr('page-id')));
$(".nav li").eq(thisInd).addClass('active').siblings().removeClass('active')