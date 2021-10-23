<?php
//
// Description
// ===========
// This function will lookup an item that is being added to a shopping cart online.  This function
// has extra checks to make sure the requested item is available to the customer.
//
// Arguments
// =========
// 
// Returns
// =======
//
function ciniki_forms_sapos_cartItemLookup($ciniki, $tnid, $customer, $args) {

    if( !isset($args['object']) || $args['object'] == '' 
        || !isset($args['object_id']) || $args['object_id'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.131', 'msg'=>'No event specified.'));
    }

    //
    // Lookup the requested submission
    //
    if( $args['object'] == 'ciniki.forms.submission' ) {
        //
        // Check if submissions made, load values
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'forms', 'private', 'submissionLoad');
        $rc = ciniki_forms_submissionLoad($ciniki, $tnid, $args['object_id']);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.132', 'msg'=>'Unable to load submission', 'err'=>$rc['err']));
        }
        $form = $rc['form'];

        //
        // Validate submission
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'forms', 'private', 'submissionValidate');
        $rc = ciniki_forms_submissionValidate($ciniki, $tnid, $form);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.98', 'msg'=>'Unable to validate submission', 'err'=>$rc['err']));
        }

        //
        // Setup the item to be added to the invoice
        //
        $item = array(
            'code' => '',
            'description' => $form['name'],
            'quantity' => 1,
            'flags' => 0x08 | 0x80,
            'object' => 'ciniki.forms.submission',
            'object_id' => $args['object_id'],
            'price_id' => 0,
            'unit_amount' => $form['fee_amount'],
            'unit_discount_amount' => '',
            'unit_discount_percentage' => '',
            'taxtype_id' => 0,
            );
        
        return array('stat'=>'ok', 'item'=>$item);
    }

    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.103', 'msg'=>'No form specified.'));
}
?>
