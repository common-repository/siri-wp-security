jQuery(document).ready(function($) {
	
	$('input,textarea').change(function(e){
		var page_base=$('#page_base');
		var is_page_base =(page_base.length && page_base.val().length && page_base.val()!=' ' && page_base.val()!='/') ? true : false;
		var author_without_base=$('#author_without_base');
		var is_author_without_base= (author_without_base.is(':checked')) ? true : false;
		var disable_submit=false;

		//fix a little problem caused by order of conditions check
		var page_base_error=false;

		if ($('#page_enable').val()==1 && !is_page_base && is_author_without_base){
			alert('If you enable author without base you should enter something as \'Page Base\'!' );
			page_base.css('border-color','red');
			page_base_error=true;
			disable_submit=true;
		}else if (!page_base_error){
			page_base.css('border-color','#DFDFDF');
		}

		if (page_base.length && $('#paginate_enable').length && $('#page_enable').val()==1 && $('#paginate_enable').val()==1 && page_base.val() && page_base.val().replace('/','').replace('/','') == $('#paginate_base').val().replace('/','').replace('/','') ){
			alert('\'Page Base\' and \'Paginate Base\' should be different!' );
			$('#paginate_base').css('border-color','red');
			page_base.css('border-color','red');
			page_base_error=true;
			disable_submit=true;
		}else{
			$('#paginate_base').css('border-color','#DFDFDF');
			if (!page_base_error)
				page_base.css('border-color','#DFDFDF');
		}

		if ($('#post_enable').val()==1 && $('#post_base').length && ($('#post_base').val().replace('/','').replace('/','')=='%postname%' || $('#post_base').val().replace('/','').replace('/','')=='%post_id%') && is_author_without_base){
			alert('If you enable author without base you can not use \'%postname%\' or \'%post_id%\' as post base.\nInstead combine them or add something before or after.  e.g. story/%postname%' );
			$('#post_base').css('border-color','red');
			disable_submit=true;
		}else{
			$('#post_base').css('border-color','#DFDFDF');	
		}


		if ($('#paginate_query').length && $('#paginate_enable').val()==1 && $('#paginate_query').val()=='page' ){
			alert('\'Page Query\' should not be \'page\'!' );
			$('#paginate_query').css('border-color','red');
			disable_submit=true;
		}else{
			$('#paginate_query').css('border-color','#DFDFDF');	
		}

		if ($('#page_enable').val()<1 && $('#custom_404_1').is(':checked')){
			alert('You can\'t disable page URL and use custom 404 in the same time!')
			disable_submit=true;
			$('#page_enable').css('border-color','red');
		}else{
			$('#page_enable').css('border-color','#DFDFDF');
		}

		if (disable_submit){
			$('#submit').attr('disabled','disabled');
		}else{
			$('#submit').removeAttr('disabled');
			//There is no error fix all!
		}

		
	});

	$('#submit').click(function(){
		if ($('#import_field').length && $('#import_field').val().length>5)
			alert("Your login address may change after importing new settings.\n Check out 'General Settings' tab for new address.");	
	});

	$('#submit[disabled="disabled"]').click(function(){
		alert('Please fix errors before save!');
	});

	
});
				