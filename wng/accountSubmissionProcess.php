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
function ciniki_forms_wng_accountSubmissionProcess(&$ciniki, $tnid, &$request, $item) {

    $blocks = array();

    if( !isset($item['ref']) ) {
        return array('stat'=>'ok', 'blocks'=>array(array(
            'type' => 'msg', 
            'level' => 'error',
            'content' => "Request error, please contact us for help.."
            )));
    }

    if( !isset($request['session']['customer']['id']) || $request['session']['customer']['id'] <= 0 ) {
        return array('stat'=>'ok', 'blocks'=>array(array(
            'type' => 'msg', 
            'level' => 'error',
            'content' => "You must be logged in to continue the submission."
            )));
    }

    if( !isset($request['uri_split'][2]) ) {
        return array('stat'=>'ok', 'blocks'=>array(array(
            'type' => 'msg', 
            'level' => 'error',
            'content' => "Invalid request, no form requested."
            )));
    }
    $form_permalink = $request['uri_split'][2];

    $base_url = '/' . join('/', $request['uri_split']);


    //
    // Create the fake section for the function formProcess to use
    //
    $section = array(
        'settings' => array(
            ),
        );
    $request['cur_uri_pos'] += 2;

    ciniki_core_loadMethod($ciniki, 'ciniki', 'forms', 'wng', 'formProcess');
    return ciniki_forms_wng_formProcess($ciniki, $tnid, $request, $section);
}
?>
