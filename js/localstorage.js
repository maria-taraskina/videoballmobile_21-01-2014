
function supportsLocalStorage() {
    return ('localStorage' in window) && window['localStorage'] !== null;
}


function saveData(){//console.log("savedata! ",currentPlayers);
 if (!supportsLocalStorage()) { return false; }
 
var userInfo = {user:User,
				players:  currentPlayers
				};
localStorage.setItem('userInfo', JSON.stringify(userInfo));



return true;
}

function restoreData(){
 if (!supportsLocalStorage()) { return false; }
var userInfo = JSON.parse(localStorage.getItem('userInfo'));
User = userInfo.user;

	/*	if(currentPlayers.length) {
		while(currentPlayers.length)currentPlayers.pop();
		};
	 	$.each(userInfo.players, function(i, item) {
			currentPlayers.push(item);							
			});//each end				
		*/
currentPlayers = userInfo.players;
restore=true;
//console.log("restore from userInfo: User = "+userInfo.user,"  currentPlayers = ",userInfo.players);
}













