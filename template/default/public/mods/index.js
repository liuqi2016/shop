//幻灯片
$(function() {
	jQuery.slide = function(slid) {
		var sWidth = $(slid).width(); //获取幻灯片图的宽度（显示面积）
		var len = $(slid).find("ul li").length; //获取幻灯片图个数
		var index = 0;
		var picTimer;
		
		//以下代码添加数字按钮和按钮后的半透明条，还有上一页、下一页两个按钮
		var btn = "<div class='btnBg'></div><div class='btn'>";
		for(var i=0; i < len; i++) {
			var ii = i+1;
			btn += "<span>"+ii+"</span>";
		}
		btn += "</div><div class='preNext pre'></div><div class='preNext next'></div>";
		$(slid).append(btn);
		$(slid).find("div.btnBg").css("opacity",0.5);
	
		//为小按钮添加鼠标滑入事件，以显示相应的内容
		$(slid+" div.btn span").css("opacity",0.4).mouseenter(function() {
			index = $(slid+" .btn span").index(this);
			showPics(index);
		}).eq(0).trigger("mouseenter");
	
		//上一页、下一页按钮透明度处理
		$(slid+" .preNext").css("opacity",0.2).hover(function() {
			$(this).stop(true,false).animate({"opacity":"0.5"},300);
		},function() {
			$(this).stop(true,false).animate({"opacity":"0.2"},300);
		});
	
		//上一页按钮
		$(slid+" .pre").click(function() {
			index -= 1;
			if(index == -1) {index = len - 1;}
			showPics(index);
		});
	
		//下一页按钮
		$(slid+" .next").click(function() {
			index += 1;
			if(index == len) {index = 0;}
			showPics(index);
		});
	
		//本例为左右滚动，即所有li元素都是在同一排向左浮动，所以这里需要计算出外围ul元素的宽度
		$(slid+" ul").css("width",sWidth * (len));
		
		//鼠标滑上幻灯片图时停止自动播放，滑出时开始自动播放
		$(slid).hover(function() {
			clearInterval(picTimer);
		},function() {
			picTimer = setInterval(function() {
				showPics(index);
				index++;
				if(index == len) {index = 0;}
			},2000); //此4000代表自动播放的间隔，单位：毫秒
		}).trigger("mouseleave");
		
		//显示图片函数，根据接收的index值显示相应的内容
		function showPics(index) { //普通切换
			var nowLeft = -index*sWidth; //根据index值计算ul元素的left值
			$(slid+" ul").stop(true,false).animate({"left":nowLeft},300); //通过animate()调整ul元素滚动到计算出的position
			$(slid+" .btn span").removeClass("active").eq(index).addClass("active"); //为当前的按钮切换到选中的效果
			$(slid+" .btn span").stop(true,false).animate({"opacity":"1"},300).eq(index).stop(true,false).animate({"opacity":"1"},300); //为当前的按钮切换到选中的效果
		}
	};	
});

//下拉显示
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