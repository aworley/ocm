function insert_interview(str) {
	if (str.length < 1) { return; }

	xmlHttp=GetXmlHttpObject();
	if (xmlHttp==null) {
  		alert ("Your browser does not support AJAX!");
  		return;
	} 
	
	var url="%%[base_url]%%/services/interview-server-ajax.php";
	url=url+"?interview_id="+str;
	xmlHttp.onreadystatechange=interviewStateChanged;
	xmlHttp.open("GET",url,true);
	xmlHttp.send(null);
}

function interviewStateChanged() 
{
	
	if (xmlHttp.readyState==4 || xmlHttp.readyState=="complete") { 
		var interview_text = xmlHttp.responseText;
		var int_index = document.ws.interviews.selectedIndex;
		var int_text = document.ws.interviews.options[int_index].text;

		var summary = document.ws.summary;
		if(summary.value != null) { summary.value += int_text; }
		var notes = document.ws.notes;
		if(notes.value != null) { notes.value += interview_text; }
		
	}

	return;
}
