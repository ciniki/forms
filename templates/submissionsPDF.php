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

    ciniki_core_loadMethod($ciniki, 'ciniki', 'images', 'private', 'loadCacheJPEG');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'forms', 'private', 'submissionLoad');

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
        public $header_height = 7; // height of title
        public $tenant_details = array();
        public $courses_settings = array();
        public $footer_text = '';

        public function Header() {
            $this->setFont('', 'B', 14);
            $this->MultiCell(180, 10, $this->title, 0, 'C', 0, 1, '', '', true, 0, false, true, 10, 'T');
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


        $pdf->title = $form['name'] . ' - Submission';
        

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
                $lh = 0;
                $section_height = 0;
                $cur_line = 0;
                $cur_col = 0;
                $line_heights = array();
                foreach($section['fields'] as $fid => $field) { 
                    if( $field['ftype'] == 'newline' ) {
                        continue;
                    }
//                    if( $field['ftype'] == 'break' ) {
//                        $pdf->MultiCell($w[0], '', '', 0, 'L', 0, 1);
//                    }
                    $newline = 1;
                    if( $field['ftype'] == 'address' ) {
                        $w = array(40, 140);
                    } elseif( ($field['field_size'] == 'small' || $field['field_size'] == 'small-medium') 
                        && isset($field['group_size']) && $field['group_size'] == 3 
                        ) {
                        if( $field['ftype'] == 'phone' ) {
                            $w = array(32, 28);
                        } else {
                            $w = array(26, 34);
                        }
                        $newline = ($cur_col > 1 ? 1 : 0);
                        //$newline = ($pdf->getX() > 110 ? 1 : 0);
                    } elseif( $field['field_size'] == 'small' ) {   
                        $w = array(40, 50);
                        $newline = ($cur_col > 0 ? 1 : 0);
//                        $newline = ($pdf->getX() > 80 ? 1 : 0);
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
                        $section['fields'][$fid]['value'] = $addr;
                        $field['value'] = $addr;
                    }
                    //
                    // Find the tallest field for this line
                    //
                    $pdf->setFont('', 'B', 10);
                    $label_height = $pdf->getStringHeight($w[0], $field['label']);
                    $value_height = isset($field['value']) ? $pdf->getStringHeight($w[1], $field['value']) : 0;
                    if( $label_height > $lh ) {
                        $lh = $label_height;
                    }
                    if( $value_height > $lh ) {
                        $lh = $value_height;
                    }
                    $section['fields'][$fid]['widths'] = $w;
                    $section['fields'][$fid]['line'] = $cur_line;
                    $section['fields'][$fid]['newline'] = $newline;
                    $cur_col++;
                    if( $newline == 1 ) {
                        $line_heights[$cur_line] = $lh;
                        $section_height += $lh;
                        $cur_line++;
                        $cur_col = 0;
                        $lh = 0;
                    }
                }

                //
                // Check if section will fit
                //
                if( $section_height < 100 && $pdf->getY() > ($pdf->getPageHeight() - $section_height - 30) ) {
                    $pdf->AddPage(); 
                }
                //
                // Output the section heading
                //
                $repeats = ($section['flags']&0x01) == 0x01 ? $section['max_repeats'] : 1;
                for($i = 1; $i <= $repeats; $i++) {
                    //
                    // Check for empty repeat
                    //
                    if( $i > 1 ) {
                        $data = 'no';
                        foreach($section['fields'] as $fid => $field) {
                            if( isset($field['ftype']) && $field['ftype'] == 'image' 
                                && isset($field['values'][$i]) && $field['values'][$i] != '0' 
                                ) {
                                $data = 'yes';
                            }
                            if( isset($field['values'][$i]) && $field['values'][$i] != '' ) {
                                $data = 'yes';
                            }
                        }
                        if( $data == 'no' ) {
                            continue;
                        }
                    }

                    if( $pdf->GetY() > ($pdf->getPageHeight() - 50) ) {
                        $pdf->AddPage();
                    }
                    $fill = 1;
                    $pdf->setFont('', 'B', 12);
                    $pdf->MultiCell(180, 12, $section['label'] . ($repeats > 1 ? ' #' . $i : ''), 0, 'L', 0, 1, '', '', true, 0, false, true, 12, 'B');
                    foreach($section['fields'] as $fid => $field) { 
                        if( $field['ftype'] == 'newline' ) {
                            continue;
                        }
                        if( $repeats > 1 ) {
                            $field['value'] = isset($field['values'][$i]) ? $field['values'][$i] : '';
                        } 
                        if( $field['ftype'] == 'break' ) {
                            $pdf->MultiCell($w[0], '', '', 0, 'L', 0, 1);
                        }
                        if( $pdf->GetY() > ($pdf->getPageHeight() - 40) ) {
                            $pdf->AddPage();
                            $pdf->setFont('', 'B', 12);
                            $pdf->MultiCell(180, 12, $section['label'] . ($repeats > 1 ? ' #' . $i : '') . ' - continued', 0, 'L', 0, 1, '', '', true, 0, false, true, 12, 'B');
                        }
                        if( $field['ftype'] == 'textarea' ) {
                            $lh = $pdf->getStringHeight(180, $field['label']);
                            $pdf->setFont('', 'B', 10);
                            $pdf->MultiCell(180, $lh, $field['label'], 1, 'L', 1, 1);
                            $pdf->setFont('', '', 10);
                            $pdf->MultiCell(180, 0, (isset($field['value']) ? $field['value'] : ''), 1, 'L', 0, 1);
                            $fill=1;
                            continue;
                        }
                        elseif( $field['ftype'] == 'image' ) {
                            error_log(print_r($field,true));
                            $lh = 60;
                            if( $pdf->GetY() > ($pdf->getPageHeight() - 20 - $lh) ) {
                                $pdf->AddPage();
                            }
                            $pdf->setFont('', 'B', 10);
                            $cur_y = $pdf->GetY();
                            $pdf->MultiCell(90, $lh, $field['label'], 1, 'L', $fill, 0);
                            $cur_x = $pdf->GetX();
                            $pdf->MultiCell($w[1], $lh, '', 1, 'L', $fill, 1);

                            //
                            // Load and add image
                            //
                            $rc = ciniki_images_loadCacheJPEG($ciniki, $tnid, $field['value'], 320, 200);
                            if( $rc['stat'] == 'ok' ) {
                                $image = $rc['image'];
                                $img = $pdf->Image('@'.$image, $pdf->left_margin + 95, $cur_y+5, 80, 50, 'JPEG', '', '', false, 75, '', false, false, 0, 'CM');
                            }
                            continue;

                        }
                        $w = $field['widths'];
                        $lh = $line_heights[$field['line']];
                        $newline = $field['newline'];
                        $pdf->setFont('', 'B', 10);
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

        //
        // Add terms of use
        //
        if( isset($args['terms']) && $args['terms'] == 'yes' && isset($form['termsofuse']) && $form['termsofuse'] != '' ) {  
            $pdf->Ln();
            if( $pdf->GetY() > ($pdf->getPageHeight() - 40) ) {
                $pdf->AddPage();
            }
            $pdf->setFont('', 'B', 12);
            $pdf->MultiCell(180, 12, 'Terms & Conditions', 0, 'L', 0, 1, '', '', true, 0, false, true, 12, 'B');
            $pdf->setFont('', '', 10);
            $pdf->MultiCell(180, $lh, $form['termsofuse'], 0, 'L', 0, 0);    
        }
    }

    return array('stat'=>'ok', 'pdf'=>$pdf);
}
?>
