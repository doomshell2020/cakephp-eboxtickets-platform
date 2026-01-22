<link href="//netdna.bootstrapcdn.com/font-awesome/4.0.3/css/font-awesome.min.css" rel="stylesheet" type="text/css" />
<link href="https://cdn.rawgit.com/mdehoog/Semantic-UI/6e6d051d47b598ebab05857545f242caf2b4b48c/dist/semantic.min.css" rel="stylesheet" type="text/css" />
<script src="https://code.jquery.com/jquery-2.1.4.js"></script>

<script src="https://cdn.rawgit.com/mdehoog/Semantic-UI/6e6d051d47b598ebab05857545f242caf2b4b48c/dist/semantic.min.js">
</script>

<script href="<?php echo SITE_URL; ?>js/datetimepicker_ra.js"></script>
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css" crossorigin="anonymous" />
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.min.js" crossorigin="anonymous"></script>
<link href="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.11/summernote-bs4.css" rel="stylesheet" />

<!-- Sweet alert CDn -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<section id="Dashboard_section">
    <div class="d-flex">
        <?php echo $this->element('organizerdashboard'); ?>

        <!-- <div class="col-sm-9"> -->
        <div class="dsa_contant">
            <?php echo $this->element('allevent'); ?>
            <div class="pro_section">
                <div class="table-responsive">
                    <div class="scroll_tab">
                        <ul id="progressbar">
                            <li class="active"><a href="<?php echo SITE_URL; ?>event/settings/<?php echo $id; ?>">Manage
                                    Event</a> </li>
                            <?php if ($eventDetails['is_free'] == 'Y') { ?>
                                <li><a href="<?php echo SITE_URL; ?>event/attendees/<?php echo $id; ?>">Manage Attendees</a>
                                </li>
                            <?php } else { ?>
                                <li><a href="<?php echo SITE_URL; ?>event/manage/<?php echo $id; ?>">Manage Tickets</a></li>
                                <li><a href="<?php echo SITE_URL . "event/committee/" . $id; ?>">Manage Committee</a> </li>
                            <?php  } ?>
                            <li><a href="<?php echo SITE_URL; ?>event/generalsetting/<?php echo $id; ?>">Publish
                                    Event</a></li>
                        </ul>
                    </div>
                </div>
            </div>

            <h4>Manage Event Settings </h4>
            <hr>
            <p>You can manage all your event settings here.</p>

            <ul class="tabes d-flex">
                <li><a class="active" href="#">Settings</a></li>
            </ul>
            <div class="contant_bg">
                <div class="event_settings">
                    <h6>Event Settings</h6>
                    <?php echo $this->Flash->render(); ?>
                    <form method="post" enctype="multipart/form-data" accept-charset="utf-8" id="formsubmit" class="row g-3 needs-validation">
                        <!-- <div class="row g-3"> -->

                        <div class="col-md-6">
                            <label for="inputName" class="form-label">Event Name<strong style="color:red;">*</strong></label>
                            <?php
                            if ($eventDetails['name']) {
                                $name = $eventDetails['name'];
                            }
                            ?>
                            <input type="text" class="form-control" name="name" placeholder="Event Name" required value="<?php echo isset($name) ? $name : '' ?>">
                        </div>

                        <div class="col-md-6">
                            <label for="inputName" class="form-label">Location<strong style="color:red;">*</strong></label>

                            <?php
                            if ($eventDetails['location']) {
                                $location = $eventDetails['location'];
                            }
                            echo $this->Form->input('location', array('class' => 'form-control ', 'type' => 'text', 'placeholder' => 'Location', 'required', 'label' => false, 'value' => ($location) ? $location : "")); ?>
                        </div>

                        <div class="col-md-6">
                            <label for="inputState" class="form-label">Company<strong style="color:red;">*</strong></label>
                            <?php
                            if ($eventDetails['company_id']) {
                                $company_id = $eventDetails['company_id'];
                            }
                            echo $this->Form->input(
                                'company_id',
                                ['empty' => 'Choose Company', 'options' => $company, 'default' => ($company_id) ? $company_id : "", 'required' => 'required', 'class' => 'form-select', 'label' => false]
                            ); ?>
                        </div>
                        <div class="col-md-6">
                            <label for="inputState" class="form-label">Country<strong style="color:red;">*</strong></label>
                            <?php
                            if ($eventDetails['country_id']) {
                                $country_id = $eventDetails['country_id'];
                            }
                            echo $this->Form->input(
                                'country_id',
                                ['empty' => 'Choose Country', 'options' => $country, 'default' => ($country_id) ? $country_id : "", 'required' => 'required', 'class' => 'form-select', 'label' => false]
                            ); ?>
                        </div>

                        <div class="col-md-6">
                            <label for="inputName" class="form-label">URL Slug<strong style="color:red;">*
                                </strong></label><span id="checkalready"><strong style="color:red;">Already
                                    exist</strong></span>
                            <?php
                            if ($eventDetails['slug']) {
                                $slug = $eventDetails['slug'];
                            }
                            ?>
                            <input type="text" required class="form-control slug" placeholder="Slug" id="slugyfy" name="slug" value="<?php echo isset($slug) ? $slug : '' ?>">

                        </div>

                        <div class="col-md-6">
                            <label for="inputName" class="form-label">Share URL</label>
                            <span class="slug-display form-control"><?php echo SITE_URL . 'event/'; ?><?php echo isset($slug) ? $slug : ''; ?></span>
                        </div>

                        <div class="col-md-6">
                            <?php
                            if ($eventDetails['date_from']) {
                                $date_from = date("d-m-Y h:i:s A", strtotime($eventDetails['date_from']));
                            }
                            if ($eventDetails['date_to']) {
                                $date_to = date("d-m-Y h:i:s A", strtotime($eventDetails['date_to']));
                            }
                            if ($eventDetails['sale_start']) {
                                $sale_start = date("d-m-Y h:i:s A", strtotime($eventDetails['sale_start']));
                            }
                            if ($eventDetails['sale_end']) {
                                $sale_end = date("d-m-Y h:i:s A", strtotime($eventDetails['sale_end']));
                            }
                            if ($eventDetails['request_rsvp']) {
                                $request_rsvp = date("d-m-Y h:i:s A", strtotime($eventDetails['request_rsvp']));
                            }
                            ?>
                            <label for="inputName" class="form-label">Event Start:-(<?php echo $date_from; ?>)<strong style="color:red;">*</strong></label>

                            <div class="ui calendar" id="example1">
                                <div class="ui input left icon">
                                    <i class="calendar icon"></i>
                                    <input class="form-control" type="text" name="date_from" placeholder="Date/Time" autocomplete="off" value="">
                                </div>
                            </div>

                        </div>

                        <div class="col-md-6">
                            <label for="inputName" class="form-label">Event End:-(<?php echo $date_to; ?>)<strong style="color:red;">*</strong></label>
                            <div class="ui calendar" id="exampleE">
                                <div class="ui input left icon">
                                    <i class="calendar icon"></i>
                                    <input class="form-control" type="text" name="date_to" id="date_to" disabled value="" placeholder="Date/Time" autocomplete="off">
                                </div>
                            </div>
                        </div>
                        <?php
                        if ($eventDetails['is_free'] == 'N') { ?>
                            <div class="col-md-6">
                                <label for="inputName" class="form-label">Sale Start:-(<?php echo $sale_start; ?>)<strong style="color:red;">*</strong></label>

                                <div class="ui calendar" id="example3">
                                    <div class="ui input left icon">
                                        <i class="calendar icon"></i>
                                        <input class="form-control" type="text" name="sale_start" placeholder="Date/Time" autocomplete="off" value="">
                                    </div>
                                </div>

                            </div>

                            <div class="col-md-6">
                                <label for="inputName" class="form-label">Sale End:-(<?php echo $sale_end; ?>)<strong style="color:red;">*</strong></label>
                                <div class="ui calendar" id="example4">
                                    <div class="ui input left icon">
                                        <i class="calendar icon"></i>
                                        <input class="form-control" type="text" name="sale_end" value="" placeholder="Date/Time" autocomplete="off">
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label for="inputState" class="form-label">Currency </label>
                                <?php

                                echo $this->Form->input(
                                    'payment_currency',
                                    ['empty' => 'Choose Payment Type', 'options' => $currency, 'required' => 'required', 'class' => 'form-select', 'label' => false, 'value' => ($eventDetails['payment_currency']) ? $eventDetails['payment_currency'] : ""]
                                );  ?>
                            </div>

                            <div class="col-md-6">
                                <label for="inputName" class="form-label">Ticket Limit per person</label>
                                <?php
                                $limit = [
                                    '1' => '1',
                                    '2' => '2',
                                    '3' => '3',
                                    '4' => '4',
                                    '5' => '5',
                                    '6' => '6',
                                    '7' => '7',
                                    '8' => '8',
                                    '9' => '9',
                                    '10' => '10',
                                    '11' => '11',
                                    '12' => '12',
                                    '13' => '13',
                                    '14' => '14',
                                    '15' => '15',
                                    '16' => '16',
                                    '17' => '17',
                                    '18' => '18',
                                    '19' => '19',
                                    '20' => '20',
                                    '25' => '25',
                                    '30' => '30',
                                    '35' => '35',
                                    '40' => '40',
                                    '45' => '45',
                                    '50' => '50'
                                ];

                                echo $this->Form->input(
                                    'ticket_limit',
                                    ['empty' => 'Choose Limit', 'options' => $limit, 'required' => 'required', 'class' => 'form-select', 'label' => false, 'value' => ($eventDetails['ticket_limit']) ? $eventDetails['ticket_limit'] : ""]
                                ); ?>
                            </div>

                            <div class="col-md-6">
                                <label for="inputState" class="form-label">Approval Expiry</label>
                                <?php

                                $approve = ['1' => '1', '2' => '2', '3' => '3', '4' => '4', '5' => '5', '6' => '6', '7' => '7'];

                                echo $this->Form->input(
                                    'approve_timer',
                                    ['empty' => 'Choose Days', 'options' => $approve, 'required' => 'required', 'class' => 'form-select', 'label' => false, 'value' => ($eventDetails['approve_timer']) ? $eventDetails['approve_timer'] : ""]
                                ); ?>
                            </div>
                        <?php } ?>

                        <?php if ($eventDetails['is_free'] == 'Y') { ?>
                            <div class="col-md-6">
                                <label for="inputState" class="form-label">Request
                                    RSVP:-(<?php echo $request_rsvp; ?>)<strong style="color:red;">*</strong></label>
                                <div class="ui calendar" id="request_rsvp">
                                    <div class="ui input left icon">
                                        <i class="calendar icon"></i>
                                        <input class="form-control" type="text" name="request_rsvp" placeholder="Date/Time" autocomplete="off" id="req_rsvp_input" disabled>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">

                                <div class="request_rsvp">
                                    <label style="opacity:0; padding-top: 4px;" class="form-label  mb-0">This</label>
                                    <div class="form-check freeEventCheck colorGreen" style="padding-left:1.7rem; width:max-content;">
                                        <input class="form-check-input" type="checkbox" name="allow_register" <?php echo ($eventDetails['allow_register'] == 'Y') ? 'checked' : ""; ?> value="Y" id="allow-register">
                                        <label class="form-check-label mb-0" for="allow-register">Allow Registration
                                        </label>
                                    </div>
                                </div>

                            </div>
                        <?php } ?>


                        <div class="col-md-<?php echo ($eventDetails['is_free'] == 'Y') ? 6 : 6; ?> mb-3">
                            <label for="formFile" class="form-label">Preview Image
                                <a class="preview_img" data-bs-toggle="modal" data-bs-target="#exampleModal">
                                    Preview Image
                                </a>
                            </label>


                        </div>

                        <!-- Modal -->
                        <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" style="font-weight: 600;" id="exampleModalLabel"><?php echo $eventDetails['name']; ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body text-center">
                                        <div class="sec_E_img">
                                            <img src="<?php echo IMAGE_PATH . 'eventimages/' . $eventDetails['feat_image']; ?>" alt="alt">
                                        </div>
                                        <div id="fileUploadSection" style="display: none;">
                                            <label for="myImg" class="form-label">Event Image <strong style="color: red;font-size: 14px;">(Size 550*550)*JPG,JPEG,PNG </strong></label>
                                            <input type="file" id="myImg" class="form-control file_upload" name="event_image" accept="image/png, image/gif, image/jpeg">
                                        </div>
                                        <button type="button" class="btn btn-primary mt-3" id="changeImageButton">Change Image</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <script>
                            var fileInput = document.getElementById('myImg');

                            document.getElementById('changeImageButton').addEventListener('click', function() {
                                var fileUploadSection = document.getElementById('fileUploadSection');
                                if (fileUploadSection.style.display === 'none') {
                                    fileUploadSection.style.display = 'block';
                                    this.innerHTML = 'Upload';
                                } else {
                                    fileUploadSection.style.display = 'none';
                                    this.innerHTML = 'Change Image';
                                }
                            });

                            document.getElementById('myImg').addEventListener('change', function() {
                                var fileInput = document.getElementById('myImg');
                                var file = fileInput.files[0];
                                console.log("🚀 ~ file: settings.ctp:309 ~ document.getElementById ~ file:", fileInput.files.length);

                                if (fileInput.files.length == 1) { // Check if files exist
                                    console.log("🚀 ~ file: settings.ctp:311 ~ document.getElementById ~ fileInput:", fileInput.files.length);
                                    document.getElementById('changeImageButton').addEventListener('click', function() {
                                        uploadImage(file);
                                    });
                                }
                            });


                            function uploadImage(file) {
                                $('.preloader').show();
                                var formData = new FormData();
                                var eventId = '<?php echo $eventDetails['id']; ?>';
                                formData.append('event_image', file);
                                console.log("🚀 ~ file: settings.ctp:322 ~ uploadImage ~ file:", file)
                                formData.append('event_id', eventId);

                                // Send AJAX request to update the image
                                $.ajax({
                                    async: true,
                                    data: formData,
                                    dataType: "json",
                                    type: "POST",
                                    url: "<?php echo SITE_URL; ?>event/eventimagechange",
                                    processData: false,
                                    contentType: false,
                                    success: function(response) {
                                        if (response.success) {
                                            $('.preloader').hide();
                                            var imageElement = document.querySelector('.sec_E_img img');
                                            imageElement.src = response.imageUrl;
                                            Swal.fire({
                                                title: response.message,
                                                icon: 'success',
                                            });
                                            fileInput.value = ""; // Clear the file input field
                                        } else {
                                            fileInput.value = "";
                                            $('.preloader').hide();
                                            Swal.fire({
                                                title: 'Oops...',
                                                text: response.message,
                                                icon: 'error',
                                            });
                                            console.error(response.message);
                                        }
                                    }
                                });
                            }
                        </script>


                        <div class="col-md-12  mb-3">
                            <label for="exampleFormControlTextarea1" class="form-label">Description<strong style="color:red;">*</strong></label>
                            <?php
                            if ($eventDetails['desp']) {
                                $desp = $eventDetails['desp'];
                            }
                            ?>
                            <textarea class="form-control" required name="desp" id="summernote" rows="3"><?php echo isset($desp) ? $desp : '' ?></textarea>
                        </div>

                        <div class="col-12 text-end">
                            <button type="submit" class="btn submit">Next</button>
                        </div>

                    </form>

                </div>
            </div>

        </div>
        <!-- </div> -->
    </div>
</section>

<script>
    // image validation 
    var _URL = window.URL || window.webkitURL;
    $("#myImg").change(function(e) {
        var changeImageButton = document.getElementById('changeImageButton');
        var file, img, Extension;
        Extension = this.files[0]['name'].split('.').pop();

        if (Extension == "png" || Extension == "jpeg" || Extension == "jpg") {
            // To Display
            var img = document.getElementById("myImg");
            if (img.files[0]) // validation according to file size
            {
                // uploadimage(img);

            }
            changeImageButton.disabled = true;
        } else {
            document.getElementById("myImg").value = "";
            $('#imagenamexx').html('');
            Swal.fire({
                title: 'Oops...',
                icon: 'error',
                text: 'Uploaded file is not a valid image. Only JPG, PNG and JPEG files are allowed.',
                // confirmButtonText: 'Cool'
            });
            changeImageButton.disabled = true;
            return true;
        }

        if ((file = this.files[0])) {
            img = new Image();
            var objectUrl = _URL.createObjectURL(file);
            img.onload = function() {
                if (this.width < 200 || this.height < 200) {
                    document.getElementById("myImg").value = "";
                    Swal.fire({
                        title: 'Oops...',
                        icon: 'error',
                        text: `Image dimensions are too small. Minimum (Size 200*200)*. Uploaded image (Size ${this.height} px * ${this.width})`,
                        // confirmButtonText: 'Cool'
                    });
                    var changeImageButton = document.getElementById('changeImageButton');
                    changeImageButton.disabled = true;
                }
                _URL.revokeObjectURL(objectUrl);
            };
            img.src = objectUrl;
        }
        changeImageButton.disabled = false;

    });

    var free = '<?php echo $eventDetails['is_free']; ?>';
    if (free == 'Y') {
        var li = document.getElementById('progressbar');
        let lis = document.getElementById('progressbar').getElementsByTagName('li');
        // document.getElementById('progressbar').style.background ="red";
        for (var i = 0; i < lis.length; i++) {
            lis[i].classList.add("changeprogressbar");
        }
    }

    $(document).ready(function() {

        var site_url = '<?php echo SITE_URL; ?>';
        var lateventid = '<?php echo $lateventid; ?>';

        $("#checkalready").hide();

        var slugify = function(text) {
            return text.toString()
                .replace(/\s+/g, '-') // Replace spaces with -
                .replace(/[^\w\-]+/g, '') // Remove all non-word chars
                .replace(/\-\-+/g, '-') // Replace multiple - with single -
                .replace(/^-+/, '') // Trim - from start of text
                .replace(/-+$/, ''); // Trim - from end of text
        }

        $('.slug').on('keyup', function(e) {

            text = $(e.target).val();
            if (text) {
                $('.slug-display').empty().append(site_url + 'event/<strong>' + slugify(text) +
                    '</strong>');
            } else {
                // $('.slug-display').empty().append(site_url+'event/' + '<strong></strong>');
                $('.slug-display').empty().append(SITE_URL + '<strong>' + lateventid + '</strong>');
                return false;
            }

            $.ajax({
                async: true,
                data: {
                    'exist_slug': text
                },
                dataType: "json",
                type: "POST",
                url: "<?php echo SITE_URL; ?>event/checkexist",
                success: function(data) {
                    if (data != null) {
                        $("#checkalready").show();
                        $("#slugyfy").val('');
                        $('.slug-display').empty().append(site_url + 'event/');
                    } else {
                        $("#checkalready").hide();
                    }
                },
            });


        }).on('blur', function(e) {
            $(e.target).val(slugify(e.target.value));
        });

    });
</script>
<!-- Calendra 1  -->

<script>
    var today = new Date();
    var monthNames = ["January", "February", "March", "April", "May", "June",
        "July", "August", "September", "October", "November", "December"
    ];
    $('#example1').calendar({
        type: 'datetime',
        formatter: {
            date: function(date, settings) {
                if (!date) return '';
                var day = date.getDate();
                var month = monthNames[date.getMonth()];
                var year = date.getFullYear();
                var newfdateformat = month + ' ' + day + ',' + year;
                return newfdateformat;
            }
        },
        minDate: new Date(today.getFullYear(), today.getMonth(), today.getDate(), today.getHours() + 1),
        onChange: function(date, text) {
            var fffsss = new Date(text);
            var event_end_date = new Date(text);
            $('#date_to').removeAttr("disabled")
            // $('span[id^="event_start_date"]').remove();
            if ($('#exampleE').val()) {

            } else {
                $('#exampleE').calendar({
                    type: 'datetime',
                    minDate: new Date(fffsss.getFullYear(), fffsss.getMonth(), fffsss.getDate(), fffsss
                        .getHours() + 1),
                    formatter: {
                        date: function(date, settings) {
                            if (!date) return '';
                            var day = date.getDate();
                            var month = monthNames[date.getMonth()];
                            var year = date.getFullYear();
                            var newfdateformat = month + ' ' + day + ',' + year;
                            return newfdateformat;
                        }
                    },
                    onChange: function(date, text) {
                        var fffsss = new Date(text);
                        $('#sale_start').removeAttr("disabled")
                        $('#req_rsvp_input').removeAttr("disabled")
                        $('#example3').calendar({
                            type: 'datetime',
                            minDate: new Date(today.getFullYear(), today.getMonth(), today
                                .getDate(), today.getHours()),
                            maxDate: new Date(fffsss.getFullYear(), fffsss.getMonth(),
                                fffsss.getDate(), fffsss.getHours() - 1),
                            onChange: function(date, text) {
                                $('#sale_end').removeAttr("disabled")
                                var fffsssdddss = new Date(text);
                                $('#example4').calendar({
                                    type: 'datetime',
                                    minDate: new Date(fffsssdddss.getFullYear(),
                                        fffsssdddss.getMonth(), fffsssdddss
                                        .getDate(), fffsssdddss.getHours()),
                                    maxDate: new Date(fffsss.getFullYear(),
                                        fffsss.getMonth(), fffsss.getDate(),
                                        fffsss.getHours() - 1),
                                });
                            }
                        });

                        $('#example4').calendar({
                            type: 'datetime',
                            minDate: new Date(today.getFullYear(), today.getMonth(), today
                                .getDate(), today.getHours()),
                            maxDate: new Date(fffsss.getFullYear(), fffsss.getMonth(),
                                fffsss.getDate(), fffsss.getHours() - 1),
                        });

                    }
                });

            }

            $('#request_rsvp').calendar({
                type: 'datetime',
                dateFormat: "dd.mm.yy",
                minDate: new Date(today.getFullYear(), today.getMonth(), today.getDate(), today
                    .getHours() - 1),
                maxDate: new Date(event_end_date.getFullYear(), event_end_date.getMonth(),
                    event_end_date.getDate(), event_end_date.getHours() - 1),
                // onChange: function(date, text) {
                //     var request_rsvp = new Date(text);
                //     $('#request_rsvp').calendar({
                //         type: 'datetime',
                //         minDate: new Date(today.getFullYear(), today.getMonth(), today.getDate(), today.getHours() - 1),
                //         maxDate: new Date(event_end_date.getFullYear(), event_end_date.getMonth(), event_end_date.getDate(), event_end_date.getHours() - 1),
                //     });
                // }
            });

        }
    });



    $('#exampleE').calendar({
        type: 'datetime',
        minDate: new Date(today.getFullYear(), today.getMonth(), today.getDate()),
        formatter: {
            date: function(date, settings) {
                if (!date) return '';
                var day = date.getDate();
                var month = monthNames[date.getMonth()];
                var year = date.getFullYear();
                var newfdateformat = month + ' ' + day + ',' + year;
                return newfdateformat;
            }
        },
        onChange: function(date, text) {
            $('span[id^="event_start_date"]').remove();
            if ($('#example1').val()) {} else {
                //alert('test');
                $("#example1").after(
                    '<span class="error" id ="event_start_date" style="color:red">Select Event start date</span>'
                );
                var fffsss = new Date(text);
                $('#example1').calendar({
                    type: 'datetime',
                    minDate: new Date(today.getFullYear(), today.getMonth(), today.getDate()),
                    maxDate: new Date(fffsss.getFullYear(), fffsss.getMonth(), fffsss.getDate()),
                    onChange: function(date, text) {
                        $('span[id^="event_start_date"]').remove();
                    }
                });


            }
            $('#example3').calendar({
                type: 'datetime',
                minDate: new Date(today.getFullYear(), today.getMonth(), today.getDate()),
                maxDate: new Date(fffsss.getFullYear(), fffsss.getMonth(), fffsss.getDate()),
                onChange: function(date, text) {
                    var fffsss = new Date(text);
                    //alert('test');
                    var fffssdds = new Date($('#exampleE').val());
                    $('#example4').calendar({
                        type: 'datetime',
                        minDate: new Date(fffsss.getFullYear(), fffsss.getMonth(), fffsss
                            .getDate()),
                        maxDate: new Date(fffssdds.getFullYear(), fffssdds.getMonth(),
                            fffssdds.getDate()),
                    });
                }
            });

            $('#example4').calendar({
                type: 'datetime',
                minDate: new Date(today.getFullYear(), today.getMonth(), today.getDate()),
                maxDate: new Date(fffsss.getFullYear(), fffsss.getMonth(), fffsss.getDate()),
            });

        }
    });


    $('#example3').calendar({
        type: 'datetime',
        minDate: new Date(today.getFullYear(), today.getMonth(), today.getDate()),
        onChange: function(date, text) {
            var fffsss = new Date(text);

            var fffssdds = new Date($('#exampleE').val());
            $('#example4').calendar({
                type: 'datetime',
                minDate: new Date(fffsss.getFullYear(), fffsss.getMonth(), fffsss.getDate()),
                maxDate: new Date(fffssdds.getFullYear(), fffssdds.getMonth(), fffssdds.getDate()),
            });
        }
    });

    $('#example4').calendar({
        type: 'datetime',
        minDate: new Date(today.getFullYear(), today.getMonth(), today.getDate()),
    });
</script>

<script>
    $('#myModal').on('shown.bs.modal', function() {
        $('#myInput').trigger('focus')
    })
</script>