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
                    <li>Seo Manager</li>
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
                        <strong class="card-title">Seo Manager</strong>
                        <a href="<?php echo SITE_URL; ?>admin/seomanager/add"><strong class=" btn btn-info card-title pull-right">Add</strong></a>
                    </div>



                    <div class="card-body">

                        <table id="bootstrap-data-table" class="table table-striped table-bordered ">
                            <thead>
                                <tr>
                                    <th scope="col"><?= $this->Paginator->sort('S.No.') ?></th>
                                    <th scope="col"><?= $this->Paginator->sort('Page Name') ?></th>
                                    <th scope="col"><?= $this->Paginator->sort('Page Location') ?></th>
                                    <th scope="col" class="actions"><?= $this->Paginator->sort('Title') ?></th>
                                    <th scope="col" class="actions"><?= __('Description') ?></th>
                                    <th scope="col" class="actions"><?= __('Keywords') ?></th>
                                    <th scope="col" class="actions"><?= __('Actions') ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $i = 1;
                                foreach ($seo as $se) { ?>
                                    <tr>
                                        <td><?= $i ?></td>
                                        <td><?= $se->page ?></td>
                                        <td><?= $se->location ?></td>
                                        <td><?= $se->title ?></td>
                                        <td><?= $se->description ?></td>
                                        <td><?= $se->keyword ?></td>
                                        <td>
                                            <?php if ($se->status == "Y") {  ?>
                                                <a href="<?php echo ADMIN_URL ?>seomanager/status/<?php echo $se->id . '/N'; ?>" title="Click to Inactive"><i class="fa fa-toggle-on" style="font-size: 20px !important; margin-left: 1px; color:green;" aria-hidden="true"></i></a>
                                            <?php  } else { ?>

                                                <a href="<?php echo ADMIN_URL ?>seomanager/status/<?php echo $se->id . '/Y'; ?>" title="Click to Active"><i class="fa fa-toggle-off" style="font-size: 20px !important; margin-left: 1px;" aria-hidden="true"></i></a>
                                            <?php } ?>

                                            <?php echo $this->Html->link(__(''), ['action' => 'edit', $se->id], array('class' => 'fa fa-pencil-square-o fa-lg', 'title' => 'Edit', 'style' => 'font-size: 20px !important;margin-right:5px;')) ?>

                                        </td>


                                    </tr>
                                <?php
                                    $i++;
                                } ?>

                            </tbody>
                        </table>
                        <div class="paginator">
                            <ul class="pagination">
                                <?= $this->Paginator->first('<< ' . __('First')) ?>
                                <?= $this->Paginator->prev('< ' . __('Previous')) ?>
                                <?= $this->Paginator->numbers() ?>
                                <?= $this->Paginator->next(__('Next') . ' >') ?>
                                <?= $this->Paginator->last(__('Last') . ' >>') ?>
                            </ul>
                            <p><?= $this->Paginator->counter(['format' => __('Page {{page}} of {{pages}}, showing {{current}} record(s) out of {{count}} total')]) ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>