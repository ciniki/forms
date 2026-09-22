<?php
//
// Description
// -----------
// This method will duplicate a current form to a new copy
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to add the Form to.
//
// Returns
// -------
//
function ciniki_forms_formDup(&$ciniki) {
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
    // Check access to tnid as owner
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'forms', 'private', 'checkAccess');
    $rc = ciniki_forms_checkAccess($ciniki, $args['tnid'], 'ciniki.forms.formDup');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load current form
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'forms', 'private', 'formLoad');
    $rc = ciniki_forms_formLoad($ciniki, $args['tnid'], $args['form_id']);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.118', 'msg'=>'Unable to load form', 'err'=>$rc['err']));
    }
    $form = $rc['form'];

    //
    // Add Copy to name
    //
    $form['name'] .= " Copy";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'makePermalink');
    $form['permalink'] = ciniki_core_makePermalink($ciniki, $form['name']);

    //
    // Reset status
    //
    unset($form['id']);
    $form['status'] = 10;

    //
    // Start transaction
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.forms');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Add the form to the database
    //
    unset($form['id']);
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    $rc = ciniki_core_objectAdd($ciniki, $args['tnid'], 'ciniki.forms.form', $form, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.forms');
        return $rc;
    }
    $form_id = $rc['id'];
    $form['id'] = $form_id;

    //
    // Add the sections and fields
    //
    if( isset($form['sections']) ) {
        foreach($form['sections'] as $section) {
            unset($section['id']);
            unset($section['uuid']);
            $section['form_id'] = $form['id'];
            $rc = ciniki_core_objectAdd($ciniki, $args['tnid'], 'ciniki.forms.section', $section, 0x04);
            if( $rc['stat'] != 'ok' ) {
                ciniki_core_dbTransactionRollback($ciniki, 'ciniki.forms');
                return $rc;
            }
            $section['id'] = $rc['id'];

            if( isset($section['fields']) ) {
                foreach($section['fields'] as $field) {
                    unset($field['id']);
                    unset($field['uuid']);
                    $field['section_id'] = $section['id'];
                    $rc = ciniki_core_objectAdd($ciniki, $args['tnid'], 'ciniki.forms.field', $field, 0x04);
                    if( $rc['stat'] != 'ok' ) {
                        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.forms');
                        return $rc;
                    }
                }
            }
        }
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

    //
    // Update the web index if enabled
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'hookExec');
    ciniki_core_hookExec($ciniki, $args['tnid'], 'ciniki', 'web', 'indexObject', array('object'=>'ciniki.forms.form', 'object_id'=>$form_id));

    return array('stat'=>'ok', 'id'=>$form['id']);
}
?>
