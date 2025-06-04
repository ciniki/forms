<?php
//
// Description
// -----------
// This method will return the list of actions that were applied to an element of an form.
// This method is typically used by the UI to display a list of changes that have occured
// on an element through time. This information can be used to revert elements to a previous value.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant to get the details for.
// form_id:          The ID of the form to get the history for.
// field:                   The field to get the history for.
//
// Returns
// -------
//
function ciniki_forms_formHistory($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'form_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Form'),
        'field'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'field'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner, or sys admin
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'forms', 'private', 'checkAccess');
    $rc = ciniki_forms_checkAccess($ciniki, $args['tnid'], 'ciniki.forms.formHistory');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    if( in_array($args['field'], ['dt_start', 'dt_end']) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbGetModuleHistoryReformat');
        return ciniki_core_dbGetModuleHistoryReformat($ciniki, 'ciniki.forms', 'ciniki_forms_history', $args['tnid'], 'ciniki_forms', $args['form_id'], $args['field'], 'utcdatetime');
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbGetModuleHistory');
    return ciniki_core_dbGetModuleHistory($ciniki, 'ciniki.forms', 'ciniki_forms_history', $args['tnid'], 'ciniki_forms', $args['form_id'], $args['field']);
}
?>
