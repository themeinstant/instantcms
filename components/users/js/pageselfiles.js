function orderPage(field){
	$("#orderby").attr('value', field);
	document.orderform.submit();
}

function checkSelFiles(){
	var sel =false;
	for(i=0; i<25; i++){
	 if($("#fileid"+i).attr('checked')){
		sel = true;
	 }
	}
	return sel;
}

function delFiles(title){
	var sel = checkSelFiles();
	if (sel == false){
	 	core.alert('Нет выбранных файлов!', 'Ошибка');
	} else {
		core.confirm(title, null, function(){
            $("#listform").attr('action', 'delfilelist.html');
            document.listform.submit();
		});
	}
}

function pubFiles(flag){
	var sel = false;
	for(i=0; i<25; i++){
	 if($("#fileid"+i).attr('checked')){
		sel = true;
	 }
	}
	if (sel == false){
	 	core.alert('Нет выбранных файлов!', 'Ошибка');
	} else {
		if(flag==1){
		 $("#listform").attr('action', 'showfilelist.html');
		} else {
		 $("#listform").attr('action', 'hidefilelist.html');
		}
		document.listform.submit();
	}
}
