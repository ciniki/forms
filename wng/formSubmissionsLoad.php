<?php
//
// Description
// ===========
// This method will return all the information about an form.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the form is attached to.
// form_id:          The ID of the form to get the details for.
//
// Returns
// -------
//
function ciniki_forms_wng_formSubmissionsLoad(&$ciniki, $tnid, $request, &$form) {
   
    if( !isset($form['customer_id']) || $form['customer_id'] <= 0 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.176', 'msg'=>'No customer specified'));
    }

    //
    // Load maps
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'forms', 'private', 'maps');
    $rc = ciniki_forms_maps($ciniki);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $maps = $rc['maps'];

    //
    // Get the list of submissions for this form
    //
    $strsql = "SELECT submissions.id, "
        . "submissions.uuid, "
        . "submissions.status, "
        . "submissions.status AS status_text, "
        . "submissions.label "
        . "FROM ciniki_form_submissions AS submissions "
        . "WHERE submissions.form_id = '" . ciniki_core_dbQuote($ciniki, $form['id']) . "' "
        . "AND submissions.object = '" . ciniki_core_dbQuote($ciniki, $form['object']) . "' "
        . "AND submissions.object_id = '" . ciniki_core_dbQuote($ciniki, $form['object_id']) . "' "
        . "AND submissions.customer_id = '" . ciniki_core_dbQuote($ciniki, $form['customer_id']) . "' "
        . "AND submissions.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "ORDER BY status DESC, label "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.forms', array(
        array('container'=>'submissions', 'fname'=>'id', 
            'fields'=>array('id', 'uuid', 'status', 'status_text', 'label'),
            'maps'=>array('status_text'=>$maps['submission']['status']),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.177', 'msg'=>'Unable to load submissions', 'err'=>$rc['err']));
    }
    $submissions = isset($rc['submissions']) ? $rc['submissions'] : array();

    $form['submissions'] = $submissions;

    return array('stat'=>'ok', 'submissions'=>$submissions);
}
?>
