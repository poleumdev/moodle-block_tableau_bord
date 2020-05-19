// Fonctions pour utiliser un objet XMLHttpRequest

var xhr = getXMLHttpRequest();

xhr.onreadystatechange = function() {
		if (xhr.readyState == 4 && (xhr.status == 200 || xhr.status == 0)) {
				//alert(xhr.responseText); // Pop-up avec les donnees textuelles recuperees du formulaire
		}
};

function getXMLHttpRequest() {
	var xhr = null;
	
	if (window.XMLHttpRequest || window.ActiveXObject) {
		if (window.ActiveXObject) {
			try {
				xhr = new ActiveXObject("Msxml2.XMLHTTP");
			} catch(e) {
				xhr = new ActiveXObject("Microsoft.XMLHTTP");
			}
		} else {
			xhr = new XMLHttpRequest(); 
		}
	} else {
		alert("Votre navigateur ne supporte pas lobjet XMLHTTPRequest...");
		return null;
	}													
	return xhr;
}
