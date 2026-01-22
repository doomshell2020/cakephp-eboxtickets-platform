<style type="text/css">
    #myUL {
        position: relative;
        z-index: 999;
    }

    #myUL ul {
        list-style-type: none;
        margin: 0;
        padding: 0;
        max-height: 100px;
        position: absolute;
        background-color: #fff;

    }

    #myUL li {
        font: 200 15px/1.5 Helvetica, Verdana, sans-serif;
        border-bottom: 1px solid #ccc;
        background-color: #fff;
    }

    #myUL li li:last-child {
        border: none;
    }

    #myUL li a {
        text-decoration: none;
        color: #000;
        display: block;
        width: 258px;


    }

    #orgUL {
        position: relative;
        z-index: 999;
    }

    #orgUL ul {
        list-style-type: none;
        margin: 0;
        padding: 0;
        max-height: 100px;
        position: absolute;
        background-color: #fff;

    }

    #orgUL li {
        font: 30px/ Helvetica, Verdana, sans-serif;
        border-bottom: 1px solid #ccc;
        background-color: #fff;
    }

    #orgUL li li:last-child {
        border: none;
    }

    #orgUL li a {
        text-decoration: none;
        color: #000;
        display: block;
        width: 258px;
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
                    <li><a href="<?php echo SITE_URL; ?>admin/dashboard ">Dashboard</a></li>
                    <li><a href="<?php echo ADMIN_URL; ?>paymentreport/index">Payment Report Manager</a></li>
                </ol>
            </div>
        </div>
    </div>
</div>

<!-- <?php //echo $this->Paginator->limitControl([10 => 10, 15 => 15,20=>20,25=>25,30=>30]);
        ?> -->

<div class="content mt-3">
    <div class="animated fadeIn">
        <div class="row">

            <?php echo $this->Flash->render(); ?>
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <strong class="card-title">Payment Report Manager</strong>
                    </div>

                    <div class="row" style="padding: 10px;">

                        <script>
                            $(document).ready(function() {

                                $("#Mysubscriptionsf").bind("submit", function(event) {
                                    $('.preloader').show();
                                    $.ajax({
                                        async: true,
                                        data: $("#Mysubscriptionsf").serialize(),
                                        dataType: "html",
                                        type: "GET",
                                        url: "<?php echo ADMIN_URL; ?>paymentreport/search",
                                        success: function(data) {
                                            $('.preloader').hide();
                                            $("#example23").html(data);
                                        },
                                    });
                                    return false;
                                });
                            });

                            $(document).on('click', '.pagination a', function(e) {
                                var target = $(this).attr('href');
                                var res = target.replace("/paymentreport/search", "/paymentreport");
                                window.location = res;
                                return false;
                            });

                            //]]>
                        </script>

                        <?php
                        $req_data = $_GET;
                        $customername = $req_data['customername'];
                        // $customermobile = $req_data['customermobile'];
                        $date_from = $req_data['date_from'];
                        $date_to = $req_data['date_to'];
                        $eventname = $req_data['eventname'];
                        $eventId = $req_data['eventid'];
                        // pr($eventname);exit;
                        ?>

                        <div class="col-sm-3">
                            <?php echo $this->Form->create('Mysubscription', array('type' => 'file', 'inputDefaults' => array('div' => false, 'label' => false), 'id' => 'Mysubscriptionsf', 'class' => 'form-horizontal')); ?>

                            <div class="form-group">
                                <label>Customer</label>
                                <?php
                                echo $this->Form->input('customername', array('class' => 'longinput form-control input-medium ', 'placeholder' => 'Customer', 'type' => 'text', 'label' => false, 'value' => $customername)); ?>
                            </div>
                        </div>


                        <div class="col-sm-3">
                            <div class="form-group">
                                <label>Event</label>
                                <input type="hidden" id="location_ids" name="eventid" value="<?php echo (!empty($eventId)) ? $eventId : null; ?>">
                                <?php echo $this->Form->input('eventname', array('class' => 'longinput form-control input-medium secrh-loc', 'placeholder' => 'Event Name', 'autocomplete' => 'off', 'type' => 'search', 'label' => false, 'value' => $eventname)); ?>
                                <div id="myUL">
                                    <ul></ul>
                                </div>
                            </div>
                        </div>

                        <!-- <div class="col-sm-3">

                            <div class="form-group">
                                <label>Mobile</label>
                                <?php
                                echo $this->Form->input('customermobile', array('class' => 'longinput form-control input-medium ', 'placeholder' => 'Mobile', 'type' => 'text', 'label' => false, 'value' => $customermobile)); ?>
                            </div>
                        </div> -->

                        <div class="col-sm-3">
                            <div class="form-group">
                                <label for="company" class=" form-control-label">Order From</label>
                                <?php echo $this->Form->input('date_from', array('class' => 'longinput form-control input-medium ', 'placeholder' => 'Date From', 'type' => 'text', 'label' => false, 'autocomplete' => 'off', 'id' => 'datetimepicker1', 'value' => $date_from)); ?>
                            </div>
                        </div>

                        <div class="col-sm-3">
                            <div class="form-group">
                                <label for="company" class=" form-control-label">Order To</label>
                                <?php echo $this->Form->input('date_to', array('class' => 'longinput form-control input-medium ', 'placeholder' => 'Date To', 'type' => 'text', 'label' => false, 'autocomplete' => 'off', 'id' => 'datetimepicker2', 'value' => $date_to)); ?>
                            </div>
                        </div>


                        <!-- <div class="col-sm-3">
                            <div class="form-group">
                                <label>Organiser</label>
                                <input type="hidden" id="organiser_search" name="organiser_id" value="<?php //echo
                                                                                                        // isset($organiser_id) ? $organiser_id : ''; 
                                                                                                        ?>">
                                <?php //echo $this->Form->input('organiser', array('class' => 'longinput form-control input-medium organiser_search', 'placeholder' => 'Organiser Name', 'autocomplete' => 'off', 'type' => 'search', 'label' => false, 'value' => $abc)); 
                                ?>
                                <div id="orgUL">
                                    <ul></ul>
                                </div>
                            </div>
                        </div> -->


                        <div class="col-sm-1">
                            <button type="submit" class="btn btn-success" id="Mysubscriptionsg">Search</button>

                        </div>

                        <div class="col-sm-1">
                            <a href="<?php echo SITE_URL; ?>admin/paymentreport"><strong class=" btn btn-warning card-title">Reset</strong></a>
                        </div>

                        <?php echo $this->Form->end(); ?>

                        <!-- <div class="col-sm-12">
                            <a href="<?php //echo SITE_URL; 
                                        ?>admin/paymentreport/export"><strong class=" btn btn-info card-title pull-right">Export CSV</strong></a>
                        </div> -->

                    </div>

                    <div class="card-body" id="example23">

                        <table id="bootstrap-data-table" class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>S.No</th>
                                    <th>Order Date</th>
                                    <th>Order Details</th>
                                    <th>Event</th>
                                    <th>Customer</th>
                                    <th>Mobile</th>
                                    <th>Country</th>
                                    <th>Qty.</th>
                                    <th style="width: 10%;">Customer Pay</th>
                                    <th>Admin Commision</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $total_amount = 0; // initialize total amount variable to 0
                                $total_commission_amount = 0; // initialize total commission amount variable to 0
                                $i = 1;
                                // pr($getAllOrderData);exit;
                                foreach ($getAllOrderData as $key => $indValue) { //pr($indValue);
                                    // pr($indValue);die;
                                    $total_amount += $indValue['total_amount']; // add amount to total amount
                                    $total_commission_amount += ($indValue['total_amount'] * $indValue['adminfee'] / 100); // add commission to total commission amount
                                ?>
                                    <tr>
                                        <td><?php echo $i++; ?></td>
                                        <td>
                                            <?php if (isset($indValue['created'])) {
                                                echo date('d M, Y h:i A', strtotime($indValue['created']));
                                            } ?>
                                        </td>

                                        <td>
                                            <strong>OrderIdentifier : </strong><?php echo $indValue['OrderIdentifier']; ?><br>
                                            <strong>OriginalTrxnIdentifier : </strong><?php echo $indValue['OriginalTrxnIdentifier']; ?><br>
                                            <strong>TransactionIdentifier : </strong><?php echo $indValue['TransactionIdentifier']; ?>
                                        </td>

                                        <td>
                                            <?php
                                            $eventName = $indValue['ticket'][0]['event']['name'] ?? 'N/A';
                                            $company   = $indValue['ticket'][0]['event']['company']['name'] ?? '';
                                            $country   = $indValue['ticket'][0]['event']['country']['CountryName'] ?? '';

                                            echo "<strong>" . htmlspecialchars($eventName) . "</strong>";

                                            if ($company) {
                                                echo "<br><small><strong>Company:</strong> " . htmlspecialchars($company) . "</small>";
                                            }

                                            if ($country) {
                                                echo "<br><small><strong>Country:</strong> " . htmlspecialchars($country) . "</small>";
                                            }
                                            ?>
                                        </td>


                                        <td><?php echo ucwords($indValue['user']['name']) . ' ' . ucwords($indValue['user']['lname']); ?></td>
                                        <td><?php echo $indValue['user']['mobile']; ?></td>
                                        <td><?php echo $indValue['user']['country']['CountryName']; ?></td>
                                        <td><?php echo count($indValue['ticket']); ?></td>
                                        <td>
                                            <?php echo $indValue['ticket'][0]['event']['currency']['Currency_symbol']; ?><?php echo number_format($indValue['total_amount'], 2) ?> <?php echo $indValue['ticket'][0]['event']['currency']['Currency']; ?>
                                        </td>
                                        <td>
                                            <a style="color: black;">
                                                <?php echo $indValue['ticket'][0]['event']['currency']['Currency_symbol']; ?><?php echo number_format($indValue['total_amount'] * $indValue['adminfee'] / 100, 2); ?> <?php echo $indValue['ticket'][0]['event']['currency']['Currency']; ?>
                                            </a>
                                        </td>
                                    </tr>
                                <?php

                                }
                                ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="7" style="text-align: right"><strong>Total Amount:</strong></td>
                                    <td>
                                        <strong>
                                            <?php echo "$" . number_format($total_amount, 2); ?></strong>
                                    </td>

                                    <td>
                                        <strong>
                                            <?php echo "$" . number_format($total_commission_amount, 2); ?></strong>
                                    </td>

                                </tr>
                            </tfoot>
                        </table>
                        <?php echo $this->element('admin/pagination'); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<script type="text/javascript">
    function cllbck(id, cid) {
        $('.secrh-loc').val(id);
        $('#location_ids').val(cid);
        $('#myUL').hide();
    }

    $(function() {
        $('.secrh-loc').bind('keyup', function() {
            var pos = $(this).val();
            $('#myUL').show();
            $('#location_ids').val('');
            var count = pos.length;

            if (count > 0) {
                $.ajax({
                    type: 'POST',
                    url: '<?php echo SITE_URL; ?>admin/ticket/loction',
                    data: {
                        'fetch': pos
                    },
                    success: function(data) {
                        $('#myUL ul').html(data);
                    },
                });
            } else {
                $('#myUL').hide();
            }
        });
    });

    // search organiser
    $('.organiser_search').bind('keyup', function() {
        var pos = $(this).val();

        $('#orgUL').show();
        $('#organiser_search').val('');
        var count = pos.length;

        if (count > 0) {
            $.ajax({
                type: 'POST',
                url: '<?php echo SITE_URL; ?>admin/event/organiser_search',
                data: {
                    'fetch': pos
                },
                success: function(data) {
                    // console.log(data);
                    $('#orgUL ul').html(data);
                },
            });
        } else {
            $('#orgUL').hide();
        }
    });

    function searchbck(id, cid) {
        $('.organiser_search').val(id);
        $('#organiser_search').val(cid);
        $('#orgUL').hide();
    }
</script>

<link href="https://code.jquery.com/ui/1.10.4/themes/ui-lightness/jquery-ui.css" rel="stylesheet">
<script src="https://code.jquery.com/jquery-1.10.2.js"></script>
<script src="https://code.jquery.com/ui/1.10.4/jquery-ui.js"></script>

<script type="text/javascript">
    $(function() {
        $("#datetimepicker1").datepicker({
            dateFormat: 'dd-mm-yy'
        });

        $("#datetimepicker2").datepicker({
            dateFormat: 'dd-mm-yy'
        });

    });
</script>