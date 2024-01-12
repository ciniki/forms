<?php
//
// Description
// ===========
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_forms_fieldUpdate(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'field_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Form Field'),
        'section_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Section'),
        'ftype'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Type'),
        'flags'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Options'),
        'sequence'=>array('required'=>'no', 'blank'=>'yes', 'trim'=>'yes', 'name'=>'Order'),
        'prefill_ref'=>array('required'=>'no', 'blank'=>'yes', 'trim'=>'yes', 'name'=>'Prefill Ref'),
        'field_ref'=>array('required'=>'no', 'blank'=>'yes', 'trim'=>'yes', 'name'=>'Field Ref'),
        'field_size'=>array('required'=>'no', 'blank'=>'yes', 'trim'=>'yes', 'name'=>'Field Size'),
        'label'=>array('required'=>'no', 'blank'=>'yes', 'trim'=>'yes', 'name'=>'Label'),
        'description'=>array('required'=>'no', 'blank'=>'yes', 'trim'=>'yes', 'name'=>'Description'),
        'formula'=>array('required'=>'no', 'blank'=>'yes', 'trim'=>'yes', 'name'=>'Formula'),
        'options'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Options'),
        'fieldlist'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Field List'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'forms', 'private', 'checkAccess');
    $rc = ciniki_forms_checkAccess($ciniki, $args['tnid'], 'ciniki.forms.fieldUpdate');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
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
    // Get existing field info
    //
    $strsql = "SELECT ciniki_form_fields.id, "
        . "ciniki_form_fields.section_id, "
        . "ciniki_form_fields.ftype, "
        . "ciniki_form_fields.flags, "
        . "ciniki_form_fields.sequence, "
        . "ciniki_form_fields.prefill_ref, "
        . "ciniki_form_fields.field_ref, "
        . "ciniki_form_fields.field_size, "
        . "ciniki_form_fields.label, "
        . "ciniki_form_fields.description, "
        . "ciniki_form_fields.options "
        . "FROM ciniki_form_fields "
        . "WHERE ciniki_form_fields.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND ciniki_form_fields.id = '" . ciniki_core_dbQuote($ciniki, $args['field_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.forms', 'field');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.35', 'msg'=>'Unable to load field', 'err'=>$rc['err']));
    }
    if( !isset($rc['field']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.36', 'msg'=>'Unable to find requested field'));
    }
    $field = $rc['field'];
    $ftype = isset($args['ftype']) ? $args['ftype'] : $field['ftype'];

    //
    // Parse form options
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'forms', 'private', 'fieldOptionsParse');
    $rc = ciniki_forms_fieldOptionsParse($ciniki, $args['tnid'], $ftype, $ciniki['request']['args'], $field['options']);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.53', 'msg'=>'Unable to parse field options', 'err'=>$rc['err']));
    }
    if( $rc['options'] != $field['options'] ) {
        $args['options'] = $rc['options'];
    }

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
    // Update the Form Field in the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.forms.field', $args['field_id'], $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.forms');
        return $rc;
    }

    //
    // Update the sequences
    //
    if( isset($args['sequence']) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'sequencesUpdate');
        $rc = ciniki_core_sequencesUpdate($ciniki, $args['tnid'], 'ciniki.forms.field', 'section_id', $field['section_id'], 
            $args['sequence'], $field['sequence']);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.37', 'msg'=>'Unable to update field', 'err'=>$rc['err']));
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
    ciniki_core_hookExec($ciniki, $args['tnid'], 'ciniki', 'web', 'indexObject', array('object'=>'ciniki.forms.field', 'object_id'=>$args['field_id']));

    $rsp = array('stat'=>'ok');

    //
    // Check if field list should be returned
    //
    if( isset($args['fieldlist']) && $args['fieldlist'] == 'yes' ) {
        //
        // Get the list of field refs
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'forms', 'private', 'fieldRefsAvailable');
        $rc = ciniki_forms_fieldRefsAvailable($ciniki, $args['tnid']);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.101', 'msg'=>'Unable to load field references', 'err'=>$rc['err']));
        }
        $refs = $rc['refs'];

        //
        // Load the fields
        //
        $strsql = "SELECT ciniki_form_fields.id, "
            . "ciniki_form_fields.section_id, "
            . "ciniki_form_fields.ftype, "
            . "ciniki_form_fields.ftype AS type_text, "
            . "ciniki_form_fields.flags, "
            . "ciniki_form_fields.sequence, "
            . "ciniki_form_fields.prefill_ref, "
            . "ciniki_form_fields.field_ref, "
            . "ciniki_form_fields.field_size, "
            . "ciniki_form_fields.label "
            . "FROM ciniki_form_fields "
            . "WHERE ciniki_form_fields.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_form_fields.section_id = '" . ciniki_core_dbQuote($ciniki, $field['section_id']) . "' "
            . "ORDER BY sequence, label "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.forms', array(
            array('container'=>'fields', 'fname'=>'id', 
                'fields'=>array('id', 'section_id', 'ftype', 'type_text', 'flags', 'sequence', 'prefill_ref', 'field_ref', 'field_size', 'label'),
                'maps'=>array('type_text'=>$maps['field']['ftype']),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $rsp['fields'] = isset($rc['fields']) ? $rc['fields'] : array();
        $rsp['field_ids'] = array();
        foreach($rsp['fields'] as $iid => $field) {
            $rsp['fields'][$iid]['prefill_ref_text'] = '';
            if( isset($refs[$field['prefill_ref']]) ) {
                $rsp['fields'][$iid]['prefill_ref_text'] = $refs[$field['prefill_ref']]['module'] . ' - ' . $refs[$field['prefill_ref']]['name'];
            }
            $rsp['fields'][$iid]['field_ref_text'] = '';
            if( isset($refs[$field['field_ref']]) ) {
                $rsp['fields'][$iid]['field_ref_text'] = $refs[$field['field_ref']]['module'] . ' - ' . $refs[$field['field_ref']]['name'];
            }
            $rsp['field_ids'][] = $field['id'];
        }
    }

    return $rsp;
}
?>
