<?php
//
// Description
// -----------
// This function will process a wng request for the forms module.
//
// Arguments
// ---------
// ciniki:
// settings:        The web settings structure.
// tnid:     The ID of the tenant to get post for.
//
// args:            The possible arguments for posts
//
//
// Returns
// -------
//
function ciniki_forms_wng_formProcess(&$ciniki, $tnid, &$request, $section) {

    if( !isset($ciniki['tenant']['modules']['ciniki.forms']) ) {
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.forms.17', 'msg'=>"I'm sorry, the page you requested does not exist."));
    }

    //
    // Make sure a valid section was passed
    //
    if( !isset($section['settings']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.18', 'msg'=>"No forms specified"));
    }
    $s = $section['settings'];
    $blocks = array();
    $error_blocks = array();
    $cur_section_id = '';

    $base_url = '';
    for($i = 0; $i <= $request['cur_uri_pos']; $i++) {
        if( !isset($request['uri_split'][$i]) ) {
            break;
        }
        $base_url .= '/' . $request['uri_split'][$i];
    }

    //
    // Set now date time
    //
    $dt_now = new DateTime('now', new DateTimezone('UTC')); 

    //
    // Check for forms item request
    //
    if( isset($s['form-id']) && $s['form-id'] > 0 ) {
        $form_id = $s['form-id'];
    }
    elseif( isset($request['uri_split'][($request['cur_uri_pos']+0)])
        && $request['uri_split'][($request['cur_uri_pos']+0)] != '' 
        ) {
        $form_id = $request['uri_split'][($request['cur_uri_pos']+0)];
    } else {
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.forms.19', 'msg'=>"No forms specified"));
    }


    //
    // Check if submission specified
    //
    if( isset($request['uri_split'][($request['cur_uri_pos']+1)])
        && $request['uri_split'][($request['cur_uri_pos']+1)] != '' 
        ) {
        $submission_uuid = $request['uri_split'][($request['cur_uri_pos']+1)];
    }
//    error_log($base_url);
//    error_log($submission_uuid);

    //
    // Check to make sure logged in
    //
/*    if( !isset($request['session']['customer']['id']) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'wng', 'private', 'accountLoginProcess');
        $rc = ciniki_wng_accountLoginProcess($ciniki, $tnid, $request, array(
            'create-account' => 'simple',
            'return-url' => $request['base_url'] . '/' . implode('/', $request['uri_split']),
            ));
        return $rc;
    } */
  
    $customer_id = 0;
    if( isset($request['session']['customer']['id']) && $request['session']['customer']['id'] > 0 ) {
        $customer_id = $request['session']['customer']['id'];
    }

    //
    // Load the form
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'forms', 'wng', 'formLoad');
    $rc = ciniki_forms_wng_formLoad($ciniki, $tnid, $request, $form_id, $customer_id);
    if( $rc['stat'] == 'noauth' ) {
        $form = $rc['form'];
        ciniki_core_loadMethod($ciniki, 'ciniki', 'wng', 'private', 'accountLoginProcess');
        $rc = ciniki_wng_accountLoginProcess($ciniki, $tnid, $request, array(
            'create-account' => 'simple',
            'return-url' => $request['base_url'] . '/' . implode('/', $request['uri_split']),
            ));
        if( $rc['stat'] == 'ok' ) {
            array_unshift($rc['blocks'], array(
                'sequence' => 1,
                'type' => 'text',
                'class' => 'form-intro',
                'title' => $form['name'],
                'content' => isset($form['loginmsg']) && $form['loginmsg'] != '' ? $form['loginmsg'] : 'You must login or sign up for an account',
                ));
        }
        return $rc;
    }
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.forms.20', 'msg'=>'Form not found', 'err'=>$rc['err']));
    }
    $form = $rc['form'];

    if( isset($submission_uuid) ) {
        $form['submission_uuid'] = $submission_uuid;
    }

    //
    // Load all submissions for the customer for the form
    //
    if( $customer_id > 0 || isset($submission_uuid) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'forms', 'wng', 'formSubmissionsLoad');
        $rc = ciniki_forms_wng_formSubmissionsLoad($ciniki, $tnid, $request, $form);
        if( $rc['stat'] != 'ok' ) {
            $blocks[] = $block_title;
            $blocks[] = array(
                'type' => 'msg',
                'level' => 'error',
                'content' => 'Unable to load submissions',
                );
            return array('stat'=>'ok', 'blocks'=>$blocks);
        }
    }

    //
    // Check if a submission already exists
    //
    if( isset($form['submissions']) && count($form['submissions']) > 0 ) {
        foreach($form['submissions'] as $sid => $sub) {
            if( isset($submission_uuid) && $submission_uuid == $sub['uuid'] ) {
                $form['submission_id'] = $sub['id'];
            }
            if( $sub['label'] == 'New Submission' ) {  
                $new_submission_exists = 'yes';
            }
            if( $sub['status'] < 90 ) {
                $form['submissions'][$sid]['url'] = "<a class='button' href='{$request['base_url']}{$base_url}/{$sub['uuid']}'>Continue</a>";
            } elseif( ($form['flags']&0x08) == 0 ) {
                $form['submissions'][$sid]['url'] = "<a class='button' href='{$request['base_url']}{$base_url}/{$sub['uuid']}'>Update</a>";
            } else {
                $form['submissions'][$sid]['url'] = "";
            }
        }
    }

    //
    // Setup the list of submissions. This block could be used multiple times in the following code.
    //
    $block_title = array(
        'type' => 'title',
        'class' => 'form-title',
        'title' => $form['name'],
        );
    $block_submission_list = array(
        'type' => 'table',
        'class' => 'limit-width center limit-width-40',
        'columns' => array(
                array(
                    'label' => 'Submissions',
                    'field' => 'label',
                ),
                array(
                    'label' => 'Status',
                    'field' => 'status_text',
                ),
                array(
                    'label' => '',
                    'class' => 'alignright',
                    'field' => 'url',
                ),
            ),
        'rows' => isset($form['submissions']) ? $form['submissions'] : array(),
        );
    if( isset($form['submissions']) && count($form['submissions']) < $form['max_customer_submissions'] 
        && ($form['max_submissions'] <= 0 || ($form['num_submissions'] < $form['max_submissions']))
        && !isset($new_submission_exists)
        ) {
        $block_submission_new = array(
            'type' => 'buttons',
            'class' => 'aligncenter',
            'list' => array(
                array(
                    'text' => 'Start New Submission',
                    'page' => 0,
                    'url' => $base_url . '/new',
                    ),
                ),
            );
    }

    //
    // Check if form allows multiple submissions and none specified, then show the list
    //
    if( isset($form['max_customer_submissions']) 
        && $form['max_customer_submissions'] > 1
        && isset($form['submissions']) && count($form['submissions']) > 0
        && !isset($submission_uuid)
        ) {
        $blocks[] = $block_title;
        $blocks[] = $block_submission_list;
        if( isset($block_submission_new) ) {
            $blocks[] = $block_submission_new;
        }
        return array('stat'=>'ok', 'blocks'=>$blocks);
    } 
    //
    // Existing submission and submission is specified, Load the submission
    //
    elseif( ($customer_id > 0 && $form['max_customer_submissions'] <= 1) || $form['submission_id'] > 0 ) { 
        ciniki_core_loadMethod($ciniki, 'ciniki', 'forms', 'wng', 'submissionLoad');
        $rc = ciniki_forms_wng_submissionLoad($ciniki, $tnid, $request, $form);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.50', 'msg'=>'Unable to load submission', 'err'=>$rc['err']));
        }
    }

    //
    // Check if this is a new submission, then create the submission and redirect to full url
    //
    if( ($form['submission_id'] == 'new' || $form['submission_id'] == 0) ) {
        if( $form['max_customer_submissions'] > 0 
            && isset($form['submissions']) 
            && count($form['submissions']) >= $form['max_customer_submissions'] 
            ) {
            $blocks[] = $block_title;
            $blocks[] = array(
                'type' => 'msg',
                'level' => 'error',
                'content' => 'You have already submitted the maximum number allowed.',
                );
            if( $form['max_customer_submissions'] > 1 ) {
                $blocks[] = $block_submission_list;
            }
            return array('stat'=>'ok', 'blocks'=>$blocks);
        }
        if( $form['max_submissions'] > 0 
            && isset($form['num_submissions']) 
            && $form['num_submissions'] >= $form['max_submissions'] 
            ) {
            $blocks[] = $block_title;
            $blocks[] = array(
                'type' => 'msg',
                'level' => 'error',
                'content' => "We are sorry but we've reached the maximum number of submissions.",
                );
            if( $form['max_customer_submissions'] > 1 ) {
                $blocks[] = $block_submission_list;
            }
            return array('stat'=>'ok', 'blocks'=>$blocks);
        }
        //
        // Check if a "New Submission" already exists
        //
        if( $form['customer_id'] > 0 ) {
            $strsql = "SELECT submissions.id, "
                . "submissions.uuid "
                . "FROM ciniki_form_submissions AS submissions "
                . "WHERE submissions.customer_id = '" . ciniki_core_dbQuote($ciniki, $form['customer_id']) . "' "
                . "AND submissions.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . "AND submissions.label = 'New Submission' "
                . "AND submissions.form_id = '" . ciniki_core_dbQuote($ciniki, $form['id']) . "' "
                . "LIMIT 1 "
                . "";
            $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.forms', 'submission');
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.187', 'msg'=>'Unable to load submission', 'err'=>$rc['err']));
            }
            if( isset($rc['submission']) ) {
                header("Location: {$request['base_url']}{$base_url}/{$rc['submission']['uuid']}");
                return array('stat'=>'exit');
            }

            //
            // Create a new submission and redirect
            //
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
            $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.forms.submission', array(
                'form_id' => $form['id'],
                'object' => $form['object'],
                'object_id' => $form['object_id'],
                'customer_id' => $form['customer_id'],
                'invoice_id' => 0,
                'status' => 10,
                'label' => 'New Submission',
                ), 0x04);
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.71', 'msg'=>'Unable to add the submission', 'err'=>$rc['err']));
            }
            $form['submission_id'] = $rc['id'];
            $form['submission_uuid'] = $rc['uuid'];
        }
        //
        // Create new submission for anonymous customer
        //
        else {
            //
            // Create a new submission and redirect
            //
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
            $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.forms.submission', array(
                'form_id' => $form['id'],
                'object' => $form['object'],
                'object_id' => $form['object_id'],
                'customer_id' => 0,
                'invoice_id' => 0,
                'status' => 10,
                'label' => 'New Submission',
                ), 0x04);
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.71', 'msg'=>'Unable to add the submission', 'err'=>$rc['err']));
            }
            $form['submission_id'] = $rc['id'];
            $form['submission_uuid'] = $rc['uuid'];
            //
            // Redirect to the submission
            //
            if( isset($rc['uuid']) ) {
                header("Location: {$request['base_url']}{$base_url}/{$rc['uuid']}");
                return array('stat'=>'exit');
            }
        }

        //
        // Save the defaults for the form fields
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'forms', 'wng', 'formDefaultsApply');
        $rc = ciniki_forms_wng_formDefaultsApply($ciniki, $tnid, $request, $form);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.188', 'msg'=>'Unable to apply default values', 'err'=>$rc['err']));
        }

        header("Location: {$request['base_url']}{$base_url}/{$form['submission_uuid']}");
        return array('stat'=>'exit');
    }

    //
    // Check if single submission form and if already submitted
    //
    if( isset($form['submission']['status']) && $form['submission']['status'] >= 90 && $form['max_customer_submissions'] <= 1 ) {
        
//        $blocks[] = $block_title;
        $blocks[] = array(
            'type' => 'msg',
            'level' => 'error',
            'content' => (isset($form['alreadysubmitted']) && $form['alreadysubmitted'] != '' ? $form['alreadysubmitted'] : 'Only 1 submission allowed'),
            );
        return array('stat'=>'ok', 'blocks'=>$blocks);
    }

    //
    // Check if submission is to be submitted
    //
    if( (isset($_POST['action']) && $_POST['action'] == 'submit')
        || (isset($request['session']['cart-payment-success']) && $request['session']['cart-payment-success'] == 'yes')
        ) {
        $errors = 'no';
        //
        // If submitted after payment, remove so a refresh doesn't resubmit
        //
        if( isset($request['session']['cart-payment-success']) && $request['session']['cart-payment-success'] == 'yes' ) {
            $request['session']['cart-payment-success'] = 'no';
            unset($request['session']['cart-payment-success']);
            $cur_section_id = 'submit';
        } elseif( isset($_GET['submit']) ) {
            $cur_section_id = 'submit';
        }

        if( !isset($form['submission']) ) {
            $error_blocks[] = array(
                'type' => 'msg',
                'level' => 'error',
                'content' => 'You must complete all the fields in the form',
                );
        } 
        //
        // Check to make sure we haven't reached the maximum allowed submissions
        // The form can be resubmitted if it was already submitted (status = 90).
        //
        elseif( $form['max_submissions'] > 0 
            && isset($form['num_submissions']) 
            && $form['num_submissions'] >= $form['max_submissions'] 
            && $form['submission']['status'] < 90
            ) {
            $error_blocks[] = array(
                'type' => 'msg',
                'level' => 'error',
                'content' => "We are sorry but we've reached the maximum allowed number of submissions.",
                );
        }
        elseif( $form['submission']['status'] < 90 ) {
            //
            // Validate the form
            //
            ciniki_core_loadMethod($ciniki, 'ciniki', 'forms', 'private', 'submissionValidate');
            $rc = ciniki_forms_submissionValidate($ciniki, $tnid, $form);
            if( isset($rc['problems']) ) {
                $problem_list = '';
                foreach($rc['problems'] as $pid => $problem) {
                    $problem_list .= $problem . "\n";
                }
            }
            elseif( $rc['stat'] != 'ok' ) {
                $error_blocks[] = array(
                    'type' => 'msg',
                    'level' => 'error',
                    'content' => 'Error processing your submission, please try again or contact us for assistance.',
                    );
            }
            elseif( $form['fee_amount'] > 0 && $form['invoice_status'] != 50 ) {
                $blocks[] = array(
                    'type' => 'msg',
                    'level' => 'error',
                    'content' => 'Payment required',
                    );
            }
          
            //
            // If no errors, submittion the form
            //
            if( !isset($problem_list) && count($error_blocks) == 0 ) {
                ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
                $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.forms.submission', $form['submission']['id'], array(
                    'status' => 90,
                    'dt_last_submitted' => $dt_now->format('Y-m-d H:i:s'),
                    ), 0x04);
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.117', 'msg'=>'Unable to update the submission', 'err'=>$rc['err']));
                }
                //
                // Update the status in the submission list 
                //
                foreach($block_submission_list['rows'] as $sid => $s) {
                    if( $s['id'] == $form['submission']['id'] ) {
                        $block_submission_list['rows'][$sid]['status'] = 90;
                        $block_submission_list['rows'][$sid]['status_text'] = 'Submitted';
                        if( ($form['flags']&0x80) == 0 ) {
                            $block_submission_list['rows'][$sid]['url'] = "<a class='button' href='{$request['base_url']}{$base_url}/{$s['uuid']}'>Update</a>";
                        } else {
                            $block_submission_list['rows'][$sid]['url'] = "";
                        }
                    }
                }

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
                        'customer_id' => $request['session']['customer']['id'],
                        'customer_email' => $request['session']['customer']['email'],
                        'customer_name' => $request['session']['customer']['display_name'],
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
                    $subject = $form['name'] . ' - Submission';
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

                //
                // Form is now submitted
                //
            }
        }

        if( !isset($problem_list) && count($error_blocks) == 0 ) {
            $blocks[] = $block_title;
            $blocks[] = array(
                'type' => 'msg',
                'level' => 'success',
                'content' => (isset($form['thankyou']) && $form['thankyou'] != '' ? $form['thankyou'] : 'Thank you for your submission.'),
                );
            if( $form['max_customer_submissions'] > 1 ) {
                $blocks[] = $block_submission_list;
                if( isset($block_submission_new) ) {
                    $blocks[] = $block_submission_new;
                }
            }
            return array('stat'=>'ok', 'blocks'=>$blocks);
        }
    }

    //
    // Check if payment required, but no invoice yet
    //
    if( $form['fee_amount'] > 0 && $form['invoice_id'] == 0 ) {
        //
        // No invoice, create cart and add form fee
        //
        $form['sections']['submit']['fields']['payment'] = array(
            'id' => 'payment',
            'ftype' => 'payment', 
            'label' => $form['fee_label'] != '' ? $form['fee_label'] : 'Fee',
            'amount' => $form['fee_amount'],
            'cart-url' => $request['base_url'] . '/cart',
            'button-label' => $form['cartsubmit_label'] != '' ? $form['cartsubmit_label'] : 'Pay Now',
            );
        $request['session']['cart-redirect-success'] = $request['base_url'] . '/' . implode('/', $request['uri_split']) . '?submit';
    }
    //
    // Check if payment required, and invoice exists but not yet paid, redirect them to the cart
    //
    elseif( $form['fee_amount'] > 0 && $form['invoice_id'] > 0 && $form['invoice_status'] < 50 ) {
        // redirect to /cart
        $form['sections']['submit']['fields']['payment'] = array(
            'id' => 'payment',
            'ftype' => 'payment', 
            'label' => $form['fee_label'] != '' ? $form['fee_label'] : 'Fee',
            'amount' => $form['fee_amount'],
            'paid' => 'unpaidcart',
            'cart-url' => $request['base_url'] . '/cart',
            'button-label' => $form['cartsubmit_label'] != '' ? $form['cartsubmit_label'] : 'Pay Now',
            );
        $request['session']['cart-redirect-success'] = $request['base_url'] . '/' . implode('/', $request['uri_split']) . '?submit';
    }
    elseif( $form['fee_amount'] > 0 && $form['invoice_id'] > 0 && $form['invoice_status'] > 50 ) {
        $form['sections']['submit']['fields']['error'] = array(
            'id' => 'info',
            'ftype' => 'content',
            'label' => '',
            'description' => '<b>There was a problem with your payment, please contact us for help.</b>',
            );
    }
    //
    // No payment OR payment completed, display the submit button
    //
    else {
        if( $form['fee_amount'] > 0 ) {
            $form['sections']['submit']['fields']['payment'] = array(
                'id' => 'payment',
                'ftype' => 'payment', 
                'label' => $form['fee_label'] != '' ? $form['fee_label'] : 'Fee',
                'amount' => $form['fee_amount'],
                'paid' => 'yes',
                'button-label' => $form['cartsubmit_label'] != '' ? $form['cartsubmit_label'] : 'Pay Now',
                );
        }
        $form['sections']['submit']['fields']['submit'] = array(
            'id' => 'submit',
            'ftype' => 'submit', 
            'label' => (isset($form['submit_label']) && $form['submit_label'] != '' ? $form['submit_label'] : 'Submit'),
            );
    }
    if( isset($request['session']['cart-payment-success']) && $request['session']['cart-payment-success'] == 'yes' ) {
        $request['session']['cart-payment-success'] = 'no';
        unset($request['session']['cart-payment-success']);
        $cur_section_id = 'submit';
    } elseif( isset($_GET['submit']) ) {
        $cur_section_id = 'submit';
    }

    //
    // Currently only supports sectionedforms
    //
    $blocks[] = array(
        'sequence' => 1,
        'type' => ($form['guidelines'] != '' ? 'text' : 'title'),
        'title' => $form['name'],
        'content' => $form['guidelines'],
        'class' => 'form-intro',
        );
    if( count($error_blocks) > 0 ) {
        foreach($error_blocks as $block) {
            $blocks[] = $block;
        }
    }

    if( isset($problem_list) && $problem_list != '' ) {
        $problem_list = "You must complete all the required fields in the form. The following fields are missing:\n\n" . $problem_list;
    }
    $blocks[] = array(
        'type' => 'form',
        'section-selector' => 'yes',
        'form-id' => $form['id'],
        'termsofuse' => $form['termsofuse'],
        'fee-amount' => $form['fee_amount'],
        'form-sections' => $form['sections'],
        'problem-list' => isset($problem_list) ? $problem_list : '',
        'api-save-url' => $request['api_url'] . "/ciniki/forms/submissionSave",
        'api-image-url' => $request['api_url'] . "/ciniki/forms/submissionImage/" . $form['id'] . "/" . $form['submission_id'],
        'api-formcheck-url' => $request['api_url'] . "/ciniki/forms/submissionCheck",
        'api-cartsubmit-url' => $request['api_url'] . "/ciniki/forms/cartSubmit",
        'cur-section-id' => $cur_section_id,
        'api-args' => array(
            'form_id' => $form['id'],
            'submission_id' => $form['submission_id'],
            'object' => $form['object'],
            'object_id' => $form['object_id'],
            'customer_id' => $form['customer_id'],
            ),
        );

    return array('stat'=>'ok', 'blocks'=>$blocks);
}
?>
