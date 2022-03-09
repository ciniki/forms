<?php
//
// Description
// -----------
// This method will delete an juror.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:            The ID of the tenant the juror is attached to.
// juror_id:            The ID of the juror to be removed.
//
// Returns
// -------
//
function ciniki_forms_jurorDelete(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'juror_id'=>array('required'=>'yes', 'blank'=>'yes', 'name'=>'Juror'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'forms', 'private', 'checkAccess');
    $rc = ciniki_forms_checkAccess($ciniki, $args['tnid'], 'ciniki.forms.jurorDelete');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the current settings for the juror
    //
    $strsql = "SELECT id, uuid, form_id, customer_id "
        . "FROM ciniki_form_jurors "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND id = '" . ciniki_core_dbQuote($ciniki, $args['juror_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.forms', 'juror');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['juror']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.143', 'msg'=>'Juror does not exist.'));
    }
    $juror = $rc['juror'];

    //
    // Get the list of votes for this juror
    //
    $strsql = "SELECT id, uuid, vote "
        . "FROM ciniki_form_votes "
        . "WHERE ciniki_form_votes.juror_id = '" . ciniki_core_dbQuote($ciniki, $juror['id']) . "' "
        . "AND ciniki_form_votes.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.forms', 'vote');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.137', 'msg'=>'Unable to load vote', 'err'=>$rc['err']));
    }
    $votes = isset($rc['rows']) ? $rc['rows'] : array();

    //
    // Check for any dependencies before deleting
    //

    //
    // Check if any modules are currently using this object
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectCheckUsed');
    $rc = ciniki_core_objectCheckUsed($ciniki, $args['tnid'], 'ciniki.forms.juror', $args['juror_id']);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.144', 'msg'=>'Unable to check if the juror is still being used.', 'err'=>$rc['err']));
    }
    if( $rc['used'] != 'no' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.145', 'msg'=>'The juror is still in use. ' . $rc['msg']));
    }

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
    // Remove all votes
    //
    foreach($votes as $vote) {
        $rc = ciniki_core_objectDelete($ciniki, $args['tnid'], 'ciniki.forms.vote',
            $vote['id'], $vote['uuid'], 0x04);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.forms');
            return $rc;
        }
    }

    //
    // Remove the juror
    //
    $rc = ciniki_core_objectDelete($ciniki, $args['tnid'], 'ciniki.forms.juror',
        $args['juror_id'], $juror['uuid'], 0x04);
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
