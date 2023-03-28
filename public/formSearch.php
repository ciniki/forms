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
    $strsql = "SELECT forms.id, "
        . "forms.name, "
        . "forms.permalink, "
        . "forms.type, "
        . "forms.status, "
        . "forms.flags, "
        . "forms.fee_amount, "
        . "forms.max_submissions, "
        . "forms.dt_start, "
        . "forms.dt_end, "
        . "0 AS submission_id, "
        . "'' AS display_name "
        . "FROM ciniki_forms AS forms "
        . "WHERE forms.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND ("
            . "forms.name LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR forms.name LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
        . ") "
        . "ORDER BY forms.name "
        . "";
    if( isset($args['limit']) && is_numeric($args['limit']) && $args['limit'] > 0 ) {
        $strsql .= "LIMIT " . ciniki_core_dbQuote($ciniki, $args['limit']) . " ";
    } else {
        $strsql .= "LIMIT 25 ";
    }
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.forms', array(
        array('container'=>'forms', 'fname'=>'id', 
            'fields'=>array('id', 'name', 'permalink', 'type', 'status', 'flags', 'fee_amount', 
                'max_submissions', 'dt_start', 'dt_end', 'submission_id', 'display_name')),
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

    //
    // Get the list of forms
    //
    if( count($forms) == 0 ) {
        $strsql = "SELECT forms.id, "
            . "IFNULL(submissions.id, forms.id) AS row_id, "
            . "forms.name, "
            . "forms.permalink, "
            . "forms.type, "
            . "forms.status, "
            . "forms.flags, "
            . "forms.fee_amount, "
            . "forms.max_submissions, "
            . "forms.dt_start, "
            . "forms.dt_end, "
            . "IFNULL(submissions.id, 0) AS submission_id, "
            . "IFNULL(customers.display_name, '') AS display_name "
            . "FROM ciniki_forms AS forms "
            . "INNER JOIN ciniki_form_submissions AS submissions ON ("
                . "forms.id = submissions.form_id "
                . "AND submissions.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") " 
            . "INNER JOIN ciniki_customers AS customers ON ("
                . "submissions.customer_id = customers.id "
                . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") " 
            . "WHERE forms.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ("
                . "customers.display_name LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
                . "OR customers.display_name LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . ") "
            . "ORDER BY customers.display_name, forms.name "
            . "";
        if( isset($args['limit']) && is_numeric($args['limit']) && $args['limit'] > 0 ) {
            $strsql .= "LIMIT " . ciniki_core_dbQuote($ciniki, $args['limit']) . " ";
        } else {
            $strsql .= "LIMIT 25 ";
        }
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.forms', array(
            array('container'=>'forms', 'fname'=>'submission_id', 
                'fields'=>array('id', 'name', 'permalink', 'type', 'status', 'flags', 'fee_amount', 
                    'max_submissions', 'dt_start', 'dt_end', 'submission_id', 'display_name')),
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
    }

    return array('stat'=>'ok', 'forms'=>$forms, 'nplist'=>$form_ids);
}
?>
