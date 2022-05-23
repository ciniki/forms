<?php
//
// Description
// -----------
// This method will return the list of Forms for a tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to get Form for.
//
// Returns
// -------
//
function ciniki_forms_hooks_formList($ciniki, $tnid, $args) {
    //
    // Get the list of forms
    //
    $strsql = "SELECT forms.id, "
        . "forms.name "
        . "FROM ciniki_forms AS forms "
        . "WHERE forms.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND forms.status = 50 "
        . "AND (forms.dt_end = '0000-00-00 00:00:00' OR forms.dt_end > NOW()) "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    return ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.forms', array(
        array('container'=>'forms', 'fname'=>'id', 'fields'=>array('id', 'name')),
        ));
}
?>
