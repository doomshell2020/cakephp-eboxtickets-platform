<?php
class xtcpdf extends TCPDF {}
$pdf = new xtcpdf('L', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
ob_start();
$pdf->SetTitle('Event Summary Tender Report ' . $destiid['genrateid']);
$pdf->SetY(-550);
$pdf->SetFont('', '', 10, '', 'false');
$pdf->SetPrintHeader(false);
$pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE . ' 048', PDF_HEADER_STRING);
$pdf->setHeaderFont(array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
$pdf->setFooterFont(array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
$pdf->AddPage();
$commission = number_format($totalAmount['totalAmount'] * $admin_info['feeassignment'] / 100, 2);
$totalamount = number_format($totalAmount['totalAmount'], 2);
$tmparray = array("Sr.Number", "Tenderer Name", "Company Name", "Title of Tender", "Uploaded Documents", "Uploaded Documents Timestamp", "Login Time");

$pdf->Image('/path/to/your/project/webroot/img/logo.png', 15, 10, 40, '', 'PNG');
$image_url = SITE_URL . 'images/eventimages/' . $event['feat_image'];
$html = '<br><br>








<table width="100%" >
<tr>
        <td width="75%" valign="middle" colspan="4" style="border-top:1px solid #ddd; vertical-align: middle;  border-bottom:1px solid #ddd; border-left:1px solid #ddd; border-right:1px solid #ddd; ">
            <h2 style="text-align:center; font-size:16px; margin-top:0; margin-bottom:0px;">Event Payment Report   <br><small> ' . ' (' . $event['name'] . ')</small></h2> <br>
        </td>
         <td width="25%" style=" border-top:1px solid #ddd; border-bottom:1px solid #ddd; border-left:1px solid #ddd; border-right:1px solid #ddd;" rowspan="5">
            <img  src="' . $image_url . '" alt="event-image">
        </td>
     </tr>

     <tr>
        <td width="75%" colspan="4"  style="height: 30px; border-top:1px solid #ddd; border-bottom:1px solid #ddd; border-left:1px solid #ddd; border-right:1px solid #ddd;"></td>
     </tr>
     <tr nobr="true">
        <td width: 10%; style="border-top:1px solid #ddd; border-bottom:1px solid #ddd; border-left:1px solid #ddd; border-right:1px solid #ddd; line-height:35px;" >
            <b>&nbsp;Event Name :</b>
        </td>
        <td width: 30%; style="border-top:1px solid #ddd; border-bottom:1px solid #ddd; border-left:1px solid #ddd; border-right:1px solid #ddd; text-align: left; line-height:22px;" >&nbsp; ' . $event["name"] . '</td>
        <td width: 10%; style="border-top:1px solid #ddd; border-bottom:1px solid #ddd; border-left:1px solid #ddd; border-right:1px solid #ddd; line-height:35px;" >
            <b>&nbsp;Event Date:</b>
        </td>
        <td width: 25%; style="border-top:1px solid #ddd; border-bottom:1px solid #ddd; border-left:1px solid #ddd; border-right:1px solid #ddd; line-height:35px;" >
             &nbsp;' . date('F j<\s\up>S</\s\up> Y', strtotime($event["date_from"])) . ' @' . date('h:i a', strtotime($event["date_from"])) . '
        </td>
       
     </tr>


      <tr nobr="true">
        <td width: 10%; style="border-top:1px solid #ddd; border-bottom:1px solid #ddd; border-left:1px solid #ddd; border-right:1px solid #ddd; line-height:35px;" >
            <b>&nbsp;Location :</b>
        </td>
        <td width: 30%; style="border-top:1px solid #ddd; border-bottom:1px solid #ddd; border-left:1px solid #ddd; border-right:1px solid #ddd; line-height:35px;" >
            &nbsp;' . $event['location'] . '
        </td>
        <td width: 10%; style="border-top:1px solid #ddd; border-bottom:1px solid #ddd; border-left:1px solid #ddd; border-right:1px solid #ddd; line-height:35px;" >
            <b>&nbsp;Currency :</b>
        </td>
        <td width: 25%; style="border-top:1px solid #ddd; border-bottom:1px solid #ddd; border-left:1px solid #ddd; border-right:1px solid #ddd; line-height:35px;" >
           &nbsp;' . $event["currency"]['Currency'] . '
        </td>
       
     </tr>



       <tr nobr="true">
        <td width: 10%; style="border-top:1px solid #ddd; border-bottom:1px solid #ddd; border-left:1px solid #ddd; border-right:1px solid #ddd; line-height:35px;" >
            <b>&nbsp;Total Sales :</b>
        </td>
        <td width: 30%; style="border-top:1px solid #ddd; border-bottom:1px solid #ddd; border-left:1px solid #ddd; border-right:1px solid #ddd; line-height:35px;" >
             &nbsp;$' . $totalamount . $event["currency"]['Currency'] . '
        </td>
        <td width: 10%; style="border-top:1px solid #ddd; border-bottom:1px solid #ddd; border-left:1px solid #ddd; border-right:1px solid #ddd; line-height:35px;" >
            <b>&nbsp;Total Commission :</b>
        </td>
        <td width: 25%; style="border-top:1px solid #ddd; border-bottom:1px solid #ddd; border-left:1px solid #ddd; border-right:1px solid #ddd; line-height:35px;" >
           &nbsp;$' . $commission . $event["currency"]['Currency']  . '
        </td>
       
     
';
$findviewbid = $this->Comman->findviewbid($destiid["id"]);


$html .= '</tr>';



$html .= '<tr nobr="true">';
$html .= '</tr>






<tr nobr="true">
';
$html .= '</tr>';
$html .= '</table><br><br>';
$sums = 1;
$html .= '<table width="100%"><thead>
<tr  nobr="true" style="background-color:#4a5362;">
<td width="5%" style="color:#fff; font-size:9px; text-align:cnter; height:30px; line-height:31px;border-left:1px  solid #dddddd; border-right:0px  solid #dddddd;border-top:1px solid #dddddd; border-bottom:1px solid #dddddd;">
&nbsp;&nbsp;No#
</td>
<td width="8%" style="color:#fff; font-size:9px; text-align:cnter; height:30px; line-height:31px; border-top:1px solid #dddddd; border-bottom:1px solid #dddddd;border-right:0px  solid #dddddd;">Invoice No.</td>
<td width="15%" style="color:#fff; font-size:9px; text-align:cnter; height:30px; line-height:31px; border-top:1px solid #dddddd; border-bottom:1px solid #dddddd;border-right:0px  solid #dddddd;">Date</td>
<td width="15%" style="color:#fff; font-size:9px; text-align:cnter; height:30px; line-height:31px; border-top:1px solid #dddddd; border-bottom:1px solid #dddddd;border-right:0px  solid #dddddd;">Ticket Name</td>
<td width="10%" style="color:#fff; font-size:9px; text-align:cnter; height:30px; line-height:31px; border-top:1px solid #dddddd; border-bottom:1px solid #dddddd;border-right:0px  solid #dddddd;">Price</td>
<td width="10%" style="color:#fff; font-size:9px; text-align:cnter; height:30px; line-height:31px; border-top:1px solid #dddddd; border-bottom:1px solid #dddddd;border-right:0px  solid #dddddd;">Commission</td>
<td width="7%" style="color:#fff; font-size:9px; text-align:cnter; height:30px; line-height:31px; border-top:1px solid #dddddd; border-bottom:1px solid #dddddd;border-right:0px  solid #dddddd;">Type</td>
<td width="20%" style="color:#fff; font-size:9px; text-align:cnter; height:30px; line-height:31px; border-top:1px solid #dddddd; border-bottom:1px solid #dddddd;border-right:0px  solid #dddddd;">Customer Name</td>
<td width="10%" style="color:#fff; font-size:9px; text-align:cnter; height:30px; line-height:31px; border-top:1px solid #dddddd; border-bottom:1px solid #dddddd;border-right:0px  solid #dddddd;">Contact</td>
</tr></thead><tbody>';
if (isset($tickets) && !empty($tickets)) {
  $sums = 1;
  foreach ($tickets as $value) {
    $commission = number_format($value['amount'] * $value['order']['adminfee'] / 100, 2);
    $ticketprice = number_format($value['amount'], 2);
    $final_amount = number_format($value['amount'] + $value['amount'] * $value['order']['adminfee'] / 100, 2);
    $html .= '<tr nobr="true">
    <td width="5%" style="color:#000; font-size:9px; text-align:cnter; height:30px; line-height:31px;border-left:1px solid #dddddd; border-top:1px solid #dddddd; border-bottom:1px solid #dddddd;">
    &nbsp;';
    $html .= $sums++;
    $html .= '</td>';

    $html .= '<td width="8%" style="color:#000; font-size:9px; text-align:cnter; height:30px; line-height:31px;border-left:1px solid #dddddd; border-top:1px solid #dddddd; border-bottom:1px solid #dddddd;">
  &nbsp;' . $value['order']['id'] . '</td>';

    $html .= '<td width="15%" style="color:#000; font-size:9px; text-align:cnter; height:30px; line-height:31px;border-left:1px solid #dddddd; border-top:1px solid #dddddd; border-bottom:1px solid #dddddd;">
  &nbsp;' . date('d-m-Y h:i a', strtotime($value['order']['created'])) . '</td>';

    $html .= '<td width="15%" style="color:#000; font-size:9px; text-align:cnter; height:30px; line-height:31px;border-left:1px solid #dddddd; border-top:1px solid #dddddd; border-bottom:1px solid #dddddd;">
    &nbsp;' . $value['eventdetail']['title'] . '</td>';

    $html .= '<td width="10%" style="color:#000; font-size:9px; text-align:cnter; height:30px; line-height:31px;border-left:1px solid #dddddd; border-top:1px solid #dddddd; border-bottom:1px solid #dddddd;">
    &nbsp;' . $ticketprice . '</td>';
    $html .= '<td width="10%" style="color:#000; font-size:9px; text-align:cnter; height:30px; line-height:31px;border-left:1px solid #dddddd; border-top:1px solid #dddddd; border-bottom:1px solid #dddddd;">
    &nbsp;' . $commission . '</td>';

    $html .= '<td width="7%" align="center"; style="color:#000; font-size:9px; text-align:cnter; height:30px; line-height:18px;border-left:1px solid #dddddd; border-top:1px solid #dddddd; border-bottom:1px solid #dddddd;">'. $value['order']['paymenttype'] . '<br>('.$value['order']['paymentgateway'].')</td>';

    $html .= '<td width="20%" style="color:#000; font-size:9px; text-align:cnter; height:30px; line-height:31px;border-left:1px solid #dddddd; border-top:1px solid #dddddd; border-bottom:1px solid #dddddd;">
    &nbsp;' . $value['order']['user']['name'] . " " . $value['order']['user']['lname'] . '</td>';

    $html .= '<td width="10%" style="color:#000; font-size:9px; text-align:cnter; height:30px; line-height:31px;border-left:1px solid #dddddd; border-top:1px solid #dddddd; border-bottom:1px solid #dddddd;">
    &nbsp;' . $value['order']['user']['mobile'] . '</td>';


    $html .= '</tr>';
  }
} else {
  $html .= '<tr nobr="true">
    <td colspan="4" style="text-align:center;">No Tickets</td>
  </tr>';
}
$html .= '</tbody></table><br><br><br><br>';

// echo $html; die;

$pdf->WriteHTML($html, true, false, false, false, '');
ob_end_clean();
echo $pdf->Output('Event_Payment_Report_' . date('Y-m-d_H:i:s') . '.pdf', 'I');
exit;
