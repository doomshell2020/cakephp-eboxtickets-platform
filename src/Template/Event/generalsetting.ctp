<link href="https://cdn.rawgit.com/mdehoog/Semantic-UI/6e6d051d47b598ebab05857545f242caf2b4b48c/dist/semantic.min.css" rel="stylesheet" type="text/css" />
<script src="https://cdn.rawgit.com/mdehoog/Semantic-UI/6e6d051d47b598ebab05857545f242caf2b4b48c/dist/semantic.min.js"></script>
<link href="//maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" rel="stylesheet" id="bootstrap-css">
<script src="//maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"></script>
<!-- <script src="//cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script> -->


<section id="Dashboard_section">
    <div class="d-flex">
        <?php echo $this->element('organizerdashboard'); ?>

        <!-- <div class="col-sm-9"> -->
        <div class="dsa_contant">
            <?php echo $this->element('allevent'); ?>
            <div class="pro_section">
                <!--  -->
                <div class="table-responsive">
                    <div class="scroll_tab">
                        <ul id="progressbar">

                            <li class="active"><a href="<?php echo SITE_URL; ?>event/settings/<?php echo $id; ?>">Manage Event</a> </li>
                            <?php if ($findevent['is_free'] == 'Y') { ?>
                                <li class="active"><a href="<?php echo SITE_URL; ?>event/attendees/<?php echo $id; ?>">Manage Attendees</a></li>
                            <?php } else { ?>
                                <li class="active"><a href="<?php echo SITE_URL; ?>event/manage/<?php echo $id; ?>">Manage Tickets</a></li>
                                <li class="active"><a href="<?php echo $retVal = ($findevent['is_free'] == 'Y') ? '#' : SITE_URL . 'event/committee/' . $id; ?>">Manage Committee</a> </li>
                            <?php  } ?>
                            <li class="active"><a href="<?php echo SITE_URL; ?>event/generalsetting/<?php echo $id; ?>">Publish Event</a></li>
                        </ul>
                    </div>
                </div>
            </div>
            <?php
            $message = $this->Flash->render();
            if ($message) {
                // Render the success message with Swal.fire
                echo "<script>
                Swal.fire({
                    icon: 'success',
                    title: 'Event successfully published',
                    text: '" . $message . "',
                    showConfirmButton: false, // Remove the confirm button
                    showCloseButton: true, // Remove the close icon
                    footer: `<a type='button' href='" . SITE_URL . "event/" . $findevent['slug'] . "' class='btn btn-danger' target='_blank'>View</a>`
                });
                </script>";
            }
            ?>


            <h4>Publish Event</h4>
            <hr>
            <p>You can manage event status here.</p>
            <ul class="tabes d-flex">
                <li><a class="active alert" type="button" href="#">Activation Setting</a></li>
            </ul>
            <!-- <hr> -->

            <div class="contant_bg">
                <div class="event_settings">
                    <?php echo $this->Form->create($findevent, array(
                        'url' => array('controller' => 'event', 'action' => 'generalsetting'),
                        'class' => '',
                        'enctype' => 'multipart/form-data',
                        'validate',
                        'autocomplete' => 'off',
                        'id' => 'sevice_form1'

                    )); ?>
                    <h6>Activation Setting</h6>


                    <div class="activation_btn">
                        <?php if ($findevent['status'] == 'N' && $findevent['submit_count']) { ?>

                            <a class="activation_A" href="<?php echo SITE_URL; ?>event/activationevent/<?php echo $id; ?>/Y" title="Active Event">Activate</a>

                        <?php } elseif ($findevent['submit_count']) { ?>

                            <a class="activation_D" href="<?php echo SITE_URL; ?>event/activationevent/<?php echo $id; ?>/N" title="Inactive Event">Deactivate Event</a>

                        <?php } ?>

                        <a class="activation_V" href="<?php echo SITE_URL; ?>event/<?php echo $findevent['slug']; ?>" target="_blank">View Event </a>
                    </div>


                    <div class="next_prew_btn d-flex justify-content-between">
                        <?php if ($findevent['is_free'] == 'Y') { ?>
                            <a class="prew" href="<?php echo SITE_URL; ?>event/attendees/<?php echo $id; ?>">Previous</a>
                        <?php } else { ?>
                            <a class="prew" href="<?php echo SITE_URL; ?>event/committee/<?php echo $id; ?>">Previous</a>
                        <?php } ?>

                        <?php if (($findevent['is_free'] == 'N' && $admin_user['forPaidEvent'] == 'N' && $findevent['admineventstatus'] == 'Y') || ($findevent['is_free'] == 'Y' && $admin_user['forFreeEvent'] == 'N' && $findevent['admineventstatus'] == 'Y')) {

                            if (empty($findevent['submit_count'])) { ?>

                                <input type="hidden" name="is_send_email" value="Y">
                                <button type="submit" class="btn submit">Publish Event</button>

                            <?php } else { ?>

                                <input type="hidden" name="is_send_email" value="N">
                                <button type="submit" class="btn submit">Save</button>

                            <?php } ?>

                        <?php  } else { ?>

                            <input type="hidden" name="is_send_email" value="Y">
                            <button style="background-color: #ff9800;" type="submit" class="btn submit">Pending Approval</button>

                        <?php }  ?>

                        <?php
                        // $isFree = $findevent['is_free'] === 'Y';
                        // $isApproved = $findevent['admineventstatus'] === 'Y';
                        // $hasBeenSubmitted = !empty($findevent['submit_count']);
                        // $canPublishFreeEvent = $admin_user['forFreeEvent'] === 'N';
                        // $canPublishPaidEvent = $admin_user['forPaidEvent'] === 'N';

                        // $isSendEmail = $isApproved && (($isFree && $canPublishFreeEvent) || (!$isFree && $canPublishPaidEvent) || $hasBeenSubmitted);

                        // if ($isSendEmail) {
                        //     $buttonText = $hasBeenSubmitted ? 'Save' : 'Publish Event';
                        // } else {
                        //     $buttonText = 'Pending Approval';
                        // }
                        // 
                        ?>

                        <!-- // <input type="hidden" name="is_send_email" value="<?php //echo $isSendEmail ? 'Y' : 'N'; 
                                                                                    ?>">
                        // <button type="submit" class="btn submit" style="<?php //echo $isApproved ? '' : 'background-color: #ff9800;'; 
                                                                            ?>"><?php //echo $buttonText; 
                                                                                ?></button> -->

                    </div>

                    </form>

                </div>
            </div>

        </div>
        <!-- </div> -->
    </div>
</section>

<script>
    $(function() { //shorthand document.ready function
        $('#sevice_form1').on('submit', function(e) { //use on if jQuery 1.7+
            $('.preloader').show();
        });
    });

    var free = '<?php echo $findevent['is_free']; ?>';
    if (free == 'Y') {
        var li = document.getElementById('progressbar');
        let lis = document.getElementById('progressbar').getElementsByTagName('li');
        // document.getElementById('progressbar').style.background ="red";
        for (var i = 0; i < lis.length; i++) {
            lis[i].classList.add("changeprogressbar");
        }
    }

    // var today = new Date();
    // var todayfff = new Date('<?php //echo $findevent['date_to']; 
                                ?>');
    // $('#example1').calendar({
    //     type: 'datetime',
    //     minDate: new Date(today.getFullYear(), today.getMonth(), today.getDate()),
    //     endCalendar: $('#exampleE'),
    //     //     formatter: {
    //     //   date: 'YYYY-MM-DD'
    //     // }
    // });

    // $('#examplesecond').calendar({
    //     type: 'datetime',
    //     // formatter: {
    //     // date: 'd-M-Y H:i:s A'
    //     // },
    //     //minDate: new Date(today.getFullYear(), today.getMonth(), today.getDate()),
    //     maxDate: new Date(todayfff.getFullYear(), todayfff.getMonth(), todayfff.getDate()),
    //     //maxDate:new Date(today.max_year, today.max_month, today.max_date),
    //     startCalendar: $('#example1')
    // });
</script>