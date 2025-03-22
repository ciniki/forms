<?php
//
// Description
// -----------
// This function will process a form that has been submitted and will run any 
// customer updates or other updates into other modules.
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_forms_formSubmitted(&$ciniki, $tnid, $args) {

    if( !isset($args['form']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.213', 'msg'=>'No form specified'));
    }
    $form = $args['form'];

    if( ($form['flags']&0x0101) == 0x0101 ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'hooks', 'formCustomerUpdate');
        $rc = ciniki_customers_hooks_formCustomerUpdate($ciniki, $tnid, $args);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
    }

    return array('stat'=>'ok');
}
?>
