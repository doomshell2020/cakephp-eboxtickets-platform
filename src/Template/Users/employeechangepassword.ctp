<div class="modal-header">
  <h5 class="modal-title" id="exampleModalLabel">Change Password</h5>
  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>
<form id="empform" action="<?php echo SITE_URL; ?>users/employeechangepassword" method="post">
  <div class="modal-body">
    <input type="hidden" name="emp_id" id="inputEmail1" value="<?php echo $emp_id ?>">
    <div class="row g-3 text-start">

      <div class="col-md-6">
        <label for="inputName" class="form-label">Password</label>
        <div class="input text required">
          <input type="password" name="password"  class="form-control" id="inputEmail1" placeholder="password" required>

        </div>
      </div>

      <div class="col-md-6">
        <label for="inputName" class="form-label">Confirm Password</label>
        <div class="input text required"> <input type="password" name="confirmpassword"  class="form-control" id="inputEmail2" placeholder="Confirm Password" required></div>
      </div>

 
    </div>
    <!-- ================== -->
  </div>
  <div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
    <button type="submit" class="btn save">Submit</button>
  </div>
</form>

<!-- <script>
  $(document).ready(function() { //alert();
    $("#empform").submit(function(e) {
      e.preventDefault();
      var name = $('#inputEmail2').val();
      var email = $('#inputEmail3').val();
      var mobile = $('#inputEmail4').val();
      var id_data = $('#inputEmail1').val();

      $.ajax({
        type: 'POST',
        url: '<?php //echo SITE_URL; ?>/users/checkempdataedit',
        data: {
          email: email,
          mobile: mobile,
          id_data: id_data
        },
        success: function(data) { //alert(data);

          obj = JSON.parse(data);
          // console.log(obj.email);

          if (obj.email == 1) {
            $("#empemval").css("display", "block");
            // $("#empemval").css("display", "none");
            return false;
          } else if (obj.mobile == 1) {
            // $("#empemval").css("display", "none");
            $("#empmoval").css("display", "block");
            return false;
          } else {
            document.getElementById("empform").submit();
          }

        }
      });
    });
  });
</script> -->