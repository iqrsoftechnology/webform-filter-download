(function ($) {		
	$(document).ready(function() 
	{
   
    var $texta = $('#edit-form-fields');
    var $selects = $('#webform-settings-field');
    
    $selects.change(function () {
      var selected = $('#webform-settings-field :selected').val()+'|'+$('#webform-settings-field  :selected').text()+',';
      $("#edit-form-fields").append("\n" + selected);
    });
    
    
    // $selects.change(function () {
        // var opts = $selects.find('option:selected').map(function () {
            // return $.trim($(this).val()+'|'+$(this).text()+',');
        // }).get();
       // $texta.val(opts.join('\r\n'))

    // }).change();
    
  });
})(jQuery);