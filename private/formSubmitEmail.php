<?php
//
// Description
// -----------
// This function will email the form submission to the customer and the
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_forms_formSubmitEmail(&$ciniki, $tnid, $args) {

    if( !isset($args['form']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.200', 'msg'=>'No form specified'));
    }
    $form = $args['form'];

    //
    // Build PDF for emailing
    //
    if( ($form['flags']&0x08) == 0x08 || (isset($form['notify_emails']) && $form['notify_emails'] != '') ) {
        //
        // Load tenant details
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'tenantDetails');
        $rc = ciniki_tenants_tenantDetails($ciniki, $tnid);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $tenant_details = isset($rc['details']) ? $rc['details'] : array();

        //
        // Generate the PDF
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'forms', 'templates', 'submissionsPDF');
        $rc = ciniki_forms_templates_submissionsPDF($ciniki, $tnid, array(
            'tenant_details' => $tenant_details,
            'submission_ids' => array($form['submission']['id']),
            'terms' => 'yes',
            ));
        if( $rc['stat'] != 'ok' ) {
            error_log('ERR: Unable to generate submission pdf ' . print_r($rc['err'], true));
        } else {
             $pdf = $rc['pdf'];
        }
    }

    //
    // Email submission to the customer
    //
    if( ($form['flags']&0x08) == 0x08 && isset($pdf) ) {

        //
        // Load the customer details
        //
        if( !isset($args['customer']) ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'hooks', 'customerDetails2');
            $rc = ciniki_customers_hooks_customerDetails2($ciniki, $tnid, array('customer_id'=>$form['submission']['customer_id']));
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.201', 'msg'=>'Unable to load customer details', 'err'=>$rc['err']));
            }
            $customer = $rc['customer'];
            $customer['email'] = $rc['customer']['emails'][0]['address'];
        } else {
            $customer = $args['customer'];
        }

        $subject = $form['name'] . ' - Submission';
        if( isset($form['emailthankyou']) && $form['emailthankyou'] != '' ) {
            $htmlmsg = $form['emailthankyou'];
            $textmsg = strip_tags($htmlmsg);
        } else {
            $htmlmsg = "Thank you for your submission, we have attached a copy.";
            $textmsg = strip_tags($htmlmsg);
        }
        $filename = preg_replace('/[^a-zA-Z0-9_]/', '', preg_replace('/ /', '_', $subject)) . '.pdf';

        //
        // Send the email
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'mail', 'hooks', 'addMessage');
        $rc = ciniki_mail_hooks_addMessage($ciniki, $tnid, array(
            'object' => 'ciniki.forms.submission',
            'object_id' => $form['submission']['id'],
            'customer_id' => $customer['id'],
            'customer_email' => $customer['email'],
            'customer_name' => $customer['display_name'],
            'subject' => $subject,
            'html_content' => $textmsg,
            'text_content' => $textmsg,
            'attachments' => array(array('content'=>$pdf->Output($filename, 'S'), 'filename'=>$filename)),
            ));
        if( $rc['stat'] != 'ok' ) {
            error_log('ERR: Unable to email submission' . print_r($rc['err'], true));
        } else {
            $ciniki['emailqueue'][] = array('mail_id'=>$rc['id'], 'tnid'=>$tnid);
        }
    }

    //
    // Email submission to addresses specified
    //
    if( isset($form['notify_emails']) && $form['notify_emails'] != '' && isset($pdf) ) {
        if( $form['submission']['label'] != '' ) {
            $subject = $form['name'] . ' - ' . $form['submission']['label'] . ' - Submission';
        } else {
            $subject = $form['name'] . ' - Submission';
        }
        $htmlmsg = "You have received a form submission from {$request['session']['customer']['display_name']}.";
        $textmsg = strip_tags($htmlmsg);

        $filename = preg_replace('/[^a-zA-Z0-9_]/', '', preg_replace('/ /', '_', $subject)) . '.pdf';

        //
        // Send the email
        //
        $emails = explode(',', $form['notify_emails']);
        foreach($emails as $email) {
            $email = trim($email);
            if( $email != '' ) {
                ciniki_core_loadMethod($ciniki, 'ciniki', 'mail', 'hooks', 'addMessage');
                $rc = ciniki_mail_hooks_addMessage($ciniki, $tnid, array(
                    'object' => 'ciniki.forms.submission',
                    'object_id' => $form['submission']['id'],
                    'customer_id' => 0,
                    'customer_email' => $email,
                    'customer_name' => '',
                    'subject' => $subject,
                    'html_content' => $textmsg,
                    'text_content' => $textmsg,
                    'attachments' => array(array('content'=>$pdf->Output($filename, 'S'), 'filename'=>$filename)),
                    ));
                if( $rc['stat'] != 'ok' ) {
                    error_log('ERR: Unable to email submission' . print_r($rc['err'], true));
                } else {
                    $ciniki['emailqueue'][] = array('mail_id'=>$rc['id'], 'tnid'=>$tnid);
                }
            }
        }
    }
    return array('stat'=>'ok');
}
?>
