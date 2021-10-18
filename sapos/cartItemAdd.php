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
function ciniki_forms_sapos_cartItemAdd($ciniki, $tnid, $invoice_id, $args) {

    if( !isset($args['object']) || $args['object'] == '' 
        || !isset($args['object_id']) || $args['object_id'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.104', 'msg'=>'No event specified.'));
    }

    //
    // Lookup the requested submission
    //
    if( $args['object'] == 'ciniki.forms.submission' ) {
        $strsql = "SELECT invoice_id "
            . "FROM ciniki_form_submissions "
            . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['object_id']) . "' "
            . "AND ciniki_form_submissions.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.forms', 'submission');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.115', 'msg'=>'Unable to load submission', 'err'=>$rc['err']));
        }
        if( !isset($rc['submission']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.116', 'msg'=>'Unable to find requested submission'));
        }
        $submission = $rc['submission'];
        
        //
        // Update the submission to add the invoice id
        //
        if( $submission['invoice_id'] == 0 ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
            $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.forms.submission', $args['object_id'], array(
                'invoice_id' => $invoice_id,
                ), 0x04);
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.106', 'msg'=>'Unable to update the submission', 'err'=>$rc['err']));
            }
        }
      
        return array('stat'=>'ok');
    }

    return array('stat'=>'ok');
}
?>
