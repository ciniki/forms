<?php
//
// Description
// -----------
// Check the for required fields and validate any input.
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_forms_wng_cartSubmit(&$ciniki, $tnid, $request) {
    
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');

    //
    // Make sure customer is logged in
    //
    if( !isset($request['session']['customer']['id']) || $request['session']['customer']['id'] <= 0 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.93', 'msg'=>'Not signed in'));
    }
    
    if( !isset($request['args']['customer_id']) || $request['args']['customer_id'] <= 0 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.94', 'msg'=>'Not signed in'));
    }
    if( $request['args']['customer_id'] != $request['args']['customer_id'] ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.95', 'msg'=>'Incorrect account'));
    }

    //
    // Make sure the form id is specified
    //
    if( !isset($request['args']['form_id']) || $request['args']['form_id'] <= 0 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.96', 'msg'=>'No form specified'));
    }

    //
    // Validate the form before submitting to cart
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'forms', 'wng', 'submissionValidate');
    $rc = ciniki_forms_wng_submissionValidate($ciniki, $tnid, $request);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['form']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.102', 'msg'=>'Unable to validate form'));
    }
    $form = $rc['form'];

    //
    // Check to make sure there is a fee
    //
    if( $form['fee_amount'] && $form['fee_amount'] > 0 ) {
        $amount = $form['fee_amount'];
    } else {
        $amount = 0;
    }

    //
    // Check if a cart exists
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'wng', 'cartLoad');
    $rc = ciniki_sapos_wng_cartLoad($ciniki, $tnid, $request);
    if( $rc['stat'] == 'noexist' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'wng', 'cartCreate');
        $rc = ciniki_sapos_wng_cartCreate($ciniki, $tnid, $request);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.99', 'msg'=>'Unable to create cart', 'err'=>$rc['err']));
        }
    }

    //
    // Add the item to the cart
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'wng', 'cartItemAdd');
    $rc = ciniki_sapos_wng_cartItemAdd($ciniki, $tnid, $request, array(
        'object' => 'ciniki.forms.submission',
        'object_id' => $form['submission_id'],
        'quantity' => 1,
        'flags' => 0x80,
        ));
    if( $rc['stat'] != 'ok' ) {
        if( $rc['stat'] == '404' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.210', 'msg'=>'Unable to add item'));
        } 
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.100', 'msg'=>'Unable to add item', 'err'=>$rc['err']));
    }

    return array('stat'=>'ok', 'redirect_url'=>$request['base_url'] . '/cart');
}
?>
