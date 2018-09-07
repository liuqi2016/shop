//下拉显示
$(document).ready(function(){
	var topMain=$("#toolbar").height()+220//是头部的高度加头部与moquu_iconbox导航之间的距离
	var nav=$("#header");
	$(window).scroll(function(){
		if ($(window).scrollTop()>topMain){//如果滚动条顶部的距离大于topMain则就nav导航就添加类.fixed，否则就移除
			nav.addClass("fixed");
		}else{
			nav.removeClass("fixed");
		}
	});
})
$(document).ready(function(){
	var topMain=$("#header").height()+220//是头部的高度加头部与moquu_iconbox导航之间的距离
	var nav=$("#subnav");
	$(window).scroll(function(){
		if ($(window).scrollTop()>topMain){//如果滚动条顶部的距离大于topMain则就nav导航就添加类.fixed，否则就移除
			nav.addClass("fixed");
		}else{
			nav.removeClass("fixed");
		}
	});
})
//侧边导航
function sidenavIn() {
   $("#sidenav-toggle").toggleClass("show");
    var oDiv = document.createElement('div');
    oDiv.id="sidenav-overlay";  
    oDiv.className="sidenav-overlay";  
    oDiv.style.cssText="display: block;"; 
    oDiv.setAttribute("onclick","sidenavOut()");
    document.body.appendChild(oDiv);
}
function sidenavOut() {
   $("#sidenav-toggle").removeClass("show");
   document.body.removeChild(document.getElementById("sidenav-overlay"));
}