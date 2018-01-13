function formatState (state) {
	console.log(state);
//return state;
  if (!state.id) {
    return state.text;
  }
  var $state = jQuery(
  		"<span><span class=\"flag " + state.id.toLowerCase() + "\"></span> " + state.id + ' | ' + state.text + "</span>"
  );
  return $state;
};

function formatSelection( country )
{
	console.log(country);
	if (!country.id) {
		return country.text;
	}
	var $country = jQuery(
			"<span><span class=\"flag " + country.id.toLowerCase() + "\"></span> " + country.text + "</span>"
	);
	return $country;
}


jQuery(document).ready(function(){

	setTimeout(function() {
		var h = parseInt( jQuery(".column-2:first-child>div").css('height') );
		console.log('height ' + h + 'px');
		jQuery(".column-2:last-child>div").css('height', h + 'px' );
	}, 1000);

	jQuery('#wl_pages').select2({
	    tags: true
	});

	jQuery('#redirect_page').select2({
	    tags: true
	});

	jQuery('#whiteList').select2({
	    tags: true
	});
	/*
	jQuery("#redirect_page").select2({
		createSearchChoice:function(term, data) {
		  if (jQuery(data).filter(function() {
		    return this.text.localeCompare(term)===0;
		  }).length===0) {
		    return {id:term, text:term};
		  }
		},
		multiple: true
	});
*/
	jQuery("#country_code").select2({
	  templateResult: formatState,
	  templateSelection: formatSelection
	})

	jQuery(".select2-dropdown").addClass('f16');
	/*
	jQuery("#country_code").select2({
	    placeholder: "Select a country",
	    formatResult: function (country) {
	    	console.log(country);
	    	var flag = jQuery(this).attr('data-flag');
	        return $(
	          "<span><i class=\"flag flag-" + flag.toLowerCase() + "\"></i> " + country.text + "</span>"
	        );;
	    },
	    data: ipc_settings_vars
	});
	*/
});