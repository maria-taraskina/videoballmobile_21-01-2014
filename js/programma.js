var pageFull=1;
var pages = new Array();
var strList='';	
var isGroups = true;
var currentGroup = -1;
var allIsLoaded = false;

var prevChannel  = null;
var nextChannel = null;

var programListsReady = false;

function loadProgrammByCategory(){
		$.getJSON( "groups.html?t=ch",
			function(data) {	
		
			$("div#programma-groups .loading").hide();
			if(data.channels){
				var s2='';
			$.each(data.channels, function(i, item) {
					 					
				pages[item.id_grp] = [1,false]; //первонач состояние каждой группы - первая страница и ничего не подгружено - false
				
				s2 += '<div class="ch-group" id="category'+item.id_grp+'" category-id="'+item.id_grp+'" data-role="collapsible" data-theme="d" data-content-theme="d"><h3>'+item.name+'</h3>';
			  	s2 += '<ul data-role="listview" data-inset="true" data-theme="d">';
				//s2 += '<li>some data</li>';
				s2 += '</ul><div class="loading"><span></span></div></div>';
							
				});
		
				$("#programma-groups").html(s2);
				$("#programma-groups").collapsibleset("refresh");
					
				$("#programma-groups div.ch-group a").click(function(){
				var cont = $(this).parent().parent();				
					if(!$(this).parent().hasClass("ui-collapsible-heading ui-collapsible-heading-collapsed")){
						//console.log("свернули");
						currentGroup = -1;
						return;
					}
					
					currentGroup = cont.attr("category-id");
					var loader = cont.children("div.ui-collapsible-content").children("div.loading");
					loader.hide();
					if(pages[currentGroup][0] == 1 && pages[currentGroup][1] == false){
					var curUl = cont.children("div.ui-collapsible-content").children("ul");
					var loader = cont.children("div.ui-collapsible-content").children("div.loading");
					loader.show();
					
					loadFullProgrammList('?n=1&p=1&g='+currentGroup);
					pages[currentGroup][0]++;
				}
									
				});
					
				}
			}			
		);
	
	
}
function buildListView(cont,data){
			cont.html(data);
			cont.listview("refresh");
}
function loadFullProgrammList(parameters){
var s1="";
		programListsReady=false;

		$.getJSON( "programma.html" + parameters,
			function(data) {
			
			$("div#list-wrap .loading").hide();
			programListsReady=true;
			if(data.programma){	
						
				$.each(data.programma, function(i, item) {
					var start1 = 	item.start.substring(item.start.indexOf(" ")+1,item.start.indexOf(" ")+6);
					var stop1 = 	item.stop.substring(item.stop.indexOf(" ")+1,item.stop.indexOf(" ")+6);				  						
					s1 += '<li><a channel="'+item.id_channel+'" href="index.html"><div class="ch-img"><img src="images/' + item.id_channel + '.gif'  + '"></div><div class="ch-name">' + item.name +  '</div><span class="ch-title">' + item.title + '</span><br/><span class="duration">'+ start1 + ' &mdash; ' + stop1  +'</span></a></li>';
				});
				
				if(isGroups == false || pageFull == 1){
				var cont = $("div#list-wrap ul");
				var s0 = cont.html();
					var s = s0 + s1;
					buildListView(cont,s);		
					}
					if(isGroups == true){
						var cont = $("div#category"+currentGroup+" ul");
						var loader = $("div#category"+currentGroup+" div.loading");
						var s0 = cont.html();
						var s = s0 + s1;
						cont.html(s);
			
						cont.each(function( index ) {
						$(this).listview();
						$(this).listview("refresh");
						
						});
						
						loader.hide();
					}
					
					$("#programma-groups li a, div#list-wrap ul li a").click(function(){
						clickChannel($(this));
						return false;
							});
					  
			  }
			  else{
				if(isGroups == false){
					$("div#list-wrap .loading").hide();
					allIsLoaded = true;
					}
			  if(isGroups == true){
					$("div#category"+currentGroup+" div.loading").hide();
					pages[currentGroup][1] = true;
					}
			  }
			}			
		);	
		
}



function clickChannel(obj){
					//if(User == -1) return;
					$("#programma-groups li a, div#list-wrap ul li a").removeClass('ui-btn-active');
					$("#programma-groups li, div#list-wrap ul li").removeClass('ui-focus');
					obj.addClass('ui-btn-active');
					var channel=obj.attr("channel");
						var channelName = obj.children('div.ch-name').html();
						var progTitle = obj.children('span.ch-title').html();
						var duration = obj.children('span.duration').html();
												
						getPrevNextChannels(obj);
						
						/*$.ajax({
						url: "dvbs.html?cmd=stop&u=" + User ,
						dataType: "json",
						success: function (data) {
						console.log("DVBS stop data: ", data);
								//$("#playerMonitor").html( "<b>Статус:</b> " + data.player_state + "<br>" );								
						}
						
						});//ajax end*/
						
						
						checkStatus();
						
						
						$.ajax({
								url: "dvbs.html?cmd=play&u=" + User+"&c="+channel,
								dataType: "json",
								success: function (data) {	
								console.log(data);
								var status = "<b>Канал: </b>"+ channelName+"<br/><b>Передача: </b>"+progTitle+"<br/><b>Время:</b> "+duration;
								//status+="<b>Плеер: </b>"+data.
									$('#userMonitor').html(status);								
								}
								});//ajax end	
						
}
	function getPrevNextChannels(obj){
				prevChannel = obj.parents("li").prev().find("a");
				nextChannel = obj.parents("li").next().find("a");
				if(!nextChannel.length){//если прошли весь список, надо вернуться к первому в списке
					if(isGroups == false ){//ходим по всему списку
					
					if(programListsReady==true && allIsLoaded == false){
						//console.log("подгружаем!!!!");
							$("div#list-wrap .loading").show();					
							pageFull++;
							loadFullProgrammList('?n=1&p='+pageFull);	
							nextChannel = obj.parents("li").next().find("a");							
						}	
					
					if(allIsLoaded == true)
						nextChannel = $('div#list-wrap ul li:first').find("a");
						}
						else {//Ходим внутри группы
							nextChannel = obj.parents(".ch-group").children('div.ui-collapsible-content').children('ul').children('li:first').find("a");
							}
				}
				
				if(!prevChannel.length){//если прошли весь список до первого, надо вернуться к последнему в списке
					if(isGroups == false ){//ходим по всему списку
						prevChannel = $('div#list-wrap ul li:last').find("a");
						}
						else {//Ходим внутри группы
							prevChannel = obj.parents(".ch-group").children('div.ui-collapsible-content').children('ul').children('li:last').find("a");
						}
				}
						
	}
	function setPrevChannel(){
		if(!prevChannel) return;
		clickChannel(prevChannel);
	}
	function setNextChannel(){
		if(!nextChannel) {
		
		//return;
		if(isGroups == false ){
		nextChannel = $("div#list-wrap ul li:first").find("a");
		
		}
		else {//???????????????????????????????????
		$("#programma-groups .ch-group.ui-first-child").addClass("ui-collapsible-collapsed");
		nextChannel = $("#programma-groups .ch-group:first ul li:first").find("a");
		}
		}
		clickChannel(nextChannel);
	}
	$( document ).on( "vmousedown", "div.controls #next", function() {	
		setNextChannel();
			return false;
		});
		
	$( document ).on( "vmousedown", "div.controls #prev", function() {	
		setPrevChannel();
			return false;
		});

$(function() {

	$("#a-group").click(function(){
		$(this).addClass("active");
		$("#a-list").removeClass("active");
		$("#programma-groups").show();
		$("#list-wrap").hide();
		isGroups = true;
	return false;
});

$("#a-list").click(function(){
		$(this).addClass("active");
		$("#a-group").removeClass("active");
		$("#programma-groups").hide();
		$("#list-wrap").show();
		isGroups = false;
	return false;
});

$(window).scroll(function(){

		if(isGroups == false){
		if($(window).scrollTop() == $(document).height() - $(window).height()){
			if(programListsReady==true && allIsLoaded == false){
				//console.log("подгружаем!!!!");
					$("div#list-wrap .loading").show();					
					pageFull++;
					loadFullProgrammList('?n=1&p='+pageFull);					
				}	
			}
		}
		
		
		if(isGroups == true){
		var cont = $("div#category"+currentGroup);
		
		if(cont.offset()){
			var cont_top=cont.offset().top;
			if($(window).scrollTop() >= Math.round(cont.height() + cont_top - $(window).height())){
				if(currentGroup != -1 && pages[currentGroup][1] == false){
						//console.log("подгружаем в группы!!!! "+currentGroup);
						$("div#programma-groups .loading").show();
						loadFullProgrammList('?n=1&p='+pages[currentGroup][0]+'&g='+currentGroup);
						pages[currentGroup][0]++;
						}						
				}
			}
		}
		
		
		
	}); //window.scroll

});

