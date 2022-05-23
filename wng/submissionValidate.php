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
function ciniki_forms_wng_submissionValidate(&$ciniki, $tnid, $request, $form=null) {
    
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');

    //
    // Make sure customer is logged in
    //
    if( !isset($request['session']['customer']['id']) || $request['session']['customer']['id'] <= 0 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.85', 'msg'=>'Not signed in'));
    }
    
    if( !isset($request['args']['customer_id']) || $request['args']['customer_id'] <= 0 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.86', 'msg'=>'Not signed in'));
    }
    if( $request['args']['customer_id'] != $request['args']['customer_id'] ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.87', 'msg'=>'Incorrect account'));
    }

    //
    // Make sure the form id is specified
    //
    if( !isset($request['args']['form_id']) || $request['args']['form_id'] <= 0 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.88', 'msg'=>'No form specified'));
    }

    //
    // Load the form
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'forms', 'wng', 'formLoad');
    $rc = ciniki_forms_wng_formLoad($ciniki, $tnid, $request, $request['args']['form_id'], $request['session']['customer']['id']);
    if( $rc['stat'] == 'noauth' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.89', 'msg'=>'Not signed in'));
    }
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.90', 'msg'=>'Unable to load form', 'err'=>$rc['err']));
    }
    $form = $rc['form'];
        
    //
    // Load the existing submission
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'forms', 'wng', 'submissionLoad');
    $rc = ciniki_forms_wng_submissionLoad($ciniki, $tnid, $request, $form);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.91', 'msg'=>'Unable to lookup submission', 'err'=>$rc['err']));
    }
    if( !isset($form['submission_id']) || $form['submission_id'] == 0 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.92', 'msg'=>'No submission', 'err'=>$rc['err']));
    }

    //
    // Validate the submission
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'forms', 'private', 'submissionValidate');
    $rc = ciniki_forms_submissionValidate($ciniki, $tnid, $form);
    if( isset($rc['problems']) ) {
        return $rc;
    }
   
    $rsp = array('stat'=>'ok', 'form'=>$form);
    if( isset($api_args) ) {
        $rsp['api_args'] = $api_args;
    }
    
    return $rsp;
}
?>
