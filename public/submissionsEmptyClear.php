<?php
//
// Description
// -----------
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_forms_submissionsEmptyClear(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'form_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Form'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'forms', 'private', 'checkAccess');
    $rc = ciniki_forms_checkAccess($ciniki, $args['tnid'], 'ciniki.forms.submissionsEmptyClear');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }


    //
    // Get submissions that have no data
    //
    $strsql = "SELECT submissions.id, "
        . "submissions.uuid, "
        . "data.id AS data_id, "
        . "data.data "
        . "FROM ciniki_form_submissions AS submissions "
        . "LEFT JOIN ciniki_form_data AS data ON ("
            . "submissions.id = data.submission_id "
            . "AND data.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE submissions.form_id = '" . ciniki_core_dbQuote($ciniki, $args['form_id']) . "' "
        . "AND submissions.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.forms', array(
        array('container'=>'submissions', 'fname'=>'id', 'fields'=>array('id', 'uuid')),
        array('container'=>'data', 'fname'=>'data_id', 'fields'=>array('data_id', 'data')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.196', 'msg'=>'Unable to load submissions', 'err'=>$rc['err']));
    }
    $submissions = isset($rc['submissions']) ? $rc['submissions'] : array();

    //
    // Clear any submissions with no data
    //
    foreach($submissions AS $sub) {
        if( !isset($sub['data']) ) {
            $ciniki['request']['args']['submission_id'] = $sub['id'];
            ciniki_core_loadMethod($ciniki, 'ciniki', 'forms', 'public', 'submissionDelete');
            $rc = ciniki_forms_submissionDelete($ciniki);
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.197', 'msg'=>'Unable to delete submission', 'err'=>$rc['err']));
            }
        }
    }

    return array('stat'=>'ok');
}
?>
