$(function(){
  forum = {
    votePost: function(post_id, vote) {
        $('#votes'+post_id).html('<img src="/images/ajax-loader.gif" border="0"/>');
        $.post('/components/forum/vote.php', { post_id: post_id, vote: vote }, function(data){
            $('#votes'+post_id).html(data);
        });
    },
    getUserActivity: function(sub_do, link, page) {
        $('.component').css({opacity:0.4, filter:'alpha(opacity=40)'});
        $.post(link, { sub_do: sub_do, page: page, of_ajax: 1 }, function(data){
            $('.component').html(data);
            $('.component').css({opacity:1.0, filter:'alpha(opacity=100)'});
        });
    },
    revotePoll: function(thread_id) {
        $('#thread_poll').css({opacity:0.4, filter:'alpha(opacity=40)'});
        $.post('/forum/viewpoll'+thread_id, { revote: 1 }, function(data){
            $('#thread_poll').html(data);
            $('#thread_poll').css({opacity:1.0, filter:'alpha(opacity=100)'});
        });
    },
    loadForumPoll: function(thread_id, show_result) {
        $('#thread_poll').css({opacity:0.4, filter:'alpha(opacity=40)'});
        $.post('/forum/viewpoll'+thread_id, { show_result: show_result }, function(data){
            $('#thread_poll').html(data);
            $('#thread_poll').css({opacity:1.0, filter:'alpha(opacity=100)'});
        });
    },
    deletePoll: function(thread_id, csrf_token) {
        core.confirm('Вы уверены что хотите удалить этот опрос?', null, function() {
			$.post('/forum/delete_poll'+thread_id, {csrf_token: csrf_token}, function(result){
                $('#thread_poll').html('');
			});
        });
    },
    deletePost: function(post_id, csrf_token, page) {
        core.confirm('Вы уверены что хотите удалить этот пост?', null, function() {
			$.post('/forum/deletepost'+post_id+'.html', {csrf_token: csrf_token, page: page}, function(result){
				if(result.error == false){
					window.location.href = result.redirect;
				}
			}, 'json');
        });
    },
    ocThread: function(thread_id, action) {
        if(action == 1){
            href = '/forum/closethread'+thread_id+'.html';
        } else {
            href = '/forum/openthread'+thread_id+'.html';
        }
        $.post(href, {}, function(data){
            if(data == 1){
                $('.closethread').hide();
                $('.openthread').fadeIn();
            } else {
                $('.openthread').hide();
                $('.closethread').fadeIn();
            }
        });
    },
    pinThread: function(thread_id, action) {
        if(action == 1){
            href = '/forum/pinthread'+thread_id+'.html';
        } else {
            href = '/forum/unpinthread'+thread_id+'.html';
        }
        $.post(href, {}, function(data){
            if(data == 1){
                $('.pinthread').hide();
                $('.unpinthread').fadeIn();
            } else {
                $('.unpinthread').hide();
                $('.pinthread').fadeIn();
            }
        });
    },
    deleteThread: function(thread_id, csrf_token) {
        core.confirm('Вы уверены что хотите удалить тему?', null, function() {
			$.post('/forum/deletethread'+thread_id+'.html', {csrf_token: csrf_token}, function(result){
				if(result.error == false){
					window.location.href = result.redirect;
				}
			}, 'json');
        });
    },
    moveThread: function(thread_id) {
      core.message('Перенести тему');
      $.post('/forum/movethread'+thread_id+'.html', {}, function(data){
		if(data.error == false){
			$('#popup_message').html(data.html);
			$('#popup_progress').hide();
			$('#popup_ok').val('Перенести').show();
			$('#popup_ok').click(function(){
				$('#popup_ok').attr('disabled', 'disabled');
				$('#popup_progress').show();
				$('#movethread_form').submit();
			});
		} else {
			core.alert(data.text, 'Ошибка');
		}
      }, 'json');
    },
    movePost: function(thread_id, post_id) {
      core.message('Перенести сообщение в другую тему');
      $.post('/forum/movepost.html', {id: thread_id, post_id: post_id}, function(data){
		if(data.error == false){
			$('#popup_message').html(data.html);
			$('#popup_progress').hide();
			$('#popup_ok').val('Перенести').show();
			$('#popup_ok').click(function(){
				$('#popup_ok').attr('disabled', 'disabled');
				$('#popup_progress').show();
				$('#movethread_form').submit();
			});
		} else {
			core.alert(data.text, 'Ошибка');
		}
      }, 'json');
    },
    renameThread: function(thread_id) {
      core.message('Переименовать тему');
      $.post('/forum/renamethread'+thread_id+'.html', {}, function(data){
		if(data.error == false){
			$('#popup_message').html(data.html);
			$('#popup_progress').hide();
			$('#popup_ok').val('Переименовать').show();
			$('#popup_ok').click(function(){
				$('#popup_ok').attr('disabled', 'disabled');
				$('.ajax-loader').show();
				var options = {
					success: forum.doRenameThread
				};
				$('#renamethread_form').ajaxSubmit(options);
			});
		} else {
			core.alert(data.text, 'Ошибка');
		}
      }, 'json');
    },
    doRenameThread: function(result, statusText, xhr, $form){
		$('.ajax-loader').hide();
		if(statusText == 'success'){
			if(result.error == false){
				$('#thread_title').html(result.title).fadeIn();
				$('#thread_description').html(result.description).fadeIn();
			}
            core.box_close();
		} else {
			core.alert(statusText, 'Ошибка');
		}
    },
    deleteFile: function(file_id, csrf_token, post_id) {
        core.confirm('Вы уверены что хотите удалить этот файл?', null, function() {
            file_count = $('#file_count').val();
			$.post('/forum/delfile'+file_id+'.html', {csrf_token: csrf_token}, function(result){
				if(result == 1){
                    if(file_count == 1){
                        $('#fa_attach_'+post_id).css('background', '#FFAEAE').fadeOut();
                    } else {
                        $('#filebox'+file_id).css('background', '#FFAEAE').fadeOut();
                        $('#file_count').val(file_count-1);
                    }
				}
			});
        });
    },
    reloadFile: function(file_id) {
      core.message('Выберите новый файл для загрузки:');
      $.post('/forum/reloadfile'+file_id+'.html', {}, function(data){
		if(data.error == false){
			$('#popup_message').html(data.html);
			$('#popup_progress').hide();
			$('#popup_ok').val('Сохранить').show();
			$('#popup_ok').click(function(){
				$('#popup_ok').attr('disabled', 'disabled');
				$('.ajax-loader').show();
				var options = {
					success: forum.doReloadFile
				};
				$('#reload_file').ajaxSubmit(options);
			});
		} else {
			core.alert(data.text, 'Ошибка');
		}
      }, 'json');
    },
    doReloadFile: function(result, statusText, xhr, $form){
		$('.ajax-loader').hide();
        $('.sess_messages').fadeOut();
		if(statusText == 'success'){
			if(result.error == false){
				$('#attached_files_'+result.post_id).html(result.html);
                core.box_close();
			} else {
				$('#error_mess').html(result.text);
				$('.sess_messages').fadeIn();
				$('#popup_ok').attr('disabled', '');
            }
		} else {
			core.alert(statusText, 'Ошибка');
		}
    },
    addQuoteText: function(obj) {
        author = $(obj).attr('rel');
        seltext = '';
        if (window.getSelection) {
            seltext = window.getSelection().toString();
        } else if (document.getSelection) {
            seltext = document.getSelection().toString();
        } else if (document.selection) {
            seltext = document.selection.createRange().text;
        }
        if (seltext){
            quote = '[quote='+author+']' + seltext + '[/quote]' + "\n";
            msg = $('textarea#message').val() + quote;
            $('textarea#message').val(msg);
        } else {
            core.alert('Выделите текст для цитирования!', 'Предупреждение')
        }
    }
  }
});