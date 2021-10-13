<?php
//
// Description
// -----------
// This method will return the list of Sections for a tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to get Section for.
//
// Returns
// -------
//
function ciniki_forms_sectionList($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'forms', 'private', 'checkAccess');
    $rc = ciniki_forms_checkAccess($ciniki, $args['tnid'], 'ciniki.forms.sectionList');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the list of sections
    //
    $strsql = "SELECT ciniki_form_sections.id, "
        . "ciniki_form_sections.form_id, "
        . "ciniki_form_sections.label, "
        . "ciniki_form_sections.flags, "
        . "ciniki_form_sections.sequence, "
        . "ciniki_form_sections.min_repeats, "
        . "ciniki_form_sections.max_repeats "
        . "FROM ciniki_form_sections "
        . "WHERE ciniki_form_sections.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.forms', array(
        array('container'=>'sections', 'fname'=>'id', 
            'fields'=>array('id', 'form_id', 'label', 'flags', 'sequence', 'min_repeats', 'max_repeats')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $sections = isset($rc['sections']) ? $rc['sections'] : array();
    $section_ids = array();
    foreach($sections as $iid => $section) {
        $section_ids[] = $section['id'];
    }

    return array('stat'=>'ok', 'sections'=>$sections, 'nplist'=>$section_ids);
}
?>
