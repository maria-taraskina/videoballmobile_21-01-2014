var currentPlayers;//=  new Array();
var User;// = -1;    // Выбранный канал доступа
var Volume;// = 0;   // Установленная громкость на плеере
var ChannelRunning = 0;

var restore=false;
       
function checkStatus(){
	$.ajax({
						url: "dvbs.html?cmd=status" ,
						dataType: "json",
						success: function (data) {
						console.log("DVBS status data: ", data);
								//$("#playerMonitor").html( "<b>Статус:</b> " + data.player_state + "<br>" );								
						}
						
						});
}


function load_players() {

if(currentPlayers == undefined)currentPlayers =  new Array();
	$(function() {
		// Загрузка медиаплееров
		$.getJSON( "players.html", 
			function(data) {
			    s = '';				
 				$.each(data.players, function(i, item) {
				sel='';
				if(currentPlayers.length){
					$.each(currentPlayers, function(k, item) {
						if(i==Number(item))		sel=" selected=selected ";								
						});//each end
					}		
					s += "<option "+sel+"value='"+ i +"' id='p" + i + "' onclick='SelectPlayer(" + i + ")' >"+item.name+"</option>";			
				});
				
			$("#players").html(s);
			$( "#players" ).selectmenu( "refresh" );
			//$( "#players" ).selectmenu( "option", "overlayTheme", "b" );

			$('#players').on('change', function () {
				var $this = $(this),
					val   = $this.val();
					restore=false;
					SelectPlayer(val);
			});
			
			SelectPlayer(currentPlayers);

			}
		);
	});	
}



	// Выбор пользователя или смена пользователя, должна показывать чем занята "голова" DVB приемника 
	function SelectUser(v) {//console.log("selectUser: ",v);
	   if ( v == -1 || v == undefined) return;
	   console.log("after selectUser: ",v);
					   $('select#user-nc').removeAttr('selected');
                       $("#user-nc option[value="+v+"]").attr("selected","selected");
                       $('#user-nc').selectmenu("refresh");
		
		User = v;
		url = "run/iptv-" + User + ".json";

		// Загрузка данных по пользователю
 		$.getJSON( url,
			function(data) {
			    // Если есть вещание показываем кто смотрит
			    /*if (data.DVBtoIP.cmd == 'play') {
				$("#userMonitor").html( "<b>Вещание:</b> " + data.DVBtoIP.channel_id + "<br/>")
				$("#userMonitor").append( "<b>Программа:</b> " + data.DVBtoIP.programma.title + "<br/>")
							
			        for( var i in data.PLAYER ) {				
				    $("#userMonitor").append( "<b>Плеер:</b> " + data.PLAYER[i].name  + "<br/>" );
				    $("#userMonitor").append( "<b>Громкость:</b> " + data.PLAYER[i].playback_volume + "<br/>" );				
				}
				
			    } else {
				//$("#stop").hide();
			    }*/
			}
		);
				
saveData();
	}
	
	function turnOffPlayer(v){
	$.ajax({
						url: "dune.html?cmd=off&tv=" + v ,
						dataType: "json",
						success: function (data) {
								$("#playerMonitor").html( "<b>Статус:</b> " + data.player_state + "<br>" );
								//	$("#playerMonitor").append( "<b>Канал:</b> " + data.playback_url + "<br>" );
								//$("#playerMonitor").append( "<b>Громкость:</b> " + data.playback_volume );
						},
						statusCode: {
							200: function () {
								// $("#stop").show(); 	
							}
						}
						});//ajax end
						
						
			
						
						
						
						
						//console.log($("#players option[value="+v+"]"));
						 $("#players option[value="+v+"]").removeAttr('selected');
                       $('#players').selectmenu("refresh");		
	}
	
	function turnOnPlayer(v){
	$.ajax({
						url: "dune.html?cmd=play&u=" + User + "&tv=" + v ,
						dataType: "json",
						success: function (data) {
						
						 /*$('select#user-nc').removeAttr('selected');
                       $("#user-nc option[value="+v+"]").attr("selected","selected");
                       $('#user-nc').selectmenu("refresh");*/
					   
					   
								//var result = $("#playerMonitor").empty();						
							$("#VolumePanel").mbsetVal( data.playback_volume );
							$("#VolumePanel").show();											
							$("#playerMonitor").html( "<b>Статус:</b> " + data.player_state + "<br>" );
							$("#playerMonitor").append( "<b>Канал:</b> " + data.playback_url + "<br>" );
							$("#playerMonitor").append( "<b>Громкость:</b> " + data.playback_volume );											
						},
						statusCode: {
							200: function () {
								// $("#stop").show(); 	
							}
						}
						});//ajax end
	}
	
	
	function SelectPlayer(v) {//console.log("SelectPlayer "+v);
		if ( v == -1) return;
			
		if(restore){//если идёт восстановление данных, то мы просто включаем все восстанавливаемые плееры		
					for (s in currentPlayers){	
					turnOnPlayer(currentPlayers[s]);				
					}		
		}
		if(!restore){
				var previousPlayersStatus = new Array();
			if(previousPlayersStatus.length) {
			while(previousPlayersStatus.length)previousPlayersStatus.pop();
			};
			if(currentPlayers.length) {
			while(currentPlayers.length)previousPlayersStatus.push(currentPlayers.pop());
			previousPlayersStatus.reverse();
			};
				
			
			if(v){
			$.each(v, function(i, item) {
				currentPlayers.push(item);	
				});//each end
			}
		
	saveData();

	if(previousPlayersStatus.length < currentPlayers.length){// если было выбрано меньше, чем стало, значит что-то включили
		for (s in currentPlayers){
			if(previousPlayersStatus[s] != currentPlayers[s]){
			console.log("TurnON: ",currentPlayers[s]);			
						turnOnPlayer(currentPlayers[s]);	
				return;
			}			
		}
	}

	if(previousPlayersStatus.length && previousPlayersStatus.length > currentPlayers.length){// если было выбрано больше, чем стало, значит что-то выключили
		for (s in previousPlayersStatus){
			if(previousPlayersStatus[s] != currentPlayers[s]){
			console.log("TurnOFF: ",previousPlayersStatus[s]);			
						turnOffPlayer(previousPlayersStatus[s]);	
				return;
			}				
		}
	}	
  }//if(!restore)
}
	
function absorbEvent_(event) {
      var e = event || window.event;
      e.preventDefault && e.preventDefault();
      e.stopPropagation && e.stopPropagation();
      e.cancelBubble = true;
      e.returnValue = false;
      return false;
    }

    function preventLongPressMenu(node) {
      node.ontouchstart = absorbEvent_;
      node.ontouchmove = absorbEvent_;
      node.ontouchend = absorbEvent_;
      node.ontouchcancel = absorbEvent_;
    }
	
	
	
var intUp;
var intDown;

//$( document ).ready(function() {
$( document ).on( "pageinit", "#main_page", function( event ) {


		//preventLongPressMenu($("div.controls #up"));

	$('#userMonitor').html("Ready");
	
SelectUser(User);


$('#user-nc').on('change', function () {
				var $this = $(this),
					val   = $this.val();
					if(currentPlayers.length) {
					while(currentPlayers.length)turnOffPlayer(currentPlayers.pop());
					};
					//restore=false;
					//SelectPlayer(currentPlayers);
					SelectUser(val);
			});
			
			
	function clickUp(){
	var value = $("#slider-volume").val();
	var newVal=Number(value)+1;
		volumeUp();
		
	$("#slider-volume").val(newVal).slider("refresh");
}	
			
	$("div.controls #up,div.controls #down,div.controls #next,div.controls #prev,div.controls #stop").bind( "vclick", function(e){
		return false;
		});
	$( document ).on( "vmousedown", "div.controls #up", function() {	
		intUp=setInterval(function(){
			var value = $("#slider-volume").val();
			var newVal=Number(value)+1;
			volumeUp();		
			
			$("#slider-volume").val(newVal).slider("refresh");
		},100);

			return false;
		});
			
function volumeUp(){
	for (c in currentPlayers){	
				$.ajax({
					url: "dune.html?cmd=volume_up&tv=" + currentPlayers[c] ,
					dataType: "json",
					success: function (data) {									
					}
				});//ajax end	
			}		
}
$( document ).on( "vmousedown", "div.controls #down", function() {	
		intDown=setInterval(function(){
			var value = $("#slider-volume").val();
			var newVal=Number(value)-1;
			volumeDown();		
			
			$("#slider-volume").val(newVal).slider("refresh");
		},100);

			return false;
		});
function volumeDown(){
	for (c in currentPlayers){	
				$.ajax({
					url: "dune.html?cmd=volume_down&tv=" + currentPlayers[c] ,
					dataType: "json",
					success: function (data) {									
					}
				});//ajax end	
			}		
}



$( document ).on( "vmouseup", "div.controls #up", function() {	
	if(intUp)clearInterval(intUp);
	return false;
});

$( document ).on( "vmouseup", function() {
	if(intUp)clearInterval(intUp);
	if(intDown)clearInterval(intDown);
	return false;
});


$( document ).on( "vmouseup", "div.controls #down", function() {
	if(intDown)clearInterval(intDown);
	return false;
});


$("a#mute").click(function(){	
	if(!currentPlayers.length)return;
	
	for (c in currentPlayers){	
	$.ajax({
			url: "dune.html?cmd=mute&tv=" + currentPlayers[c] ,
			dataType: "json",
			success: function (data) {									
			}
	    	});//ajax end
	}
	return false;
});




});
