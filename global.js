jQuery(document).ready(function($) {

  var dpdButton = $('#dpd-export-button');

  if(dpdButton.length) {
    var ordersTable = $('table.wp-list-table.posts');
    ordersTable.find('.check-column input').change(function() {
        var selected = new Array();
        setTimeout(function(){
          ordersTable.find('.check-column input:checked').each(function(){
            if($(this).val() != 'on') {
              selected.push($(this).val());
            }
          });

          if(selected.length) {
            dpdButton.find('small').html(' - #'+selected.join(', #'));
            dpdButton.attr('href',dpdButton.data('baseurl')+selected.join('|'));
          } else {
            dpdButton.find('small').html('');
            dpdButton.attr('href',dpdButton.data('baseurl'));
          }

        }, 100);
    });
  }


});
