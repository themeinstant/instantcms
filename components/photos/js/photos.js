$(function(){
  photos = {
    publishPhoto: function(photo_id) {
      $.post('/photos/publish'+photo_id+'.html', {}, function(data){
		if(data == 'ok'){
			$('#pub_photo_link').hide();
			$('#pub_photo_wait').hide();
			$('#pub_photo_date').fadeIn();
		} else {
			core.alert('Фото не опубликовано', 'Ошибка');
		}
      });
    },
    movePhoto: function(photo_id) {
      core.message('Перенести фотографию');
      $.post('/photos/movephoto'+photo_id+'.html', {}, function(data){
		if(data.error == false){
			$('#popup_message').html(data.html);
			$('#popup_progress').hide();
			$('#popup_ok').val('Перенести').show();
			$('#popup_ok').click(function(){
				$('#popup_ok').attr('disabled', 'disabled');
				$('#popup_progress').show();
				var options = { 
					success: photos.domovePhoto
				};
				$('#move_photo_form').ajaxSubmit(options);
			});
		} else {
			core.alert(data.text, 'Ошибка');
		}
      }, 'json');
    },
    domovePhoto: function(result, statusText, xhr, $form){
		$('#popup_progress').hide();
		if(statusText == 'success'){
			if(result.error == false){
				window.location.href = result.redirect;
			} else {
				core.alert(result.text, 'Ошибка');
			}
		} else {
			core.alert(statusText, 'Ошибка');
		}
    },
    editPhoto: function(photo_id) {
      core.message('Редактирование фотографии');
      $.post('/photos/editphoto'+photo_id+'.html', {}, function(data){
		if(data.error == false){
			$('#popup_message').html(data.html);
			$('#popup_progress').hide();
			$('#popup_ok').val('Сохранить').show();
			$('#popup_ok').click(function(){
				$('#popup_ok').attr('disabled', 'disabled');
				$('#popup_progress').show();
				var options = { 
					success: photos.doeditPhoto
				};
				$('#edit_photo_form').ajaxSubmit(options);
			});
		} else {
			core.alert(data.text, 'Ошибка');
		}
      }, 'json');
    },
    doeditPhoto: function(result, statusText, xhr, $form){
		$('#popup_progress').hide();
		if(statusText == 'success'){
			if(result.error == false){
				window.location.href = result.redirect;
			}
		} else {
			core.alert(statusText, 'Ошибка');
		}
    },
    deletePhoto: function(photo_id, csrf_token) {
		core.confirm('Вы действительно хотите удалить эту фотографию?', null, function(){
			$.post('/photos/delphoto'+photo_id+'.html', {csrf_token: csrf_token}, function(result){
				if(result.error == false){
					window.location.href = result.redirect;
				}
			}, 'json');
		});
    }
  }
});
