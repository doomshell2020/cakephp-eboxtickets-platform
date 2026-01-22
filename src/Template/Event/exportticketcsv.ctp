<?php
$objPHPExcel = new PHPExcel();

// ---- DOCUMENT PROPERTIES ----
$objPHPExcel->getProperties()
    ->setCreator("EboxTicket")
    ->setLastModifiedBy("EboxTicket")
    ->setTitle("Event Tickets Export")
    ->setSubject("Tickets Data")
    ->setDescription("Exported ticket data with QR codes");

// ---- HEADER STYLE ----
$headerStyle = array(
    'font' => array(
        'bold' => true,
        'color' => array('rgb' => 'FFFFFF')
    ),
    'alignment' => array(
        'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
        'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER
    ),
    'fill' => array(
        'type' => PHPExcel_Style_Fill::FILL_SOLID,
        'startcolor' => array('rgb' => '4a5362')
    ),
    'borders' => array(
        'allborders' => array(
            'style' => PHPExcel_Style_Border::BORDER_THIN
        )
    )
);

// ---- DATA STYLE ----
$dataStyle = array(
    'alignment' => array(
        'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER,
        'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_LEFT,
        'wrap' => true
    ),
    'borders' => array(
        'allborders' => array(
            'style' => PHPExcel_Style_Border::BORDER_THIN
        )
    )
);

// ---- COLUMN HEADERS ----
$headers = array(
    'A' => 'QR Code',
    'B' => 'BarCode Data',
    'C' => 'Ticket No',
    'D' => 'Is Scanned',
    'E' => 'Ticket Name',
    'F' => 'Ticket Amount',
    'G' => 'Currency',
    'H' => 'Username',
    'I' => 'Print Name',
    'J' => 'Email',
    'K' => 'Mobile',
    'L' => 'Country',
    'M' => 'Purchase Date'
);

$sheet = $objPHPExcel->setActiveSheetIndex(0);

// Set headers
foreach ($headers as $col => $text) {
    $sheet->setCellValue($col . '1', $text);
}

// Apply header style
$sheet->getStyle('A1:M1')->applyFromArray($headerStyle);

// Freeze top row
$sheet->freezePane('A2');

// Auto Filter
$sheet->setAutoFilter('A1:M1');

// ---- COLUMN AUTO WIDTH ----
foreach (range('A', 'M') as $columnID) {
    $sheet->getColumnDimension($columnID)->setAutoSize(true);
}

// ---- DATA POPULATION ----
$row = 2;

if (!empty($ticket_data)) {

    foreach ($ticket_data as $people) {

        // Row height for QR
        $sheet->getRowDimension($row)->setRowHeight(90);

        // QR Code Image
        $barCodePath = WWW_ROOT . 'qrimages/temp/' . $people['qrcode'];

        if (!empty($people['qrcode']) && file_exists($barCodePath)) {

            $objDrawing = new PHPExcel_Worksheet_Drawing();
            $objDrawing->setName('QR Code');
            $objDrawing->setDescription('QR Code');
            $objDrawing->setPath($barCodePath);
            $objDrawing->setHeight(80);
            $objDrawing->setCoordinates('A' . $row);
            $objDrawing->setOffsetX(10);
            $objDrawing->setWorksheet($sheet);
        } else {
            $sheet->setCellValue('A' . $row, 'No QR');
        }

        // Ticket Name Logic
        $ticketName = $people['ticket']['eventdetail']['title'];

        if (!empty($people['package_id'])) {
            $ticketName .= ' (Package)';
        }

        // Country
        $country = !empty($people['user']['country']['CountryName'])
            ? $people['user']['country']['CountryName']
            : 'N/A';

        // Set values
        $sheet->setCellValue('B' . $row, $people['user_id'] . ',' . $people['ticket_num']);
        $sheet->setCellValue('C' . $row, $people['ticket_num']);
        $sheet->setCellValue('D' . $row, ($people['status'] == 1) ? 'Yes' : 'No');
        $sheet->setCellValue('E' . $row, $ticketName);
        $sheet->setCellValue('F' . $row, $people['ticket']['amount']);
        $sheet->setCellValue('G' . $row, $event_data['currency']['Currency']);
        $sheet->setCellValue('H' . $row, $people['user']['name'] . ' ' . $people['user']['lname']);
        $sheet->setCellValue('I' . $row, $people['name']);
        $sheet->setCellValue('J' . $row, $people['user']['email']);
        $sheet->setCellValue('K' . $row, $people['user']['mobile']);
        $sheet->setCellValue('L' . $row, $country);
        $sheet->setCellValue('M' . $row, date('d M Y h:i A', strtotime($people['created'])));

        // Amount formatting
        $sheet->getStyle('F' . $row)
            ->getNumberFormat()
            ->setFormatCode('#,##0.00');

        // Apply data style
        $sheet->getStyle('A' . $row . ':M' . $row)->applyFromArray($dataStyle);

        $row++;
    }
}

// ---- FINAL TOUCH ----
$sheet->setTitle('Tickets Export');
$sheet->calculateWorksheetDimension();

// ---- OUTPUT ----
$filename = "Event_Tickets_" . date('d_m_Y_H_i_s') . ".xlsx";

ob_end_clean();

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
$objWriter->save('php://output');

exit;
