<?php
//
// Description
// -----------
// This method will delete an submission.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:            The ID of the tenant the submission is attached to.
// submission_id:            The ID of the submission to be removed.
//
// Returns
// -------
//
function ciniki_forms_submissionDelete(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'submission_id'=>array('required'=>'yes', 'blank'=>'yes', 'name'=>'Submission'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'forms', 'private', 'checkAccess');
    $rc = ciniki_forms_checkAccess($ciniki, $args['tnid'], 'ciniki.forms.submissionDelete');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the current settings for the submission
    //
    $strsql = "SELECT id, uuid "
        . "FROM ciniki_form_submissions "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND id = '" . ciniki_core_dbQuote($ciniki, $args['submission_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.forms', 'submission');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['submission']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.133', 'msg'=>'Submission does not exist.'));
    }
    $submission = $rc['submission'];

    //
    // Get the data for the submission
    //
    $strsql = "SELECT id, uuid "
        . "FROM ciniki_form_data "
        . "WHERE submission_id = '" . ciniki_core_dbQuote($ciniki, $submission['id']) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.forms', 'submission');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $data = isset($rc['rows']) ? $rc['rows'] : array();

    //
    // Check for any dependencies before deleting
    //
    $strsql = "SELECT id, uuid "
        . "FROM ciniki_form_votes "
        . "WHERE submission_id = '" . ciniki_core_dbQuote($ciniki, $args['submission_id']) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.forms', array(
        array('container'=>'votes', 'fname'=>'id', 'fields'=>array('id', 'uuid')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.170', 'msg'=>'Unable to load votes', 'err'=>$rc['err']));
    }
    $votes = isset($rc['votes']) ? $rc['votes'] : array();

    //
    // Remove the link in mail
    //
    $strsql = "SELECT id, uuid "
        . "FROM ciniki_mail_objrefs "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND object = 'ciniki.forms.submission' "
        . "AND object_id = '" . ciniki_core_dbQuote($ciniki, $args['submission_id']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.forms', array(
        array('container'=>'objrefs', 'fname'=>'id', 'fields'=>array('id', 'uuid')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.194', 'msg'=>'Unable to load ', 'err'=>$rc['err']));
    }
    $objrefs = isset($rc['objrefs']) ? $rc['objrefs'] : array();

    //
    // Start transaction
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDelete');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.forms');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Remove the links to mail messages
    //
    if( count($objrefs) > 0 ) {
        foreach($objrefs as $or) {
            $rc = ciniki_core_objectDelete($ciniki, $args['tnid'], 'ciniki.mail.objref', $or['id'], $or['uuid'], 0x04);
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.194', 'msg'=>'Unable to remove mail reference', 'err'=>$rc['err']));
            }
        }
    }

    //
    // Check if any modules are currently using this object
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectCheckUsed');
    $rc = ciniki_core_objectCheckUsed($ciniki, $args['tnid'], 'ciniki.forms.submission', $args['submission_id']);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.forms');
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.134', 'msg'=>'Unable to check if the submission is still being used.', 'err'=>$rc['err']));
    }
    if( $rc['used'] != 'no' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.forms');
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.135', 'msg'=>'The submission is still in use. ' . $rc['msg']));
    }

    //
    // Remove the votes
    //
    foreach($votes as $vote) {
        $rc = ciniki_core_objectDelete($ciniki, $args['tnid'], 'ciniki.forms.vote', $vote['id'], $vote['uuid'], 0x04);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.forms');
            return $rc;
        }
    }

    //
    // Remove the data for the submission
    //
    foreach($data as $d) {
        $rc = ciniki_core_objectDelete($ciniki, $args['tnid'], 'ciniki.forms.data', $d['id'], $d['uuid'], 0x04);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.forms');
            return $rc;
        }
    }

    //
    // Remove the submission
    //
    $rc = ciniki_core_objectDelete($ciniki, $args['tnid'], 'ciniki.forms.submission',
        $args['submission_id'], $submission['uuid'], 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.forms');
        return $rc;
    }

    //
    // Commit the transaction
    //
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.forms');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Update the last_change date in the tenant modules
    // Ignore the result, as we don't want to stop user updates if this fails.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'updateModuleChangeDate');
    ciniki_tenants_updateModuleChangeDate($ciniki, $args['tnid'], 'ciniki', 'forms');

    return array('stat'=>'ok');
}
?>
