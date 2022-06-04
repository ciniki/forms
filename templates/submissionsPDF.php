<?php
//
// Description
// ===========
// This function will produce a PDF of 1-? of submissions.
//
// Arguments
// ---------
// 
// Returns
// -------
//
function ciniki_forms_templates_submissionsPDF(&$ciniki, $tnid, $args) {

    $submission_ids = $args['submission_ids'];

    //
    // Load TCPDF library
    //
    require_once($ciniki['config']['ciniki.core']['lib_dir'] . '/tcpdf/tcpdf.php');

    class MYPDF extends TCPDF {
        public $left_margin = 18;
        public $right_margin = 18;
        public $top_margin = 8;
        //Page header
        public $title = 'Submissions';
        public $header_image = null;
        public $header_name = '';
        public $header_addr = array();
        public $header_details = array();
        public $header_height = 10;      // The height of the image and address
        public $tenant_details = array();
        public $courses_settings = array();
        public $footer_text = '';

        public function Header() {
            $this->setFont('', 'B', 14);
            $this->MultiCell(180, 16, $this->title, 0, 'C', 0, 1, '', '', true, 0, false, true, 16, 'T');
        }

        // Page footer
        public function Footer() {
            $this->SetY(-15);
            $this->SetFont('helvetica', 'I', 8);
            $this->Cell(150, 10, $this->footer_text,
                0, false, 'L', 0, '', 0, false, 'T', 'M');
            $this->Cell(0, 10, 'Page ' . $this->pageNo().'/'.$this->getAliasNbPages(), 
                0, false, 'R', 0, '', 0, false, 'T', 'M');
        }
    }

    //
    // Start a new document
    //
    $pdf = new MYPDF('P', PDF_UNIT, 'LETTER', true, 'UTF-8', false);

    $pdf->tenant_details = $args['tenant_details'];

    //
    // Setup the title
    //
    if( isset($args['title']) ) {
        $pdf->title = $args['title'];
    }

    //
    // Setup the PDF basics
    //
    $pdf->SetCreator('Ciniki');
    $pdf->SetAuthor($args['tenant_details']['name']);
    $pdf->SetTitle($pdf->title);
    $pdf->SetSubject('');
    $pdf->SetKeywords('');

    // set margins
    $pdf->SetMargins($pdf->left_margin, $pdf->top_margin + $pdf->header_height, $pdf->right_margin);
    $pdf->SetHeaderMargin($pdf->top_margin);


    // set font and pdf features
    $pdf->SetFont('times', 'BI', 10);
    $pdf->SetCellPadding(2);
    $pdf->SetFillColor(235);
    $pdf->SetTextColor(0);
    $pdf->SetDrawColor(51);
    $pdf->SetLineWidth(0.15);

    //
    // Output the submissions
    //
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
        // Create the PDF of the submission
        //
        $pdf->AddPage();

        //
        // Setup footer text
        //
        if( $submission['status'] == 90 ) {
            $pdf->footer_text = "Submitted: " . $submission['dt_last_submitted_display'];
        } else {
            $pdf->footer_text = "Incomplete";
        }

        //
        // Add the fields and responses
        //
        $w = array(80, 100);
        foreach($form['sections'] as $section) {
            if( $pdf->getY() > ($pdf->getPageHeight() - 50) ) {
                $pdf->AddPage(); 
            }
            //
            // Output the section heading
            //
            $pdf->setFont('', 'B', 12);
            $pdf->MultiCell($w[0] + $w[1], 12, $section['label'], 0, 'L', 0, 1, '', '', true, 0, false, true, 12, 'B');
            if( isset($section['fields']) ) {
                $fill = 0;
                //
                // Determine field groups
                //
                $prev_field = null;
                $group_ids = array();
                foreach($section['fields'] as $fid => $field) { 
                    if( count($group_ids) == 0 ) {
                        $group_ids[] = $fid;
                        $prev_field = $field;
                        continue;
                    }
                    if( ($prev_field['field_size'] == 'small' || $prev_field['field_size'] == 'small-medium')
                        && ($field['field_size'] == 'small' || $field['field_size'] == 'small-medium')
                        ) {
                        $group_ids[] = $fid;
                        $prev_field = $field;
                        continue;
                    } 

                    //
                    // End of group
                    //
                    foreach($group_ids as $id) {
                        $section['fields'][$id]['group_size'] = count($group_ids);
                    }
                    $group_ids = array();
                    $prev_field = null;
                }
                foreach($group_ids as $id) {
                    $section['fields'][$id]['group_size'] = count($group_ids);
                }


                //
                // Output the fields
                //
                foreach($section['fields'] as $field) { 
                    if( $field['ftype'] == 'newline' ) {
                        continue;
                    }
                    if( $field['ftype'] == 'break' ) {
                        $pdf->MultiCell($w[0], '', '', 0, 'L', 0, 1);
                    }
                    $newline = 1;
                    if( $field['ftype'] == 'address' ) {
                        $w = array(40, 140);
                    } elseif( ($field['field_size'] == 'small' || $field['field_size'] == 'small-medium') 
                        && isset($field['group_size']) && $field['group_size'] == 3 
                        ) {
                        $w = array(26, 34);
                        $newline = ($pdf->getX() > 110 ? 1 : 0);
                    } elseif( $field['field_size'] == 'small' ) {   
                        $w = array(40, 50);
                        $newline = ($pdf->getX() > 80 ? 1 : 0);
                    } else {
                        $w = array(90, 90);
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
                    $pdf->setFont('', 'B', 10);
                    $lh = $pdf->getStringHeight($w[0], $field['label']);
                    if( isset($field['value']) && $pdf->getStringHeight($w[1], $field['value']) > $lh ) {
                        $lh = $pdf->getStringHeight($w[1], $field['value']);
                    }
                    $pdf->MultiCell($w[0], $lh, $field['label'], 1, 'L', $fill, 0);
                    $pdf->setFont('', '');
                    $pdf->MultiCell($w[1], $lh, (isset($field['value']) ? $field['value'] : ''), 1, 'L', $fill, $newline);
                    if( $newline == 1 ) {
                        $fill=!$fill;
                    }
                }
            }
        }
    }

    return array('stat'=>'ok', 'pdf'=>$pdf);
}
?>
