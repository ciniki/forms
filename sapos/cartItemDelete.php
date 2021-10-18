<?php
//
// Description
// ===========
// This function 
//
// Arguments
// =========
// 
// Returns
// =======
//
function ciniki_forms_sapos_cartItemDelete($ciniki, $tnid, $invoice_id, $args) {

    if( !isset($args['object']) || $args['object'] == '' 
        || !isset($args['object_id']) || $args['object_id'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.107', 'msg'=>'No registration specified', 'err'=>$rc['err']));
    }

    //
    // Check to make sure the registration exists
    //
    if( $args['object'] == 'ciniki.forms.submission' ) {
        //
        // Get the current details for the registration
        //
        $strsql = "SELECT invoice_id "
            . "FROM ciniki_form_submissions "
            . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['object_id']) . "' "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.forms', 'submission');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.112', 'msg'=>'Unable to find form', 'err'=>$rc['err']));
        }
        if( !isset($rc['submission']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.113', 'msg'=>'Unable to find form'));
        }
        $submission = $rc['submission'];

        if( $submission['invoice_id'] == $invoice_id ) {
            //
            // Remove the invoice_id from the submission
            //
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
            $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.forms.submission', $args['object_id'], array('invoice_id'=>0));
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.114', 'msg'=>'Error trying to remove fee.', 'err'=>$rc['err']));
            }
        }

        return array('stat'=>'ok');
    }

    return array('stat'=>'ok');
}
?>
