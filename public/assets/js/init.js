// Удостовериться в готовности документа, прежде чем выполнять сценарий
jQuery(function($){


// Файл, которому следует отправить запрос AJAX
var processFile = "assets/inc/ajax.inc.php";
	
// Проверить, дал ли пользователь ответы на все вопросы на странице
// прежде чем отправлять форму
$(".nextCluster").live("click", function(event){
	//получить общее количество радио-кнопок на странице
	var numRadio = $("input:radio").size(),
		numCheckedRadio = $("input:radio:checked").size(),
		// количество вариантов ответов на вопрос
		NUM_OF_OPTIONS = 7;
	
	// Если количество данных ответов меньше, чем вопросов предупредить
	// об этом участника дегустации и отменить отправку формы
	if (numRadio / NUM_OF_OPTIONS > numCheckedRadio )
	{
		// Отменить отправку формы
		event.preventDefault();
		// Отобразить модальное окно с предупреждением
		var data = $("<p>Вы должны дать ответы на все вопросы.</p>");	
		
		data
			.dialog({modal:true, 
								buttons:{	
									OK:function(){
										// при нажатии на кнопку закрываем модальное окно
										$(this).dialog("destroy");
										}
									} 
		});
	}
});

// Отобразить форму для создания вопроса в модальном окне
$(".quest").live("click", function(event){
	// Предотвратить переход по ссылке
	event.preventDefault();
	// Загрузить атрибут action для обрабатывающего файла
	var action = "edit_question";
	//получить данные из формы
	var formData = $(this).parents("form").serialize();
	// если форма пустая - значит пользователь хочет добавить новый вопрос
	// иначе редактировать существующий
	if (formData == "")
	{
		formData = "action="+action;
	}
	// Загрузить форму для редактирования вопросов и отобразить ее
	$.ajax({
		type: "POST",
		url: processFile,
		data: formData,
		success: function(data){
			// создать форму в дереве DOM
			var form = $(data);

			// отобразить форму в модальном окне
			form
				.dialog({modal:true, width:500});
		},
		error: function(msg){
			alert(msg);
		}
	});
});

// Отобразить форму для добавления образца продукции в модальном окне
$(".add_product").live("click", function(event){
	// Предотвратить переход по ссылке
	event.preventDefault();
	
	var action = "choise_product";
	
	var newProductButton = $("<a href=\"editProduct.php\" class=\"admin new_product\">Создать новый образец продукции</a>");
	
	// Загрузить форму для выбора образцов продукции
	$.ajax({
		type: "POST",
		url: processFile,
		data: "action="+action,
		success: function(data){
			// создать форму в дереве DOM
			var form = $(data);
			// добавить кнопку для создания нового образца продукции
			form
				.addClass("choise_product")
				.append(newProductButton);
			
			// отобразить форму в модальном окне
			form
				.dialog({modal:true, width:500});
		},
		error: function(msg){
			alert(msg);
		}
	});
});

// Отобразить форму для создания образца продукции в модальном окне
$(".new_product").live("click", function(event){
	// Предотвратить переход по ссылке
	event.preventDefault();
	
	var action = "new_product";
	
	// Загрузить форму для выбора образцов продукции
	$.ajax({
		type: "POST",
		url: processFile,
		data: "action="+action,
		success: function(data){
			// закрыть форму выбора продукта
			$(".choise_product").dialog("close");
			// создать форму в дереве DOM
			var form = $(data);
			// отобразить форму в модальном окне
			form
				.addClass("edit_product")
				.dialog({modal:true, width:500});
		},
		error: function(msg){
			alert(msg);
		}
	});
});

// После создания образца продукции загрузить модальное окно с обновленным списком продуктов
$(".add_new_product").live("click", function(event){
	// Предотвратить переход по ссылке
	event.preventDefault();
	
	//получить данные из формы
	var formData = $(this).parents("form").serialize();

	$.ajax({
		type: "POST",
		url: processFile,
		data: formData,
		success: function(data){
			// закрыть окно создания образца продукта
			$(".edit_product").dialog("close");
			// открыть окно выбора 
			$(".add_product").click();
		},
		error: function(msg){
			alert(msg);
		}
	});
});

// В случае отмены вернуться на предыдущее окно
$("add_new_product_cancel").live("click", function(event){
	// Предотвратить переход по ссылке
	event.preventDefault();
	// закрыть окно редактирования продукта
	$(".edit_product").dialog("close");
	// открыть окно выбора продукта
	$(".add_product").click();
});

// Проверить, порядок следования образцов в методе треугольника
// Должна быть комбинация из 2-х символов A и одного символа B
$(".check_order").live("click", function(event){
	// получить выбранный порядок образцов
	var pos1 = $("#pos1 :selected").val(),
		pos2 = $("#pos2 :selected").val(),
		pos3 = $("#pos3 :selected").val();
	
	var order = [pos1, pos2, pos3];
	
	// подсчитать количество позиций образца закодированного символом A
	var nA = 0;
	for (var i = 0; i < 3; i++)
	{
		if ( order[i] == 'A' )
		{
			nA++;
		}
	}
	
	// если комбинация не соответсвует условию, не отправлять форму на сервер
	if (nA != 2)
	{
		// Отменить отправку формы
		event.preventDefault();
		// Отобразить модальное окно с предупреждением
		var data = $("<p>Должна быть комбинация из 2-х образцов A и одного образца B</p>");	
		
		data
			.dialog({modal:true, 
								buttons:{	
									OK:function(){
										// при нажатии на кнопку закрываем модальное окно
										$(this).dialog("destroy");
										}
									} 
		});
	}
});


});