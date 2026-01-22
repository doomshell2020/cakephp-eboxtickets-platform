<div class="event_payment">
    <section id="my_ticket">
        <div class="event-list-container" id="Mycity">
            <div class="event_detales">

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-dark table_bg">
                            <tr>
                                <th style="width: 20%;" scope="col">Bar Code</th>
                                <th style="width: 16%;" scope="col">Username</th>
                                <th style="width: 6%;" scope="col">Email</th>
                                <th style="width: 15%;" scope="col">Mobile</th>
                                <th style="width: 6%;" scope="col">Type</th>
                                <th style="width: 15%;" scope="col">Print Name</th>
                                <th style="width: 5%;" scope="col">ScannedBy</th>
                                <th style="width: 18%;" scope="col">Purchased Date</th>
                            </tr>
                        </thead>
                        <span>

                        </span>
                        <tbody class="tbody_bg">
                            <?php if (!empty($ticket_data)) { ?>

                                <?php foreach ($ticket_data as $key => $value) { //pr($value); die;
                                    // if(!empty($value['scanner_id'])){
                                    $getScannername = $this->Comman->getScannerName($value['scanner_id']);
                                    // }
                                    // pr($getScannername);exit;
                                    // print_r($value['package_id']);
                                    // exit;

                                ?>
                                    <tr>
                                        <td>
                                            <div class="qr_code d-flex">

                                                <div class="code_scanner">
                                                    <?php if ($value['status'] == 0) { ?>
                                                        <img src="<?php echo SITE_URL . 'qrimages/temp/' . $value['qrcode']; ?>">
                                                    <?php } else { ?>
                                                        <img src="<?php echo SITE_URL . 'scannedQR-code.png'; ?>">
                                                    <?php }
                                                    if ($value['package_id']) { ?>

                                                        <div class="qr_pack">
                                                            <p class="p_h">Package</p>
                                                        </div>

                                                    <?php   }
                                                    ?>

                                                </div>


                                            </div>

                                            <button type="button" class="btn export_icon " data-bs-toggle="tooltip" data-bs-placement="right" title="Ticket Amount">
                                                <i class="bi bi-cash"></i>
                                                <?php echo $event_data['currency']['Currency']; ?> <?php echo $value['ticket']['amount']; ?>

                                            </button>

                                            <br>

                                            <button type="button" class="btn export_icon " data-bs-toggle="tooltip" data-bs-placement="right" title="Ticket Type">
                                                <i class="bi bi-ticket"></i>
                                                <?php echo $value['ticket']['eventdetail']['title']; ?>
                                            </button>


                                        </td>

                                        <td>
                                            <p class="t_data"><?php echo $value['user']['name'] . " " . $value['user']['lname']; ?></p>
                                        </td>

                                        <td>
                                            <p class="t_data"><?php echo $value['user']['email']; ?></p>
                                        </td>

                                        <td>
                                            <p class="t_data"><?php echo $value['user']['mobile']; ?></p>
                                        </td>

                                        <td>
                                            <p class="t_data"><?php echo (!empty($value['package_id'])) ? 'Package' : 'Ticket'; ?></p>
                                        </td>

                                        <td>
                                            <p class="t_data"><?php echo $value['name']; ?></p>
                                        </td>

                                        <td>
                                            <p class="t_data">
                                                <?php echo $getScannername['name'] . ' ' . $getScannername['lname']; ?>
                                            </p>
                                        </td>

                                        <td>
                                            <p class="t_data"><?php echo date('d M Y', strtotime($value['created'])); ?></p>
                                        </td>
                                    </tr>
                                <?php  } ?>

                            <?php } else { ?>
                                <tr>
                                    <td colspan="8" style="text-align:center"><b>Search Tickets....</b></td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                    <?php echo $this->element('admin/pagination'); ?>
                </div>

            </div>
        </div>
    </section>

</div>