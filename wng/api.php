<?php
//
// Description
// -----------
// This function will process api requests for wng.
//
// Arguments
// ---------
// ciniki:
// tnid:     The ID of the tenant to get sapos request for.
//
// args:            The possible arguments for posts
//
//
// Returns
// -------
//
function ciniki_forms_wng_api(&$ciniki, $tnid, &$request) {

    //
    // Check to make sure the module is enabled
    //
    if( !isset($ciniki['tenant']['modules']['ciniki.forms']) ) {
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.forms.61', 'msg'=>"I'm sorry, the page you requested does not exist."));
    }

    //
    // Check to make sure logged in (also available to anonymous users of forms)
    //
//    if( !isset($request['session']['customer']['id']) || $request['session']['customer']['id'] < 1 ) {
//        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.forms.77', 'msg'=>"I'm sorry, the you are not authorized."));
//    }

    //
    // saveSubmission - Save the form submission
    //
    if( isset($request['uri_split'][$request['cur_uri_pos']]) 
        && $request['uri_split'][$request['cur_uri_pos']] == 'submissionSave' 
        ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'forms', 'wng', 'apiSubmissionSave');
        return ciniki_forms_wng_apiSubmissionSave($ciniki, $tnid, $request);
    }

    //
    // submissionImage - Return the submission image binary content
    //
    elseif( isset($request['uri_split'][$request['cur_uri_pos']]) 
        && $request['uri_split'][$request['cur_uri_pos']] == 'submissionImage' 
        ) {
        $request['cur_uri_pos']++;
        ciniki_core_loadMethod($ciniki, 'ciniki', 'forms', 'wng', 'submissionImage');
        return ciniki_forms_wng_submissionImage($ciniki, $tnid, $request);
    }

    //
    // submissionCheck - Check the submission validating all fields and ensuring all required are filled in
    //
    elseif( isset($request['uri_split'][$request['cur_uri_pos']]) 
        && $request['uri_split'][$request['cur_uri_pos']] == 'submissionCheck' 
        ) {
        $request['cur_uri_pos']++;
        ciniki_core_loadMethod($ciniki, 'ciniki', 'forms', 'wng', 'submissionCheck');
        return ciniki_forms_wng_submissionCheck($ciniki, $tnid, $request);
    }

    //
    // cartSubmit - Submit the form to the cart
    //
    elseif( isset($request['uri_split'][$request['cur_uri_pos']]) 
        && $request['uri_split'][$request['cur_uri_pos']] == 'cartSubmit' 
        ) {
        $request['cur_uri_pos']++;
        ciniki_core_loadMethod($ciniki, 'ciniki', 'forms', 'wng', 'submissionCheck');
        $rc = ciniki_forms_wng_submissionCheck($ciniki, $tnid, $request);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        ciniki_core_loadMethod($ciniki, 'ciniki', 'forms', 'wng', 'cartSubmit');
        return ciniki_forms_wng_cartSubmit($ciniki, $tnid, $request);
    }

    return array('stat'=>'ok');
}
?>
