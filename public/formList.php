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
function ciniki_forms_formList($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'type'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Type'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'forms', 'private', 'checkAccess');
    $rc = ciniki_forms_checkAccess($ciniki, $args['tnid'], 'ciniki.forms.formList');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load the tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];

    //
    // Load the date format strings for the user
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'timeFormat');
    $time_format = ciniki_users_timeFormat($ciniki, 'php');
    
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
    // Get the list of forms
    //
    $strsql = "SELECT ciniki_forms.id, "
        . "ciniki_forms.name, "
        . "ciniki_forms.permalink, "
        . "ciniki_forms.type, "
        . "ciniki_forms.status, "
        . "ciniki_forms.status AS status_text, "
        . "ciniki_forms.flags, "
        . "ciniki_forms.fee_amount, "
        . "ciniki_forms.max_submissions, "
        . "ciniki_forms.dt_start AS dt_start_date, "
        . "ciniki_forms.dt_start AS dt_start_time, "
        . "ciniki_forms.dt_end AS dt_end_date, "
        . "ciniki_forms.dt_end AS dt_end_time "
        . "FROM ciniki_forms "
        . "WHERE ciniki_forms.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    if( isset($args['type']) && $args['type'] != '' && $args['type'] != 'all' ) {
        $strsql .= "AND ciniki_forms.type = '" . ciniki_core_dbQuote($ciniki, $args['type']) . "' ";
    }
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.forms', array(
        array('container'=>'forms', 'fname'=>'id', 
            'fields'=>array('id', 'name', 'permalink', 'type', 'status', 'status_text', 'flags', 'fee_amount', 
                'max_submissions', 'dt_start_date', 'dt_start_time', 'dt_end_date', 'dt_end_time',
                ),
            'maps'=>array('status_text'=>$maps['form']['status']),
            'utctotz'=>array(
                'dt_start_date'=>array('timezone'=>$intl_timezone, 'format'=>$date_format),
                'dt_start_time'=>array('timezone'=>$intl_timezone, 'format'=>$time_format),
                'dt_end_date'=>array('timezone'=>$intl_timezone, 'format'=>$date_format),
                'dt_end_time'=>array('timezone'=>$intl_timezone, 'format'=>$time_format),
            )),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $forms = isset($rc['forms']) ? $rc['forms'] : array();
    return array('stat'=>'ok', 'forms'=>$forms);
}
?>
