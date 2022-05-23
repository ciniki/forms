<?php
//
// Description
// -----------
// Return the list of available field refs for ciniki.forms module.
//
// The format of the refs id will be ciniki.forms.<formid>.<fieldid>
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_forms_hooks_formFieldRefs(&$ciniki, $tnid, $args) {
   
    //
    // Get the list of active forms and fields where the fields are not connected somewhere else
    //
    $strsql = "SELECT forms.id, "
        . "forms.name, "
        . "forms.permalink, "
        . "fields.id AS field_id, "
        . "fields.ftype, "
        . "fields.label "
        . "FROM ciniki_forms AS forms "
        . "INNER JOIN ciniki_form_sections AS sections ON ("
            . "forms.id = sections.form_id "
            . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "INNER JOIN ciniki_form_fields AS fields ON ("
            . "sections.id = fields.section_id "
//            . "AND fields.field_ref = '' "     // Not linked to another field
            . "AND fields.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE forms.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' " 
        . "AND forms.status = 50 "  // Active forms
        . "ORDER BY forms.name, sections.sequence, sections.label, fields.sequence, fields.label "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.forms', array(
        array('container'=>'fields', 'fname'=>'field_id', 
            'fields'=>array('id', 'name', 'permalink', 'field_id', 'ftype', 'label'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.181', 'msg'=>'Unable to load fields', 'err'=>$rc['err']));
    }
    $fields = isset($rc['fields']) ? $rc['fields'] : array();

    $module = 'Forms';
    $refs = array();
    foreach($fields as $field) {
        $refs["ciniki.forms.{$field['id']}.{$field['field_id']}"] = array(
            'module' => 'Forms',
            'type' => $field['ftype'],
            'name' => $field['name'] . ' - ' . $field['label'],
            );
    }

    return array('stat'=>'ok', 'refs'=>$refs);
}
?>
