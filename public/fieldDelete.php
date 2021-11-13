<?php
//
// Description
// -----------
// This method will delete an form field.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:            The ID of the tenant the form field is attached to.
// field_id:            The ID of the form field to be removed.
//
// Returns
// -------
//
function ciniki_forms_fieldDelete(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'field_id'=>array('required'=>'yes', 'blank'=>'yes', 'name'=>'Form Field'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'forms', 'private', 'checkAccess');
    $rc = ciniki_forms_checkAccess($ciniki, $args['tnid'], 'ciniki.forms.fieldDelete');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the current settings for the form field
    //
    $strsql = "SELECT id, uuid "
        . "FROM ciniki_form_fields "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND id = '" . ciniki_core_dbQuote($ciniki, $args['field_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.forms', 'field');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['field']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.39', 'msg'=>'Form Field does not exist.'));
    }
    $field = $rc['field'];

    //
    // Check for any dependencies before deleting
    //
    $strsql = "SELECT COUNT(id) AS num "
        . "FROM ciniki_form_data "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND field_id = '" . ciniki_core_dbQuote($ciniki, $args['field_id']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbSingleCount');
    $rc = ciniki_core_dbSingleCount($ciniki, $strsql, 'ciniki.forms', 'num');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.26', 'msg'=>'Unable to check for attached data', 'err'=>$rc['err']));
    }
    if( $rc['num'] > 0 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.27', 'msg'=>'There is still submission data for this field'));
    }

    //
    // Check if any modules are currently using this object
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectCheckUsed');
    $rc = ciniki_core_objectCheckUsed($ciniki, $args['tnid'], 'ciniki.forms.field', $args['field_id']);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.40', 'msg'=>'Unable to check if the form field is still being used.', 'err'=>$rc['err']));
    }
    if( $rc['used'] != 'no' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.41', 'msg'=>'The form field is still in use. ' . $rc['msg']));
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
    // Remove the field
    //
    $rc = ciniki_core_objectDelete($ciniki, $args['tnid'], 'ciniki.forms.field',
        $args['field_id'], $field['uuid'], 0x04);
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
