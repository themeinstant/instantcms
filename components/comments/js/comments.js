function addComment(target, target_id, parent_id){
	core.message('Новый комментарий');
	$.post('/components/comments/addform.php', {target: target, target_id: target_id, parent_id: parent_id}, function(data) {
		if(data){
		  $('#popup_message').html(data);
		  is_form = $('#msgform').html();
		  if(is_form != null){
			 $('#popup_ok').val(core.send).show();
		  }
		  $('#popup_progress').hide();
		}
	});
	$('#popup_ok').click(function() {
		$('#popup_ok').attr('disabled', 'disabled');
		$('#popup_progress').show();
		var options = {
			success: showResponseAdd,
			dataType: 'json'
		};
		$('#msgform').ajaxSubmit(options);
	});
}

function showResponseAdd(result, statusText, xhr, $form){

	$('#popup_progress').hide();
	$('.sess_messages').fadeOut();

	if(statusText == 'success'){
		if(result.error == true){
			$('#error_mess').html(result.text);
			$('.sess_messages').fadeIn();
			if(result.is_captcha){
				reloadCaptcha('kcaptcha1');
			}
			$('#popup_ok').attr('disabled', '');
		} else {
			if(result.is_premod){
				core.alert(result.is_premod, 'Внимание!');
			} else {
				core.box_close();
				loadComments(result.target, result.target_id, false);
				total_page = Number($('#comments_count').html());
				$('#comments_count').html(total_page+1);
			}
		}
	} else {
		core.alert(statusText, 'Ошибка');
	}

}

function showResponseEdit(result, statusText, xhr, $form){

	$('#popup_progress').hide();
	$('.sess_messages').fadeOut();

	if(statusText == 'success'){
		if(result.error == true){
			$('#error_mess').html(result.text);
			$('.sess_messages').fadeIn();
			$('#popup_ok').attr('disabled', '');
		} else {
			core.box_close();
			$('#cm_msg_'+result.comment_id).html(result.text);
		}
	} else {
		core.alert(statusText, 'Ошибка');
	}

}

function editComment(comment_id, csrf_token){
	core.message('Редактировать комментарий');
	$.post('/components/comments/addform.php', {action: 'edit', id: comment_id, csrf_token: csrf_token}, function(data) {
		if(data) {
		  $('#popup_ok').val(core.send).show();
		  $('#popup_message').html(data);
		  $('#popup_progress').hide();
		}
	});
	$('#popup_ok').click(function(){
		$('#popup_ok').attr('disabled', 'disabled');
		$('#popup_progress').show();
		var options = {
			success: showResponseEdit,
			dataType: 'json'
		};
		$('#msgform').ajaxSubmit(options);
	});
}

function deleteComment(comment_id, csrf_token, is_delete_tree) {
	core.confirm('Удалить комментарий?', null, function() {
		$.post('/comments/delete/'+comment_id, {csrf_token: csrf_token}, function(result){
			if(result.error == false){
				if(is_delete_tree != 1){
					$('#cm_addentry'+comment_id).parent().css('background', '#FFAEAE').fadeOut();
					total_page = Number($('#comments_count').html());
					$('#comments_count').html(total_page-1);
				}
                loadComments(result.target, result.target_id, false);
			}
		}, 'json');
	});
}

function expandComment(id){
	$('a#expandlink'+id).hide();
	$('div#expandblock'+id).show();
}

function loadComments(target, target_id, anchor){

    $('div.component').css({opacity:0.4, filter:'alpha(opacity=40)'});

    $.ajax({
			type: "POST",
			url: "/components/comments/comments.php",
			data: "target="+target+"&target_id="+target_id,
			success: function(data){
				$('div.cm_ajax_list').html(data);
                $('td.loading').html('');
                if (anchor){
                    window.location.hash = anchor.substr(1, 100);
                    $('a[href='+anchor+']').css('color', 'red').attr('title', 'Вы пришли на страницу по этой ссылке');
                }
				$('div.component').css({opacity:1.0, filter:'alpha(opacity=100)'});
			}
    });

}

function goPage(dir, field, target, target_id){

	var p = Number($('#'+field).attr('value')) + dir;
    loadComments(target, target_id, p);

}

function voteComment(comment_id, vote){

    $('span#votes'+comment_id).html('<img src="/images/ajax-loader.gif" border="0"/>');
    $.ajax({
			type: "POST",
			url: "/components/comments/vote.php",
			data: "comment_id="+comment_id+"&vote="+vote,
			success: function(data){
				$('span#votes'+comment_id).html(data);
			}
    });

}