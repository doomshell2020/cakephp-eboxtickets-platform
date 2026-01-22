<div class="row g-3 text-start">
  <?php echo $this->Form->create($findtickets, array(
    'url' => array('controller' => 'event', 'action' => 'edittickets'),
    'class' => '',
    'id' => '',
    'enctype' => 'multipart/form-data',
    'validate',
    'autocomplete' => 'off'

  )); ?>

  <!-- <form method="post" action="event/edittickets" enctype="multipart/form-data" class="needs-validation"> -->
  <div class="row g-3 text-start">
    <div class="col-md-12">
      <label for="inputName" class="form-label">Name</label>
      <?php
      echo $this->Form->input(
        'title',
        ['required' => 'required', 'type' => 'text', 'class' => 'form-control', 'placeholder' => 'Enter Ticket Name', 'label' => false]
      ); ?>
    </div>
    <div class="col-md-6">
      <label for="inputState" class="form-label">Type</label>
      <input type = "hidden" name = "tmp_type" value="<?php echo $findtickets['type']; ?>">
      <?php
      $type = ['open_sales' => 'Open Sales', 'committee_sales' => 'Committee Sales'];
      echo $this->Form->input(
        'type',
        ['empty' => 'Choose Type', 'options' => $type,'id'=>'select_type','default' => '', 'required' => 'required', 'class' => 'form-select', 'label' => false]
      ); ?>
    </div>
    <div class="col-md-6">
      <label for="inputname" class="form-label">Price</label>
      <input type="hidden" name="tmpprice" value="<?php echo $findtickets['price']; ?>">
      <?php
      echo $this->Form->input(
        'price',
        ['required' => 'required', 'placeholder' => 'Price', 'class' => 'form-control', 'label' => false, 'onkeypress' => 'return isPrice()']
      ); ?>
    </div>

    <div class="col-md-6" id="count_disable">
      <label for="inputname" class="form-label">Count</label>

      <?php
      echo $this->Form->input(
        'count',
        ['required' => 'required', 'placeholder' => 'Count','id'=>'select_count','class' => 'form-control', 'label' => false, 'onkeypress' => 'return isPrice()']
      ); ?>
    </div>

    <div class="col-md-6">
      <label for="inputState" class="form-label">Visibility</label>
      <?php
      $hidden = ['N' => 'Hidden', 'Y' => 'Visible'];
      echo $this->Form->input(
        'hidden',
        ['empty' => 'Choose One', 'options' => $hidden, 'required' => 'required', 'class' => 'form-select', 'default' => '', 'label' => false]
      ); ?>
    </div>

    <div class="modal-footer">
      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      <button type="submit" class="btn save">Update Ticket</button>
    </div>

  </div>
  </form>
  <!-- ================== -->

  <script>
    function isPrice(e) {
      var e = e || window.event;
      var k = e.which || e.keyCode;
      var s = String.fromCharCode(k);
      if (/^[\\\"\'\;\:\.\,\[\]\>\<\/\?\=\+\_\|~`!@#\$%^&*\(\)a-z\A-Z]$/i.test(s)) {
        alert("Special characters not acceptable");
        return false;
      }
    }

    // jQuery($ => {
    // let $checkBox = $('#type').on('change', e => {


    $(document).ready(function() {

      var checkcomitte =  $('#select_type').val();
      // alert(checkcomitte);
      if(checkcomitte=='committee_sales'){
        $('#select_count').prop('disabled', 'disabled');
          $('#count_disable').css('display', 'none');
      }

      $('#select_type').on('change', function() {
        alert(this.value);
        if (this.value == 'committee_sales') {
          $('#select_count').prop('disabled', 'disabled');
          $('#count_disable').css('display', 'none');
          
        } else {
          $('#count_disable').css('display', 'block');
          $('#select_count').prop('disabled', false);
          $('#select_count').attr('required', true);
        }

      });

    });
    // });
  </script>