// Удостовериться в готовности документа, прежде чем выполнять сценарий
jQuery(function($){


// Файл, которому следует отправить запрос AJAX
var processFile = "assets/inc/ajax.inc.php",

// Функции для манипулирования модальным окном
fx = {

	// Возвращает модальное окно, если оно существует;
	// в противном случае создает новое модальное окно
	"initModal" : function() {
			// Если подходящие элементы отсутствуют, свойство
			// length возвратить значение 0
			if ( $(".modal-window").length==0 )
			{	
				// Создать элемент div, добать класс и
				// присоединить его к дескриптору body
				return $("<div>")
						.addClass("modal-window")
						.prependTo("body");
			}
			else
			{
				// Возвратить модальное окно, если оно уже существует
				return $(".modal-window");
			}
		},

	// Добавляет окно в разметку и обеспечивает его плавное появление
	"boxin" : function(data, modal) {
			// Создать оверлей для сайта, добавить класс к обработчикам
			// события щелчка и присоединить их к телу документа
			$("<div>")
				.hide()
				.addClass("modal-overlay")
				.click(function(event){
						// Удалить событие
						fx.boxout(event);
					})
				.appendTo("body");

			// Загрузить данные в модальное окно
			// и присоединить его к телу документа
			modal
				.hide()
				.append(data)
				.appendTo("body");
				
			// высота окна браузера 
			var windowHeight = document.documentElement.clientHeight; 
			
			/* modal
				.css({ 
						"top": windowHeight - 140
					});  */
			
			// Обеспечить плавное появление модального окна и оверлея
			$(".modal-window,.modal-overlay")
				.fadeIn("slow");
		},

	// Обеспечивает плавное исчезновение окна и его удаление из DOM
	"boxout" : function(event) {
			// Если событе было запущено элементом, который
			// вызвал эту функцию, предотвратить выполнение
			// действия, заданного по умолчанию
			if ( event!=undefined )
			{
				event.preventDefault();
			}

			// Удалить класс "active" из всех ссылок
			$("a").removeClass("active");

			// Обеспечить плавное исчесзновение модального окна и оверлея,
			// а затем полностью удалить их из DOM
			$(".modal-window,.modal-overlay")
				.fadeOut("slow", function() {
						$(this).remove();
					}
				);
		},

	// Добавляет новый вопрос в разметку после сохранения
	"addQuestion" : function(data, formData){
			// Преобразовать строку запроса в объект
			var entry = fx.deserialize(formData);
			
			console.log("data = ");
			
			var post = "action=question_view&question_id="+data;
			console.log(post);
			
			// Добавить новый вопрос на страницу
			// Передать запрос на формирование представления вопроса на сервер
			$.ajax({
				type: "POST",
				url: processFile,
				data: post,
				success: function(form){
						
						$(form)
							.hide()
							.insertAfter($("div.question"))
							.delay(1000)
							.fadeIn("slow");
					},
				error: function(msg) {
						alert(msg);
					}
			});
			
			/* $("<a>")
					.hide()
					.attr("href", "view.php?event_id="+data)
					.text(entry.event_title)
					.insertAfter($("strong:contains("+day+")"))
					.delay(1000)
					.fadeIn("slow"); */
		},

	// Removes an event from the markup after deletion
	"removeevent" : function()
	{
			// Removes any event with the class "active"
			$(".active")
				.fadeOut("slow", function(){
						$(this).remove();
					});
		},

	// Десериализует строку запроса и возвращает объект
	"deserialize" : function(str){
			// Разбить каждую пару имя-значение на две части
			var data = str.split("&"),

			// Объявить переменные для использования в цикле
			pairs=[], entry={}, key, val;

			// Выполнить цикл по всем парам имя значение
			for ( x in data )
			{
				// Представим каждую пару в виде массива
				pairs = data[x].split("=");

				// Первый элемент - это имя
				key = pairs[0];

				// Второй элемент - это значение
				val = pairs[1];

				// Обратить URL-кодирование и сохранить каждое значение 
				// в виде свойства объекта
				entry[key] = fx.urldecode(val);
			}
			return entry;
		},

	// Декодирует значение строки запроса
	"urldecode" : function(str) {
			// Преобразовать знаки + в пробелы
			var converted = str.replace(/\+/g, ' ');

			// Выполнить обратное преобразование остальных 
			// закодированных объектов
			return decodeURIComponent(converted);
		}
}

// Set a default font-size value for dateZoom
//$.fn.dateZoom.defaults.fontsize = "13px";

// Pulls up events in a modal window and attaches a zoom effect
$("li a")
    //.dateZoom()
    .live("click", function(event){

            // Stops the link from loading view.php
            event.preventDefault();

            // Adds an "active" class to the link
            $(this).addClass("active");

            // Gets the query string from the link href
            var data = $(this)
                            .attr("href")
                            .replace(/.+?\?(.*)$/, "$1"),

            // Checks if the modal window exists and
            // selects it, or creates a new one
                modal = fx.checkmodal();

            // Creates a button to close the window
            $("<a>")
                .attr("href", "#")
                .addClass("modal-close-btn")
                .html("&times;")
                .click(function(event){
                            // Removes event
                            fx.boxout(event);
                        })
                .appendTo(modal);

            // Loads the event data from the DB
            $.ajax({
                    type: "POST",
                    url: processFile,
                    data: "action=event_view&"+data,
                    success: function(data){
                            // Displays event data
                            fx.boxin(data, modal);
                        },
                    error: function(msg) {
                            alert(msg);
                        }
                });

        });

// Displays the edit form as a modal window
/* $(".admin-options form,.admin").live("click", function(event){

        // Prevents the form from submitting
        event.preventDefault();

        // Sets the action for the form submission
        var action = $(event.target).attr("name") || "edit_event",

        // Saves the value of the event_id input
            id = $(event.target)
                    .siblings("input[name=event_id]")
                        .val();

        // Creates an additional param for the ID if set
        id = ( id!=undefined ) ? "&event_id="+id : "";

        // Loads the editing form and displays it
        $.ajax({
                type: "POST",
                url: processFile,
                data: "action="+action+id,
                success: function(data){
                        // Hides the form
                        var form = $(data).hide(),

                        // Make sure the modal window exists
                            modal = fx.checkmodal()
                                .children(":not(.modal-close-btn)")
                                    .remove()
                                    .end();

                        // Call the boxin function to create
                        // the modal overlay and fade it in
                        fx.boxin(null, modal);

                        // Load the form into the window,
                        // fades in the content, and adds
                        // a class to the form
                        form
                            .appendTo(modal)
                            .addClass("edit-form")
                            .fadeIn("slow");

                },
                error: function(msg){
                    alert(msg);
                }
            });
    });
 */
// Edits events without reloading
/* $(".edit-form input[type=submit]").live("click", function(event){

        // Prevents the default form action from executing
        event.preventDefault();

        // Serializes the form data for use with $.ajax()
        var formData = $(this).parents("form").serialize(),

        // Stores the value of the submit button
            submitVal = $(this).val(),

        // Determines if the event should be removed
            remove = false,

        // Saves the start date input string
            start = $(this).siblings("[name=event_start]").val(),

        // Saves the end date input string
            end = $(this).siblings("[name=event_end]").val();

        // If this is the deletion form, appends an action
        if ( $(this).attr("name")=="confirm_delete" )
        {
            // Adds necessary info to the query string
            formData += "&action=confirm_delete"
                + "&confirm_delete="+submitVal;

            // If the event is really being deleted, sets
            // a flag to remove it from the markup
            if ( submitVal=="Yes, Delete It" )
            {
                remove = true;
            }
        }

        // If creating/editing an event, checks for valid dates
        if ( $(this).siblings("[name=action]").val()=="event_edit" )
        {
            if ( !$.validDate(start) || !$.validDate(end) )
            {
                alert("Valid dates only! (YYYY-MM-DD HH:MM:SS)");
                return false;
            }
        }

        // Sends the data to the processing file
        $.ajax({
                type: "POST",
                url: processFile,
                data: formData,
                success: function(data) {
                    // If this is a deleted event, removes
                    // it from the markup
                    if ( remove===true )
                    {
						fx.removeevent();
                    }

                    // Fades out the modal window
                    fx.boxout();

                    // If this is a new event, adds it to
                    // the calendar
                    if ( $("[name=event_id]").val().length==0
                        && remove===false )
                    {
                        fx.addevent(data, formData);
                    }
                },
                error: function(msg) {
                    alert(msg);
                }
            });

    });

 */// Make the cancel button on editing forms behave like the
// close button and fade out modal windows and overlays
$(".edit-form a:contains(cancel)").live("click", function(event){
        fx.boxout(event);
    });
	
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
		var form = "<label>Вы должны дать ответы на все вопросы.</label>",
		
		// Убедиться в существовании модального окна
		modal = fx.initModal();
		
		// Вызвать функцию boxin для создания модального окна
		// и оверления и обеспечить его плавного появление
		fx.boxin(null, modal);
		
		// Создать кнопку для закрытия окна
		$("<a>")
			.attr("href", "#")
			.addClass("modal-close-btn")
			.html("&times;")
			.click(function(event){
						// Удалить модальное окно
						fx.boxout(event);
					})
			.appendTo(modal);
			
		// Загрузить предупреждение в окно, обеспечить плавное
		// появление содержимого
		$("<label>Вы должны дать ответы на все вопросы.</label>")
			.appendTo(modal)
			.fadeIn("slow");
	}
});

// Отобразить форму для редактирования вопроса в модальном окне
$(".queston").live("click", function(event){
	// Предотвратить переход по ссылке
	event.preventDefault();
	
	// Загрузить атрибут action для обрабатывающего файла
	var action = "edit_question";
	
	// Загрузить форму для редактирования вопросов и отобразить ее
	$.ajax({
		type: "POST",
		url: processFile,
		data: "action="+action,
		success: function(data){
			// Скрыть форму 
			var form = $(data).hide(),
			
			// убедиться в существовании модального окна
			modal = fx.initModal();
			
			// Вызвать функцию boxin для создания модального окна и
			// оверлея и обеспечить их плавное появление
			fx.boxin(null, modal);
			
			// Загрузить форму в окно, обеспечить плавное 
			// появление содержимого и добавить класс в форму
			form
				.appendTo(modal)
				.addClass("edit-form")
				.fadeIn("slow");
		},
		error: function(msg){
			alert(msg);
		}
	});
});

// Наделить кнопку "Отмена" на формах редактирования 
// функциями кнопки "Закрыть" для плавного закрытия и исчезовения
// модального окна и оверлея
$(".edit-form a:contains(Отмена)").live("click", function(event){
	fx.boxout(event);
});

// Редактировать вопрос без перезагрузки страницы
$(".edit-form input[type=submit]").live("click", function(event){
	// Предотвратить выполнение действия по умолчанию для формы
	event.preventDefault();
	
	// Сериализовать данные формы для использования с функцией $.ajax()
	var formData = $(this).parents("form").serialize();
	
	// Отправить данные обрабатывающему файлу
	$.ajax({
		type: "POST",
		url: processFile,
		data: formData,
		success: function(id){
			
			// Обеспечить плавное исчезновение модального окна
			fx.boxout();
			console.log("после отправки формы");
			console.log(id);
			// Добавить вопрос в опросный лист
			fx.addQuestion(data, formData);
		},
		error: function(msg){
			alert(msg);
		}
	});
});


});