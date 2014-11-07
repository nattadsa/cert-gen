$(function() {
	
	var $formato = $('#formato'),
		$ancho = $('#ancho'),
		$alto = $('#alto'),
		$textAlign = $('#textAlign')
		$popoverPos = $("#popoverPos"),
		textAlign,
		imgTextAlign = $('<img>').addClass('textAlign');


	var changeFormato = function(){
		$ancho.val( $formato.find('option:selected').data('ancho') );
		$alto.val( $formato.find('option:selected').data('alto') );
	};

	var changeMedidas = function(){
		var ancho = $ancho.val() || 0,
			alto = $alto.val() || 0;

		$formato.find('option').each(function(i){
			if( $(this).data('ancho') == ancho && $(this).data('alto') == alto){
				$formato.val($(this).prop('value'));
				return;
			}
		});
		$formato.val('');
		return;
	};

	var changeAlign = function(){
    	$(this).addClass("active").siblings().removeClass("active");
    	textAlign = $(this).data('value');
    	$textAlign.val( textAlign );
    };

	$formato.on('change',changeFormato).trigger('change');
	$ancho.on('blur',changeMedidas);
	$alto.on('blur',changeMedidas);


	$(".btn-group > .btn").on('click',changeAlign).first().trigger('click');

	$popoverPos.popover({
		content		: imgTextAlign,
		html		: true,
		placement	: 'bottom',
		trigger		: 'hover'
	}).on('show.bs.popover', function () {
		console.log(textAlign);
		imgTextAlign.prop({'src':'img/help_' + textAlign + '.jpg'});
	});

});