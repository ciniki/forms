<?php
//
// Description
// -----------
// This method searchs for a Forms for a tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to get Form for.
// start_needle:       The search string to search for.
// limit:              The maximum number of entries to return.
//
// Returns
// -------
//
function ciniki_forms_formSearch($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'start_needle'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Search String'),
        'limit'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Limit'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'forms', 'private', 'checkAccess');
    $rc = ciniki_forms_checkAccess($ciniki, $args['tnid'], 'ciniki.forms.formSearch');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the list of forms
    //
    $strsql = "SELECT ciniki_forms.id, "
        . "ciniki_forms.name, "
        . "ciniki_forms.permalink, "
        . "ciniki_forms.type, "
        . "ciniki_forms.status, "
        . "ciniki_forms.flags, "
        . "ciniki_forms.fee_amount, "
        . "ciniki_forms.max_submissions, "
        . "ciniki_forms.dt_start, "
        . "ciniki_forms.dt_end "
        . "FROM ciniki_forms "
        . "WHERE ciniki_forms.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND ("
            . "name LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR name LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
        . ") "
        . "";
    if( isset($args['limit']) && is_numeric($args['limit']) && $args['limit'] > 0 ) {
        $strsql .= "LIMIT " . ciniki_core_dbQuote($ciniki, $args['limit']) . " ";
    } else {
        $strsql .= "LIMIT 25 ";
    }
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.forms', array(
        array('container'=>'forms', 'fname'=>'id', 
            'fields'=>array('id', 'name', 'permalink', 'type', 'status', 'flags', 'fee_amount', 'max_submissions', 'dt_start', 'dt_end')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['forms']) ) {
        $forms = $rc['forms'];
        $form_ids = array();
        foreach($forms as $iid => $form) {
            $form_ids[] = $form['id'];
        }
    } else {
        $forms = array();
        $form_ids = array();
    }

    return array('stat'=>'ok', 'forms'=>$forms, 'nplist'=>$form_ids);
}
?>
