<?php
//
// Description
// -----------
// This method will duplicate a current form section to a new copy
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
function ciniki_forms_sectionDup(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'section_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Section'),
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
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.234', 'msg'=>'Unable to load form', 'err'=>$rc['err']));
    }
    $form = $rc['form'];

    //
    // Add set the position to after current section
    //
    foreach($form['sections'] as $section) {
        if( $section['id'] == $args['section_id'] ) {
            break;
        }
    }
    if( isset($section['id']) ) {
        unset($section['id']);
    }
    if( isset($section['uuid']) ) {
        unset($section['uuid']);
    }
    $section['label'] .= ' Copy';
    $section['form_id'] = $args['form_id'];
    $section['sequence'] = $section['sequence'] + 1;

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
    // Add the section to the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    $rc = ciniki_core_objectAdd($ciniki, $args['tnid'], 'ciniki.forms.section', $section, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.forms');
        return $rc;
    }
    $section_id = $rc['id'];
    $section['id'] = $section_id;

    //
    // Update the sequences
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'sequencesUpdate');
    $rc = ciniki_core_sequencesUpdate($ciniki, $args['tnid'], 'ciniki.forms.section', 'form_id', $section['form_id'], 
        $section['sequence'], -1);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.251', 'msg'=>'Unable to update section', 'err'=>$rc['err']));
    }

    //
    // Add the sections and fields
    //
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
    ciniki_core_hookExec($ciniki, $args['tnid'], 'ciniki', 'web', 'indexObject', array('object'=>'ciniki.forms.section', 'object_id'=>$section_id));

    return array('stat'=>'ok', 'id'=>$section['id']);
}
?>
