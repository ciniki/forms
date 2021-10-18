<?php
//
// Description
// -----------
// This API endpoint calls validate and then removes the returned form to create 
// a short json response.
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_forms_wng_submissionCheck(&$ciniki, $tnid, $request) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'forms', 'wng', 'submissionValidate');
    $rc = ciniki_forms_wng_submissionValidate($ciniki, $tnid, $request);
    if( isset($rc['form']) ) {
        unset($rc['form']);
    }

    return $rc;
}
?>
