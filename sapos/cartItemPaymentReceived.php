<?php
//
// Description
// ===========
// This function completes the event registration when the customer has submitted a payment and checkout cart.
//
// Arguments
// =========
// 
// Returns
// =======
//
function ciniki_forms_sapos_cartItemPaymentReceived(&$ciniki, $tnid, $customer, $args) {

    if( !isset($args['object']) || $args['object'] == '' 
        || !isset($args['object_id']) || $args['object_id'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.47', 'msg'=>'No event specified.'));
    }

    if( !isset($args['invoice_id']) || $args['invoice_id'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.128', 'msg'=>'No event specified.'));
    }

    if( $args['object'] == 'ciniki.forms.submission' ) {
        //
        // Check if submissions made, load values
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'forms', 'private', 'submissionLoad');
        $rc = ciniki_forms_submissionLoad($ciniki, $tnid, $args['object_id']);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.108', 'msg'=>'Unable to load submission', 'err'=>$rc['err']));
        }
        $form = $rc['form'];

        $update_args = array();
        if( !isset($form['invoice_id']) || $form['invoice_id'] != $args['invoice_id'] ) {
            $update_args['invoice_id'] = $args['invoice_id'];
        }
        if( $form['submission']['status'] < 90 ) {
            $update_args['status'] = 90;
        }
        if( !isset($form['invoice_id']) || $form['invoice_id'] != $args['invoice_id'] ) {
            $update_args['invoice_id'] = $args['invoice_id'];
        }
        $dt_now = new DateTime('now', new DateTimezone('UTC'));
        $update_args['dt_last_submitted'] = $dt_now->format('Y-m-d H:i:s');

        if( count($update_args) > 0 ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
            $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.forms.submission', $args['object_id'], $update_args, 0x04);
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.105', 'msg'=>'Unable to update the submission', 'err'=>$rc['err']));
            }
        }

        //
        // Email the customer the submission receipt, and email any notification emails
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'forms', 'private', 'formSubmitEmail');
        $rc = ciniki_forms_formSubmitEmail($ciniki, $tnid, array(
            'form' => $form,
            'customer' => $customer,
            ));
        if( $rc['stat'] != 'ok' ) {
            error_log('Unable to email form submission: ');
        }

        if( isset($update_args['status']) ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'forms', 'private', 'formSubmitted');
            $rc = ciniki_forms_formSubmitted($ciniki, $tnid, ['form' => $form]);
            if( $rc['stat'] != 'ok' ) {
                error_log('Unable to update from form submission: ' . $form['submission_id']);
            }
        }
    }

    return array('stat'=>'ok');
}
?>
