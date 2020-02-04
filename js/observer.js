$("#observer-table").on('click','.assign',function(){

  var currentRow=$(this).closest("tr");
  var fname = currentRow.data('fullname');
  var phone = currentRow.data('phone');
  var email = currentRow.data('email');
  var position = currentRow.data('position');
  
  $("#name").val(fname);
  $("#phone").val(phone);
  $("#email").val(email);
  $("#position").val(position);
});