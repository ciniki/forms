<?php
//
// Description
// -----------
// This function will return the blocks for the website.
//
// Arguments
// ---------
// ciniki:
// tnid:            The ID of the tenant.
// args:            The possible arguments for.
//
//
// Returns
// -------
//
function ciniki_forms_wng_process(&$ciniki, $tnid, &$request, $section) {

    //
    // Check to make sure the module is enabled
    //
    if( !isset($ciniki['tenant']['modules']['ciniki.forms']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.32', 'msg'=>"I'm sorry, the section you requested does not exist."));
    }

    //
    // Check to make sure the report is specified
    //
    if( !isset($section['ref']) || !isset($section['settings']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.33', 'msg'=>"No section specified."));
    }

    if( $section['ref'] == 'ciniki.forms.form' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'forms', 'wng', 'formProcess');
        return ciniki_forms_wng_formProcess($ciniki, $tnid, $request, $section);
    }

    return array('stat'=>'ok');
}
?>
