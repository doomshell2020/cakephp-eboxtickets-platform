<div class="event_detales">

                <!-- <div class="row"> -->
                <div class="table-responsive">
                  <!-- <div class="table_scroll"> -->
                  <table class="table table-hover">
                    <thead class="table-dark table_bg">
                      <tr>
                        <th style="width: 2%;" scope="col">#</th>
                        <th style="width: 14%;" scope="col">Name</th>
                        <th style="width: 17%;" scope="col">Date and Time</th>
                        <th style="width: 18%;" scope="col">Ticket Sale</th>
                        <th style="width: 8%;" scope="col">Venue</th>
                        <th style="width: 16%;" scope="col">Ticket Types</th>
                        <th style="width: 15%;" scope="col">Action</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php $i = 1;
                      if (!empty($event)) {
                        foreach ($event as $key => $value) { ?>

                          <tr>
                            <th scope="row"><?php echo $i; ?></th>
                            <td><img src="<?php echo IMAGE_PATH . 'eventimages/' . $value['feat_image'] ?>" alt="Not Found">
                              <a href="<?php echo SITE_URL; ?>event/settings/<?php echo $value['id']; ?>"><?php echo $value['name']; ?></a>
                            </td>


                            <td><b>From</b> <?php echo date('d M, Y h:i A', strtotime($value['date_from'])); ?><br>
                              <b>To</b> <?php echo date('d M, Y h:i A', strtotime($value['date_to'])); ?>
                            </td>
                            <td>
                              <?php if (!empty($value['sale_start'])) { ?>
                                <b>From</b> <?php echo date('d M, Y h:i A', strtotime($value['sale_start'])); ?><br>
                                <b>To</b> <?php echo date('d M, Y h:i A', strtotime($value['sale_end'])); ?>
                              <?php } else { ?>
                                N/A
                              <?php }  ?>
                            </td>
                            <td><?php echo $value['location']; ?></td>

                            <td class="ticket_types">

                              <?php if (!empty($value['eventdetail'])) {

                                foreach ($value['eventdetail'] as $key => $tickettype) {

                                  if ($value['is_free'] == 'Y') { ?>
                                    <p><?php echo $tickettype['title']; ?> - <Span>Invitation</Span></p>
                                  <?php  } else { ?>

                                    <p><?php echo $tickettype['title']; ?> - <Span><?php echo ($tickettype['type'] == 'open_sales') ? 'Online' : 'Committee'; ?></Span></p>
                                  <?php } ?>


                                <?php }
                              } else { ?>
                                <p>Tickets not created</p>
                              <?php } ?>

                            </td>
                            
                            <td class="Con_center ">


                              <div class=" align-items-baseline justify-content-evenly editIcos ">


                                <a target="_blank" href="<?php echo SITE_URL; ?>event/<?php echo $value['slug']; ?>" class="edit viewIcos " data-bs-placement="bottom" title=""><i class="bi bi-eye-fill"></i> View
                                </a>

                                <a target="_blank" href="<?php echo SITE_URL; ?>event/settings/<?php echo $value['id']; ?> " class="edit viewIcos" data-bs-placement="bottom" title=""><i class="fas fa-edit"></i> Edit
                                </a>


                                <a class="edit deleteIcos" href="<?php echo SITE_URL . 'event/deletevent/' . $value['id'];
                                                                  ?>" onclick="return confirmDelete(event)">

                                  <button type="button" class="edit" data-bs-placement="bottom" title="">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="#fff" class="bi bi-trash" viewBox="0 0 16 16">
                                      <path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6z"></path>
                                      <path fill-rule="evenodd" d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1v1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4H4.118zM2.5 3V2h11v1h-11z"></path>
                                    </svg> Delete
                                  </button>
                                </a>

                                <script>
                                  function confirmDelete(event) {
                                    event.preventDefault();
                                    const anchorTag = event.currentTarget.getAttribute('href');
                                    Swal.fire({
                                      title: 'Are you sure do you want to delete this event?',
                                      icon: 'warning',
                                      showCancelButton: true,
                                      confirmButtonColor: '#d33',
                                      cancelButtonColor: '#3085d6',
                                      confirmButtonText: 'Yes, delete it!',
                                      cancelButtonText: 'Cancel'
                                    }).then((result) => {
                                      if (result.isConfirmed) {
                                        window.location.href = anchorTag;
                                      }
                                    });
                                  }
                                </script>

                              </div>
                              <div class="d-flex">
                                <?php if ($value['status'] == 'Y') {  ?>

                                  <a class="action_btn disable_btn" href="<?php echo SITE_URL; ?>event/eventstatus/<?php echo $value['id']; ?>/N" class="btn " data-bs-toggle="tooltip" data-bs-placement="bottom" title="Deactivate Event">
                                    <img class="del-icon" style="width:16px;" src="<?php echo SITE_URL; ?>images/delete.png" alt="">

                                  </a>


                                <?php } else { ?>


                                  <a class="action_btn enable_btn " href="<?php echo SITE_URL; ?>event/eventstatus/<?php echo $value['id']; ?>/Y" class="btn " data-bs-toggle="tooltip" data-bs-placement="bottom" title="Activate Event">
                                    <i class="bi bi-check-circle-fill"></i>
                                  </a>



                                <?php } ?>


                                <a class="action_btn excel_btn" href="<?php echo SITE_URL; ?>event/exporttickets/<?php echo $value['id']; ?>" class="btn " data-bs-toggle="tooltip" data-bs-placement="bottom" title="Export tickets">

                                  <img class="del-icon" style="width:16px;" src="<?php echo SITE_URL; ?>images/export-icon.png" alt="">

                                </a>



                              </div>


                            </td>
                          </tr>

                        <?php $i++;
                        }
                      } else {  ?>
                        <tr>
                          <td colspan="7">
                            <center>
                              <p><i>You don`t have any event created.</i></p>
                            </center>
                          </td>
                        </tr>

                      <?php } ?>

                    </tbody>
                  </table>

                  <!-- </div> -->


                </div>

                <!-- </div> -->

              </div>