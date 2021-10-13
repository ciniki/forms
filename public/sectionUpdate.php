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
function ciniki_forms_sectionUpdate(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'section_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Section'),
        'form_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Form'),
        'label'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Label'),
        'flags'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Options'),
        'sequence'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Order'),
        'repeat_prefix'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Repeat Label'),
        'min_repeats'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Min Repeats'),
        'max_repeats'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Max Repeats'),
        'description'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Description'),
        'sectionlist'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Section List'),
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
    $rc = ciniki_forms_checkAccess($ciniki, $args['tnid'], 'ciniki.forms.sectionUpdate');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load existing section
    //
    $strsql = "SELECT ciniki_form_sections.id, "
        . "ciniki_form_sections.form_id, "
        . "ciniki_form_sections.label, "
        . "ciniki_form_sections.flags, "
        . "ciniki_form_sections.sequence, "
        . "ciniki_form_sections.repeat_prefix, "
        . "ciniki_form_sections.min_repeats, "
        . "ciniki_form_sections.max_repeats, "
        . "ciniki_form_sections.description "
        . "FROM ciniki_form_sections "
        . "WHERE ciniki_form_sections.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND ciniki_form_sections.id = '" . ciniki_core_dbQuote($ciniki, $args['section_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.forms', 'section');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.58', 'msg'=>'Unable to load section', 'err'=>$rc['err']));
    }
    if( !isset($rc['section']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.59', 'msg'=>'Unable to find requested section'));
    }
    $section = $rc['section'];
    
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
    // Update the Section in the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.forms.section', $args['section_id'], $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.forms');
        return $rc;
    }

    //
    // Update the sequences
    //
    if( isset($args['sequence']) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'sequencesUpdate');
        $rc = ciniki_core_sequencesUpdate($ciniki, $args['tnid'], 'ciniki.forms.section', 'form_id', $section['form_id'], 
            $args['sequence'], $section['sequence']);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.60', 'msg'=>'Unable to update section', 'err'=>$rc['err']));
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
    ciniki_core_hookExec($ciniki, $args['tnid'], 'ciniki', 'web', 'indexObject', array('object'=>'ciniki.forms.section', 'object_id'=>$args['section_id']));

    $rsp = array('stat'=>'ok');

    //
    // Check if section list should be returned
    //
    if( isset($args['sectionlist']) && $args['sectionlist'] == 'yes' ) {
        //
        // Load the fields
        //
        $strsql = "SELECT ciniki_form_sections.id, "
            . "ciniki_form_sections.label, "
            . "ciniki_form_sections.flags, "
            . "ciniki_form_sections.sequence, "
            . "ciniki_form_sections.min_repeats, "
            . "ciniki_form_sections.max_repeats "
            . "FROM ciniki_form_sections "
            . "WHERE ciniki_form_sections.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_form_sections.form_id = '" . ciniki_core_dbQuote($ciniki, $section['form_id']) . "' "
            . "ORDER BY sequence, label "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.forms', array(
            array('container'=>'sections', 'fname'=>'id', 
                'fields'=>array('id', 'label', 'flags', 'sequence', 'min_repeats', 'max_repeats')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $rsp['sections'] = isset($rc['sections']) ? $rc['sections'] : array();
        $rsp['section_ids'] = array();
        foreach($rsp['sections'] as $iid => $section) {
            $rsp['section_ids'][] = $section['id'];
        }
    }

    return $rsp;
}
?>
