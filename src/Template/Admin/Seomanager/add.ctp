<style type="text/css">
  .are {
    margin-top: -6px;
  }

  .bfh-timepicker-popover table {
    width: 280px;
    margin: 0
  }

  select.form-control:not([size]):not([multiple]) {
    height: calc(1.99rem + 2px) !important;
  }
</style>
<div class="breadcrumbs">
  <div class="col-sm-4">
    <div class="page-header float-left">
    </div>
  </div>
  <div class="col-sm-8">
    <div class="page-header float-right">
      <div class="page-title">
        <ol class="breadcrumb text-right">
          <li><a href="<?php echo ADMIN_URL; ?>dashboard ">Dashboard</a></li>
          <li><a href="<?php echo ADMIN_URL; ?>eventorganiser/index ">SEO Manager</a></li>
        </ol>
      </div>
    </div>
  </div>
</div>
<?php echo $this->Flash->render(); ?>
<?php echo $this->Form->create($addseo, array(
  'class' => 'form-horizontal',
  'controller' => 'eventorganiser',
  'action' => 'add',
  'enctype' => 'multipart/form-data',
  'validate'
)); ?>

<div class="col-lg-12">
  <div class="card">
    <div class="card-header"><strong> <?php if (isset($event['id'])) {
                                        echo '<small> Edit Event</small>';
                                      } else {
                                        echo 'Add Seo';
                                      } ?></strong></div>
    <div class="card-body card-block">
      <div class="col-sm-6">
        <div class="form-group">
          <label>Page Name</label>
          <?php
          echo $this->Form->input('page', array('class' => 'form-control longinput  input-medium', 'type' => 'text', 'required', 'placeholder' => 'Page Name', 'label' => false, 'id' => 'sta', 'autocomplete' => 'off')); ?>
        </div>
      </div>

      <div class="col-sm-6">
        <div class="form-group">
          <label>Page Location</label>
          <?php
          echo $this->Form->input('location', array('class' => 'longinput form-control input-medium', 'placeholder' => 'Page Location', 'type' => 'text', 'label' => false, 'id' => 'emailuser', 'data-val' => '0', 'required', 'autocomplete' => 'off')); ?>
        </div>
      </div>
      <div class="col-sm-6">
        <div class="form-group">
          <label>Title</label>
          <?php
          echo $this->Form->input('title', array('class' => 'longinput form-control input-medium', 'placeholder' => 'Title', 'type' => 'text', 'label' => false, 'required', 'id' => 'addnum0', 'data-val' => '0', 'autocomplete' => 'off')); ?>
        </div>
      </div>
      <div class="col-sm-6">
        <div class="form-group">
          <label>Keywords</label>
          <?php
          echo $this->Form->textarea('keyword', ['label' => 'Keywords','rows' => '5','cols' => '30','placeholder' => 'Keywords','class' => 'form-control',
        ]);?>
        </div>
      </div>
      <div class="col-sm-6">
        <div class="form-group">
          <label>Description</label>
          <?php
          echo $this->Form->textarea('description', ['label' => 'Description','rows' => '5', 'cols' => '30','placeholder' => 'Description','class' => 'form-control', 
        ]);?>
        </div>
      </div>



      <div class="col-sm-12">
        <div class="form-group">
          <div class="col-sm-1">
            <a href="<?php echo ADMIN_URL ?>seomanager/index" class="btn btn-primary ">Back</a>
          </div>
          <div class="col-sm-1">
            <?php if (isset($event['id'])) {
              echo $this->Form->submit('Update', array(
                'title' => 'Update', 'div' => false,
                'class' => array('btn btn-primary btn-sm')
              ));
            } else {  ?>
              <button type="submit" class="btn btn-success">Submit</button>
            <?php  } ?>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>
<?php echo $this->Form->end(); ?>

