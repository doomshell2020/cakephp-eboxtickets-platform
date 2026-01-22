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
            <th style="width: 9%;">Customer Pay</th>
            <th>Admin Commision</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $total_amount = 0; // initialize total amount variable to 0
        $total_commission_amount = 0; // initialize total commission amount variable to 0
        $i = 1;
        foreach ($getAllOrderData as $key => $indValue) {
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

                <td><?php echo $indValue['ticket'][0]['event']['name']; ?></td>

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
                    <?php echo $indValue['ticket'][0]['event']['currency']['Currency_symbol']; ?><?php echo  number_format($total_amount, 2); ?> <?php echo $indValue['ticket'][0]['event']['currency']['Currency']; ?></strong>
            </td>

            <td>
                <strong>
                    <?php echo $indValue['ticket'][0]['event']['currency']['Currency_symbol']; ?><?php echo number_format($total_commission_amount, 2); ?> <?php echo $indValue['ticket'][0]['event']['currency']['Currency']; ?></strong>
            </td>

        </tr>
    </tfoot>
</table>
<?php echo $this->element('admin/pagination'); ?>