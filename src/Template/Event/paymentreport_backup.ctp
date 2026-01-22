<?php
class xtcpdf extends TCPDF {}

$pdf = new xtcpdf('L', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// ----------- BASIC PDF SETTINGS -----------
$pdf->SetCreator('Event Management System');
$pdf->SetAuthor('Admin');
$pdf->SetTitle('Event Payment Report');

$pdf->SetMargins(10, 10, 10);
$pdf->SetAutoPageBreak(TRUE, 10);
$pdf->SetPrintHeader(false);
$pdf->SetPrintFooter(false);

$pdf->SetFont('helvetica', '', 9);

$pdf->AddPage();

// ----------- CALCULATIONS -----------
$totalamount = number_format($totalAmount['totalAmount'], 2);

$image_url = SITE_URL . 'images/eventimages/' . $event['feat_image'];

// ----------- HEADER SECTION -----------
$html = '
<br>
<table width="100%" cellpadding="6" cellspacing="0" style="border:1px solid #cccccc;">
<tr>
    <td width="75%" style="text-align:center; border-right:1px solid #cccccc;">
        <h2 style="color:#2c3e50;">Event Payment Report</h2>
        <h4>(' . $event['name'] . ')</h4>
    </td>

    <td width="25%" style="text-align:center;">
        <img src="' . $image_url . '" style="height:90px;">
    </td>
</tr>
</table>

<br>

<table width="100%" cellpadding="6" cellspacing="0" style="border:1px solid #cccccc;">

<tr>
    <td width="20%" style="border:1px solid #cccccc;"><b>Event Name</b></td>
    <td width="30%" style="border:1px solid #cccccc;">' . $event["name"] . '</td>

    <td width="20%" style="border:1px solid #cccccc;"><b>Event Date</b></td>
    <td width="30%" style="border:1px solid #cccccc;">' . date('d-m-Y h:i A', strtotime($event["date_from"])) . '</td>
</tr>

<tr>
    <td style="border:1px solid #cccccc;"><b>Location</b></td>
    <td style="border:1px solid #cccccc;">' . $event["location"] . '</td>

    <td style="border:1px solid #cccccc;"><b>Currency</b></td>
    <td style="border:1px solid #cccccc;">' . $event["currency"]["Currency"] . '</td>
</tr>

<tr>
    <td style="border:1px solid #cccccc;"><b>Country</b></td>
    <td style="border:1px solid #cccccc;">' . $event["country"]["CountryName"] . '</td>

    <td style="border:1px solid #cccccc;"><b>Total Sales</b></td>
    <td style="border:1px solid #cccccc;">' . $totalamount . ' ' . $event["currency"]["Currency"] . '</td>
</tr>

</table>

<br><br>
';

// ----------- TABLE HEADER -----------
$html .= '
<table width="100%" cellpadding="5" cellspacing="0">
<thead>
<tr style="background-color:#2c3e50; color:#ffffff; text-align:center; font-size:10px;">
    <th width="5%">No#</th>
    <th width="8%">Invoice</th>
    <th width="12%">Date</th>
    <th width="13%">Ticket Name</th>
    <th width="8%">Price</th>
    <th width="14%">Payment Type</th>
    <th width="15%">Customer Name</th>
    <th width="10%">Contact</th>
    <th width="15%">Country</th>
</tr>
</thead>
<tbody>
';

// ----------- TABLE DATA -----------
if (!empty($tickets)) {

    $sums = 1;

    foreach ($tickets as $value) {

        $ticketprice = number_format($value['amount'], 2);

        $customerName = $value['order']['user']['name'] . " " . $value['order']['user']['lname'];

        $mobile = !empty($value['order']['user']['mobile'])
            ? $value['order']['user']['mobile']
            : 'N/A';

        $customerCountry = !empty($value['order']['user']['country']['CountryName'])
            ? $value['order']['user']['country']['CountryName']
            : 'N/A';

        $orderDate = date('d-m-Y h:i A', strtotime($value['order']['created']));

        $html .= '
        <tr style="text-align:center; font-size:9px;">
            <td width="5%" style="border:1px solid #dddddd;">' . $sums++ . '</td>
            <td width="8%" style="border:1px solid #dddddd;">' . $value['order']['id'] . '</td>
            <td width="12%" style="border:1px solid #dddddd;">' . $orderDate . '</td>
            <td width="13%" style="border:1px solid #dddddd;">' . $value['eventdetail']['title'] . '</td>
            <td width="8%" style="border:1px solid #dddddd;">' . $ticketprice . '</td>
            <td width="14%" style="border:1px solid #dddddd;">' . $value['order']['paymenttype'] . '</td>
            <td width="15%" style="border:1px solid #dddddd;">' . $customerName . '</td>
            <td width="10%" style="border:1px solid #dddddd;">' . $mobile . '</td>
            <td width="15%" style="border:1px solid #dddddd;">' . $customerCountry . '</td>
        </tr>
        ';
    }

} else {

    $html .= '
    <tr>
        <td colspan="9" style="text-align:center; border:1px solid #dddddd;">
            No Tickets Found
        </td>
    </tr>
    ';
}

$html .= '
</tbody>
</table>
';

// ----------- FOOTER SUMMARY -----------
$html .= '
<br><br>
<table width="100%" cellpadding="6" cellspacing="0">
<tr style="background:#f1f1f1;">
    <td width="70%" style="text-align:right; border:1px solid #cccccc;">
        <b>Total Sales Amount :</b>
    </td>

    <td width="30%" style="text-align:left; border:1px solid #cccccc;">
        <b>' . $totalamount . ' ' . $event["currency"]["Currency"] . '</b>
    </td>
</tr>
</table>
';

// ----------- GENERATE PDF -----------
$pdf->writeHTML($html, true, false, false, false, '');

ob_end_clean();

echo $pdf->Output('Event_Payment_Report_' . date('Y-m-d_H-i-s') . '.pdf', 'I');

exit;
