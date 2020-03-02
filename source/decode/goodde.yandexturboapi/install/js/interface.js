$(function(){
    $('.goodde-yandexturbo-list').each(function(){
        var holder = $(this);
        var reset = holder.next('.reset');
        var parents = $('.parent', holder);
        var allInputs = $('input', holder);

        parents.each(function(){
            var parent = $(this);
            var list = parent.siblings('ul');
            var input = parent.siblings('input[type=checkbox], input[type=radio]');
            var inputChilds = $('input', list);

            parent.click(function(event) {
                list.slideToggle();
            });
            if(input.is(':checked')){
                list.slideToggle();
            }
            input.change(function(event){
                if(input.is(':checked')){
                    inputChilds.attr('checked', 'checked');
                    list.slideDown();
                }else{
                    inputChilds.removeAttr('checked');
                    list.slideUp();
                }
            });
        });

        reset.click(function(event){
            allInputs.removeAttr('checked');
        });

        allInputs.each(function(index, el) {
            var ths = $(this);
            ths.change(function(event){
                if(ths.is(':checked')){
                    ths.parents('li', holder).find('>input').attr('checked', 'checked');
                }
            });
        });
    });
	
	formActions = function(){
		$('[data-clone-container]').each(function(){
			var holder = $(this);
			var template = $('[data-form-block-template]', holder);
			var block = $('[data-form-block]', holder);
			var target = $('[data-block-target]', holder);
			var init = $('[data-add-more]', holder);
			var counter = block.length;
			function resetBlocks(){
				block.each(function(index, el) {
					if($(this).data('js-set') == true){
						return;
					}
					$(this).data('js-set', true);

					var ths = $(this);
					var remove = $('[data-remove]', ths);
					var similarInit = $('[data-similar-init]', ths);
					var similarInput = $('[data-similar-input]', ths);

					similarInit.change(function(event){
						similarInput.hide().eq($('option:selected', similarInit).index()).show();
					}).trigger('change');

					remove.click(function(event){
						if(remove.data('remove') == 'static'){
							ths.addClass('disabled');
						}else{
							ths.remove();
						}
					});
				});
			}
			resetBlocks();

			init.click(function(event){
				event.preventDefault();
				var clone = template.clone().removeClass('template').insertBefore(target);
				
				$('select', clone).eq(0).attr('name', 'FEEDBACK[TYPE]['+counter+'][STICK]');
				$('select', clone).eq(1).attr('name', 'FEEDBACK[TYPE]['+counter+'][PROVIDER_KEY]');
				$('[data-similar-input]', clone).each(function(index, el) {
					$('input', $(this)).attr('name', 'FEEDBACK[TYPE]['+counter+'][PROVIDER_VALUE]['+index+']');
				});

				block = $('[data-form-block]', holder);
				counter++;
				resetBlocks();
			});
		});
	}
	formActions();
	
	$(document).on('click', '.option_field', function () {
		$(this).parent('label').find('.option_value').toggle();
	});

	$(document).on('click', '#feed_fields_table .controls .adm-btn-delete', function () {
		var inner = $(this).closest('.td-condition');
		var row = $(this).closest('.field-row');
		if($(inner).find('.field-row').length > 1){
			$(row).remove();
		}
	});
});

function execAjax(action, data) {

	if (typeof data === 'undefined') {
		data = getDefaultData();
	}
	data['exec_action'] = action;

	BX.showWait('wait1');
	$.ajax({
		type: 'POST',
		dataType: 'json',
		url: '/bitrix/admin/goodde_turbo_ajax.php',
		data: data,
		async: true,
		error: function (request, error) {
			if (error.length)
				alert('Error! ' + error);
		},
		success: function (data) {
			BX.closeWait('wait1');

			if (data.result == 'ok') {
				for (var key in data.items) {
					var item = data.items[key];
					$(item.id).html(item.html);
				}
			}
		}
	});
}

function getOfferFieldsSelect(select, rowId) {

	var value_row = $(select).parents('.field-row').find('.value_row');

	if (select.value === 'NONE') {
		value_row.hide();
	} else {
		value_row.show();

		var data = getDefaultData();
		data['rowId'] = rowId;
		data['type'] = $(select).val();
		data['exec_action'] = 'getOfferFieldsSelect';
		
		var type = data['type'];
		
		BX.showWait('wait1');
		$.ajax({
			type: 'POST',
			dataType: 'json',
			url: '/bitrix/admin/goodde_turbo_ajax.php',
			data: data,
			async: true,
			error: function (request, error) {
				if (error.length)
					alert('Error! ' + error);
			},
			success: function (data) {
				BX.closeWait('wait1');

				if (data.result == 'ok') {
					$(value_row).find('select').html(data.html);
					if($(value_row).find('[data-useIpropertyValue]').length){
						if(type == 'IPROPERTY'){
							$(value_row).find('[data-useIpropertyValue] input').removeAttr('disabled');
							$(value_row).find('[data-useIpropertyValue]').show();
						}
						else{
							$(value_row).find('[data-useIpropertyValue] input').attr('disabled', 'disabled');
							$(value_row).find('[data-useIpropertyValue]').hide();
						}
					}
				}
			}
		});
	}
}

function showCatalogCondTree(_this, key) {
	var fieldId = $(_this).attr('data-id');
	var rowId = 'row_' + key;

	var data = getDefaultData();
	data['fieldId'] = fieldId;
	data['rowId'] = rowId;
	data['key'] = key;

	execAjax('getCatalogCondTree', data);

	if ($(_this).prop('checked')) {
		$('#' + rowId + '_condition').removeClass('hide');
	} else {
		$('#' + rowId + '_condition').addClass('hide');
	}
}

function customFieldAdd(_this) {
	var customId = ($('#feed_fields_table > tr').length);

	var data = getDefaultData();
	data['isCustom'] = 1;
	data['customId'] = customId;
	data['exec_action'] = 'changeOfferType';

	BX.showWait('wait1');
	$.ajax({
		type: 'POST',
		dataType: 'json',
		url: '/bitrix/admin/goodde_turbo_ajax.php',
		data: data,
		async: true,
		error: function (request, error) {
			if (error.length)
				alert('Error ' + error);
		},
		success: function (data) {
			BX.closeWait('wait1');

			if (data.result == 'ok') {
				$('#feed_fields_table tr:last').after(data.html);
			}
			else {
				alert('Error create custom field');
			}
		}
	});

	return false;
}

function customFieldRemove(_this) {
	$(_this).parents('.offer-type-field').fadeOut(200, function () {
		$(this).remove()
	});
}