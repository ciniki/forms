<?php
//
// Description
// -----------
// This function will check for an existing cart to load into the session
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_forms_wng_accountRequestProcess(&$ciniki, $tnid, &$request, $item) {

    if( !isset($item['ref']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.152', 'msg'=>'No reference specified'));
    }

    if( !isset($request['session']['customer']['id']) || $request['session']['customer']['id'] <= 0 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.153', 'msg'=>'Must be logged in'));
    }

    if( $item['ref'] == 'ciniki.forms.juror' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'forms', 'wng', 'accountJurorProcess');
        return ciniki_forms_wng_accountJurorProcess($ciniki, $tnid, $request, $item);
    } elseif( $item['ref'] == 'ciniki.forms.submission' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'forms', 'wng', 'accountSubmissionProcess');
        return ciniki_forms_wng_accountSubmissionProcess($ciniki, $tnid, $request, $item);
    }
    

    return array('stat'=>'404', 'err'=>array('code'=>'ciniki.forms.154', 'msg'=>'Account page not found'));
}
?>
