$(function(){
  blogs = {
    editBlog: function(blog_id) {
      core.message('Настройка блога');
      $.post('/blogs/'+blog_id+'/editblog.html', {}, function(data){
		if(data.error == false){
			$('#popup_message').html(data.html);
			$('#popup_progress').hide();
			$('#popup_ok').val('Сохранить').show();
			$('#popup_panel').prepend('<input id="delete_blog" type="button" class="button_yes" value="Удалить блог"/>');
			$('#popup_ok').click(function(){
				$('#popup_ok').attr('disabled', 'disabled');
				$('#authorslist option').attr("selected","selected");
				$('.ajax-loader').show();
				var options = { 
					success: blogs.doeditBlog
				};
				$('#cfgform').ajaxSubmit(options);
			});
			$('#delete_blog').click(function(){
				$('#delete_blog').attr('disabled', 'disabled');
				csrf_token = $('#csrf_token').val();
				core.confirm('Вы действительно хотите удалить этот блог, включая его содержимое?', null, function(){
					$.post('/blogs/'+blog_id+'/delblog.html', {csrf_token: csrf_token}, function(result){
						if(result.error == false){
							window.location.href = result.redirect;
						}
					}, 'json');
				});
			});
		} else {
			core.alert(data.text, 'Ошибка');
		}
      }, 'json');
    },
    doeditBlog: function(result, statusText, xhr, $form){
		$('.ajax-loader').hide();
		$('.sess_messages').fadeOut();
		if(statusText == 'success'){
			if(result.error == false){
				window.location.href = result.redirect;
			} else {
				$('#error_mess').html(result.text);
				$('.sess_messages').fadeIn();
				$('#popup_ok').attr('disabled', '');
			}
		} else {
			core.alert(statusText, 'Ошибка');
		}
    },
    addBlogCat: function(blog_id) {
      core.message('Создание рубрики блога');
      $.post('/blogs/'+blog_id+'/newcat.html', {}, function(data){
		if(data.error == false){
			$('#popup_message').html(data.html);
			$('#popup_progress').hide();
			$('#popup_ok').val('Создать').show();
			$('#popup_ok').click(function(){
				$('#popup_ok').attr('disabled', 'disabled');
				$('.ajax-loader').show();
				var options = { 
					success: blogs.doBlogCat
				};
				$('#addform').ajaxSubmit(options);
			});
		} else {
			core.alert(data.text, 'Ошибка');
		}
      }, 'json');
    },
    editBlogCat: function(cat_id) {
      core.message('редактирование рубрики блога');
      $.post('/blogs/editcat'+cat_id+'.html', {}, function(data){
		if(data.error == false){
			$('#popup_message').html(data.html);
			$('#popup_progress').hide();
			$('#popup_ok').val('Сохранить').show();
			$('#popup_ok').click(function(){
				$('#popup_ok').attr('disabled', 'disabled');
				$('.ajax-loader').show();
				var options = { 
					success: blogs.doBlogCat
				};
				$('#addform').ajaxSubmit(options);
			});
		} else {
			core.alert(data.text, 'Ошибка');
		}
      }, 'json');
    },
    doBlogCat: function(result, statusText, xhr, $form){
		$('.ajax-loader').hide();
		$('.sess_messages').fadeOut();
		if(statusText == 'success'){
			if(result.error == false){
				window.location.href = result.redirect;
			} else {
				$('#error_mess').html(result.text);
				$('.sess_messages').fadeIn();
				$('#popup_ok').attr('disabled', '');
			}
		} else {
			core.alert(statusText, 'Ошибка');
		}
    },
    deleteCat: function(cat_id, csrf_token) {
		core.confirm('Вы действительно хотите удалить эту рубрику блога? Записи рубрики удалены не будут.', null, function(){
			$.post('/blogs/delcat'+cat_id+'.html', {csrf_token: csrf_token}, function(result){
				if(result.error == false){
					window.location.href = result.redirect;
				}
			}, 'json');
		});
    },
    deletePost: function(post_id, csrf_token) {
		core.confirm('Вы действительно хотите удалить этот пост блога?', null, function(){
			$.post('/blogs/delpost'+post_id+'.html', {csrf_token: csrf_token}, function(result){
				if(result.error == false){
					window.location.href = result.redirect;
				}
			}, 'json');
		});
    },
    publishPost: function(post_id) {
      $.post('/blogs/publishpost'+post_id+'.html', {}, function(data){
		if(data == 'ok'){
			$('#pub_link').hide();
			$('#pub_wait').hide();
			$('#pub_date').fadeIn();
		} else {
			core.alert('Пост не опубликован', 'Ошибка');
		}
      });
    }
  }
});