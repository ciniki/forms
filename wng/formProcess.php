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
    if( !isset($section['ref']) || !isset($section['settings']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.18', 'msg'=>"No forms specified"));
    }
    $s = $section['settings'];
    $blocks = array();
    $cur_section_id = '';

    //
    // Check for forms item request
    //
    if( isset($s['form-id']) && $s['form-id'] > 0 ) {
        $form_id = $s['form-id'];
    }
    elseif( isset($request['uri_split'][($request['cur_uri_pos']+1)])
        && $request['uri_split'][($request['cur_uri_pos']+1)] != '' 
        ) {
        $form_id = $request['uri_split'][($request['cur_uri_pos']+1)];
    } else {
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.forms.19', 'msg'=>"No forms specified"));
    }

    //
    // Load the form
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'forms', 'wng', 'formLoad');
    $rc = ciniki_forms_wng_formLoad($ciniki, $tnid, $request, $form_id);
    if( $rc['stat'] == 'noauth' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'wng', 'private', 'accountLoginProcess');
        $rc = ciniki_wng_accountLoginProcess($ciniki, $tnid, $request, array(
//            'create-account' => 'simple',
            'return-url' => $request['base_url'] . '/' . implode('/', $request['uri_split']),
            ));
        return $rc;
    }
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.forms.20', 'msg'=>'Form not found', 'err'=>$rc['err']));
    }
    $form = $rc['form'];

    //
    // Check if submissions made, load values
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'forms', 'wng', 'submissionLoad');
    $rc = ciniki_forms_wng_submissionLoad($ciniki, $tnid, $request, $form);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.50', 'msg'=>'Unable to load submission', 'err'=>$rc['err']));
    }

/* Javascript loadsaved 
    //
    // Apply the posted values or setup the default values if none posted
    //
    if( isset($_POST['action']) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'forms', 'wng', 'formPOSTApply');
        $rc = ciniki_forms_wng_formPOSTApply($ciniki, $tnid, $request, $form);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.70', 'msg'=>'Unable to update form', 'err'=>$rc['err']));
        }
    } 
    //
    // Apply the form defaults if no submission
    //
    else */
    if( !isset($form['submission_id']) || $form['submission_id'] == 0 ) {
        if( isset($form['sections']) ) {
            foreach($form['sections'] as $sid => $section) {
                if( isset($section['fields']) ) {
                    foreach($section['fields'] as $fid => $field) {
                        //
                        // Add the default
                        //
                        if( isset($field['default']) ) {
                            if( ($section['flags']&0x01) == 0x01 ) {
                                for($i = 1; $i < $section['max_repeats']; $i++ ) {
                                    $form['sections'][$sid]['fields'][$fid]['values'][$i] = $field['default'];
                                }
                            } else {
                                $form['sections'][$sid]['fields'][$fid]['value'] = $field['default'];
                            }
                        }
                    }
                }
            }
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
        $request['session']['cart-redirect-success'] = $request['base_url'] . '/' . implode('/', $request['uri_split']);
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
        $request['session']['cart-redirect-success'] = $request['base_url'] . '/' . implode('/', $request['uri_split']);
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
        $form['sections']['submit']['fields']['payment'] = array(
            'id' => 'payment',
            'ftype' => 'payment', 
            'label' => $form['fee_label'] != '' ? $form['fee_label'] : 'Fee',
            'amount' => $form['fee_amount'],
            'paid' => 'yes',
            'button-label' => $form['cartsubmit_label'] != '' ? $form['cartsubmit_label'] : 'Pay Now',
            );
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
    }

    //
    // Currently only supports sectionedforms
    //
    $blocks[] = array(
        'sequence' => 1,
        'type' => 'form',
        'title' => $form['name'],
        'section-selector' => 'yes',
        'form-id' => $form['id'],
//        'display_sectioned' => ($form['flags']&0x02) == 0 ? 'no' : 'yes',       // Default yes
        'guidelines' => $form['guidelines'],
        'termsofuse' => $form['termsofuse'],
        'fee-amount' => $form['fee_amount'],
        'form-sections' => $form['sections'],
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

//    $blocks[] = array(
//        'type' => 'content',
//        'content' => '<pre>' . print_r($request, true) . '</pre>',
//        );

    return array('stat'=>'ok', 'blocks'=>$blocks);
}
?>
