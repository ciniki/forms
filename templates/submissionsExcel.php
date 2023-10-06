<?php
//
// Description
// ===========
// This method will produce a PDF of the class.
//
// Arguments
// ---------
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_forms_templates_submissionsExcel(&$ciniki, $tnid, $args) {

    $submission_ids = $args['submission_ids'];


    require($ciniki['config']['core']['lib_dir'] . '/PHPExcel/PHPExcel.php');
    $objPHPExcel = new PHPExcel();
    $objPHPExcelWorksheet = $objPHPExcel->setActiveSheetIndex(0);

    $col = 0;
    $row = 1;

    $form_id = 0;
    $border_style = array(
        'borders' => array(
            'right' => array(
                'style' => PHPExcel_Style_Border::BORDER_THICK, 
                'color' => array('argb' => '000000'),
                ),
            ),
        );

    ciniki_core_loadMethod($ciniki, 'ciniki', 'forms', 'private', 'submissionLoad');
    foreach($submission_ids as $sid) {
        //
        // Load the submission
        //
        $rc = ciniki_forms_submissionLoad($ciniki, $tnid, $sid);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.189', 'msg'=>'Unable to load submission', 'err'=>$rc['err']));
        }
        $form = $rc['form'];
        $submission = $rc['form']['submission'];


        //
        // Add the headers
        //
        if( $form_id != $form['id'] ) {
            $row++;
            foreach($form['sections'] as $section) {
                $sec_col = $col;
                if( $col > 25 ) {
                    $sec_ltr = chr(floor($col/26) + 64) . chr(($col-26) + 65);
                } else {
                    $sec_ltr = chr($col + 65);
                }
                $cols = 0;
                if( isset($section['fields']) ) {
                    foreach($section['fields'] as $field) {
                        if( $field['ftype'] == 'newline' || $field['ftype'] == 'break' || $field['ftype'] == 'image' ) {
                            continue;
                        }
                        if( $col > 25 ) {
                            $ltr = chr(floor($col/26) + 64) . chr(($col-26) + 65);
                        } else {
                            $ltr = chr($col + 65);
                        }
                        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $field['label'], false);
                        $objPHPExcelWorksheet->getStyle("$ltr$row:$ltr$row")->getFont()->setBold(true);
                        $objPHPExcelWorksheet->getColumnDimension($ltr)->setAutoSize(true);
                        $cols++;
                    }
                }
                // Border on last field
                $objPHPExcelWorksheet->getStyle("$ltr$row:$ltr$row")->getBorders()->getRight()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
                if( $cols > 0 ) {
                    $objPHPExcelWorksheet->setCellValueByColumnAndRow($sec_col, $row-1, $section['label'], false);
                    $objPHPExcelWorksheet->mergeCells($sec_ltr . ($row-1) . ":" . $ltr . ($row-1));
                    $objPHPExcelWorksheet->getStyle($sec_ltr . ($row-1) . ":" . $sec_ltr . ($row-1))->getFont()->setBold(true);
                    $objPHPExcelWorksheet->getStyle($ltr . ($row-1) . ":" . $ltr . ($row-1))->getBorders()->getRight()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
                }
            }
            $col=0;
            $row++;
            $form_id = $form['id'];
        }

        //
        // Add the fields and responses
        //
        foreach($form['sections'] as $section) {
            //
            // Output the section heading
            //
            if( isset($section['fields']) ) {
                $fill = 0;
                foreach($section['fields'] as $field) { 
                    if( $col > 25 ) {
                        $ltr = chr(floor($col/26) + 64) . chr(($col-26) + 65);
                    } else {
                        $ltr = chr($col + 65);
                    }
                    if( $field['ftype'] == 'newline' || $field['ftype'] == 'break' || $field['ftype'] == 'image' ) {
                        continue;
                    }
                    if( $field['ftype'] == 'address' ) {
                        $addr = '';
                        if( isset($field['value']['address1']) && $field['value']['address1'] != '' ) {
                            $addr .= $field['value']['address1'];
                        }
                        if( isset($field['value']['address2']) && $field['value']['address2'] != '' ) {
                            $addr .= ($addr != '' ? ', ' : '') . $field['value']['address2'];
                        }
                        if( isset($field['value']['city']) && $field['value']['city'] != '' ) {
                            $addr .= ($addr != '' ? ', ' : '') . $field['value']['city'];
                        }
                        if( isset($field['value']['province']) && $field['value']['province'] != '' ) {
                            $addr .= ($addr != '' ? ', ' : '') . $field['value']['province'];
                        }
                        if( isset($field['value']['postal']) && $field['value']['postal'] != '' ) {
                            $addr .= ($addr != '' ? '  ' : '') . $field['value']['postal'];
                        }
                        $field['value'] = $addr;
                    }
                    if( isset($field['value']) ) {
                        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col, $row, $field['value'], false);
                    }
                    $col++;
                }
                // Border on last field
                $objPHPExcelWorksheet->getStyle("$ltr$row:$ltr$row")->getBorders()->getRight()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
            }
        }
        $col=0;
        $row++;
    }

    //
    // FIXME: Resize the columns
    //

    return array('stat'=>'ok', 'excel'=>$objPHPExcel);
}
?>
