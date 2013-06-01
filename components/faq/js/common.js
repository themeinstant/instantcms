function sendQuestion(){
	if($('#faq_message').attr('value').length < 10){
	 	core.alert('Ваш вопрос слишком короткий!', 'Ошибка');	
	} else {
		document.questform.submit();	
	}	
}