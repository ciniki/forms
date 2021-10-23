<?php
//
// Description
// -----------
// This method will delete an form.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:            The ID of the tenant the form is attached to.
// form_id:            The ID of the form to be removed.
//
// Returns
// -------
//
function ciniki_forms_formDelete(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'form_id'=>array('required'=>'yes', 'blank'=>'yes', 'name'=>'Form'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'forms', 'private', 'checkAccess');
    $rc = ciniki_forms_checkAccess($ciniki, $args['tnid'], 'ciniki.forms.formDelete');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the current settings for the form
    //
    $strsql = "SELECT forms.id, "
        . "forms.uuid, "
        . "forms.name, "
        . "sections.id AS section_id, "
        . "sections.uuid AS section_uuid, "
        . "fields.id AS field_id, "
        . "fields.uuid AS field_uuid "
        . "FROM ciniki_forms AS forms "
        . "LEFT JOIN ciniki_form_sections AS sections ON ("
            . "forms.id = sections.form_id "
            . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "LEFT JOIN ciniki_form_fields AS fields ON ("
            . "sections.id = fields.section_id "
            . "AND fields.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE forms.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND forms.id = '" . ciniki_core_dbQuote($ciniki, $args['form_id']) . "' "
        . "ORDER BY sections.sequence, sections.label, fields.sequence, fields.label "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.forms', array(
        array('container'=>'forms', 'fname'=>'id', 'fields'=>array('id', 'uuid', 'name')),
        array('container'=>'sections', 'fname'=>'section_id', 'fields'=>array('id'=>'section_id', 'uuid'=>'section_uuid')),
        array('container'=>'fields', 'fname'=>'field_id', 'fields'=>array('id'=>'field_id', 'uuid'=>'field_uuid')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.5', 'msg'=>'Form not found', 'err'=>$rc['err']));
    }
    if( !isset($rc['forms'][0]) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.121', 'msg'=>'Unable to find Form'));
    }
    $form = $rc['forms'][0];

    //
    // Check for any dependencies before deleting
    //
    $strsql = "SELECT COUNT(id) AS num "
        . "FROM ciniki_form_submissions "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND form_id = '" . ciniki_core_dbQuote($ciniki, $args['form_id']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbSingleCount');
    $rc = ciniki_core_dbSingleCount($ciniki, $strsql, 'ciniki.forms', 'num');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.119', 'msg'=>'Unable to check for submissions', 'err'=>$rc['err']));
    }
    if( $rc['num'] > 0 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.120', 'msg'=>'There are submissions for this form'));
    }

    //
    // Check if any modules are currently using this object
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectCheckUsed');
    $rc = ciniki_core_objectCheckUsed($ciniki, $args['tnid'], 'ciniki.forms.form', $args['form_id']);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.6', 'msg'=>'Unable to check if the form is still being used.', 'err'=>$rc['err']));
    }
    if( $rc['used'] != 'no' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.7', 'msg'=>'The form is still in use. ' . $rc['msg']));
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
    // Remove the fields and sections
    //
    if( isset($form['sections']) ) {
        foreach($form['sections'] as $sid => $section) {
            if( isset($section['fields']) && isset($section['flags']) && ($section['flags']&0x01) == 0x01 ) {
                foreach($section['fields'] as $fid => $field) {
                    //
                    // Remove the field
                    //
                    $rc = ciniki_core_objectDelete($ciniki, $args['tnid'], 'ciniki.forms.field', $field['id'], $field['uuid'], 0x04);
                    if( $rc['stat'] != 'ok' ) {
                        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.forms');
                        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.122', 'msg'=>'Unable to remove field', 'err'=>$rc['err']));
                    }
                }
            }
            //
            // Remove the section
            //
            $rc = ciniki_core_objectDelete($ciniki, $args['tnid'], 'ciniki.forms.section', $section['id'], $section['uuid'], 0x04);
            if( $rc['stat'] != 'ok' ) {
                ciniki_core_dbTransactionRollback($ciniki, 'ciniki.forms');
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.123', 'msg'=>'Unable to remove section', 'err'=>$rc['err']));
            }
        }
    }
    
    //
    // Remove the form
    //
    $rc = ciniki_core_objectDelete($ciniki, $args['tnid'], 'ciniki.forms.form', $args['form_id'], $form['uuid'], 0x04);
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
