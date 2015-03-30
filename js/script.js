$(document).ready(function(){
	var status = $('#status'),
		btn = $('#btn'),
		active = false;
	
	var form = $('#form');
	
	form.submit(function(){
		if(!active){
			var url = $('#url').val();
			
			if(isValidURL(url)){
				$.ajax({
					url: '/index.php',
					data: form.serialize(),
					type: "get",
					dataType: "json",
					beforeSend: function(){
						active = true;
						status.text('Идет обработка...');
					},
					success: function(resp){
						active = false;
						
						if(resp != null){
							if(resp.status == 'true'){
								status.html('<p>Сканирование завершено.</p><p>Скачать Sitemap: <a href="/files/'+resp.file+'">'+resp.file+'</a></p>');
								return;
							}
						}
						
						status.text('Генерация не удалась');
					},
					error: function(){
						active = false;
						status.text('Генерация не удалась');
					}
				});
			}else{
				status.html('<p>Введен невалидный адрес</p>');
			}
		}
		
		return false;
	});
});

function isValidURL(url){
	return /^(([\w]+:)?\/\/)?(([\d\w]|%[a-fA-f\d]{2,2})+(:([\d\w]|%[a-fA-f\d]{2,2})+)?@)?([\d\w][-\d\w]{0,253}[\d\w]\.)+[\w]{2,4}(:[\d]+)?(\/([-+_~.\d\w]|%[a-fA-f\d]{2,2})*)*(\?(&amp;?([-+_~.\d\w]|%[a-fA-f\d]{2,2})=?)*)?(#([-+_~.\d\w]|%[a-fA-f\d]{2,2})*)?$/.test(url);
}
