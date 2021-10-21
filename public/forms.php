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
function ciniki_forms_forms($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'type'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Type'),
        'status'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Status'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'forms', 'private', 'checkAccess');
    $rc = ciniki_forms_checkAccess($ciniki, $args['tnid'], 'ciniki.forms.forms');
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
        . "ciniki_forms.dt_end AS dt_end_time, "
        . "COUNT(submissions.id) AS num_submissions, "
        . "COUNT(draftsubs.id) AS num_draftsubs "
        . "FROM ciniki_forms "
        . "LEFT JOIN ciniki_form_submissions AS submissions ON ("
            . "ciniki_forms.id = submissions.form_id "
            . "AND submissions.status = 90 "
            . "AND submissions.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "LEFT JOIN ciniki_form_submissions AS draftsubs ON ("
            . "ciniki_forms.id = draftsubs.form_id "
            . "AND draftsubs.status < 90 "
            . "AND draftsubs.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE ciniki_forms.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    if( isset($args['status']) && $args['status'] != '' && strtolower($args['status']) != '0' ) {
        $strsql .= "AND ciniki_forms.status = '" . ciniki_core_dbQuote($ciniki, $args['status']) . "' ";
    }
    if( isset($args['type']) && $args['type'] != '' && strtolower($args['type']) != 'all' ) {
        $strsql .= "AND ciniki_forms.type = '" . ciniki_core_dbQuote($ciniki, $args['type']) . "' ";
    }
    $strsql .= "GROUP BY ciniki_forms.id "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.forms', array(
        array('container'=>'forms', 'fname'=>'id', 
            'fields'=>array('id', 'name', 'permalink', 'type', 'status', 'status_text', 'flags', 'fee_amount', 
                'max_submissions', 'dt_start_date', 'dt_start_time', 'dt_end_date', 'dt_end_time',
                'num_submissions', 'num_draftsubs',
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
    $rsp = array('stat'=>'ok', 'forms'=>$forms);
    
    //
    // Get the status counts
    //
    $rsp['statuses'] = array(
        '0' => array('label' => 'All', 'status'=>0, 'num_forms'=>0),
        '10' => array('label' => 'Draft', 'status'=>10, 'num_forms'=>0),
        '50' => array('label' => 'Active', 'status'=>50, 'num_forms'=>0),
        '90' => array('label' => 'Archived', 'status'=>90, 'num_forms'=>0),
        );
    $strsql = "SELECT status, COUNT(*) AS num_forms "
        . "FROM ciniki_forms "
        . "WHERE ciniki_forms.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "GROUP BY status "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbCount');
    $rc = ciniki_core_dbCount($ciniki, $strsql, 'ciniki.forms', 'num');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.14', 'msg'=>'Unable to load get the number of items', 'err'=>$rc['err']));
    }
    if( isset($rc['num']) ) {
        $count = 0;
        foreach($rc['num'] as $status => $num) {
            $count += $num;
            $rsp['statuses'][$status]['num_forms'] = $num;
        }
        $rsp['statuses'][0]['num_forms'] = $count;
    }

    //
    // Get the list of types
    //
    $strsql = "SELECT type, COUNT(*) AS num_forms "
        . "FROM ciniki_forms "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' ";
    if( isset($args['status']) && $args['status'] != '' && strtolower($args['status']) != '0' ) {
        $strsql .= "AND status = '" . ciniki_core_dbQuote($ciniki, $args['status']) . "' ";
    }
        $strsql .= "GROUP BY type "
        . "ORDER BY type "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.forms', array(
        array('container'=>'types', 'fname'=>'type', 'fields'=>array('type', 'num_forms')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.11', 'msg'=>'Unable to load types', 'err'=>$rc['err']));
    }
    $types = isset($rc['types']) ? $rc['types'] : array();
    $rsp['types'] = array();
    $count = 0;
    foreach($types as $type) {
        $rsp['types'][] = array('label'=>$type['type'], 'num_forms'=>$type['num_forms']);
        $count+= $type['num_forms'];
    }
    array_unshift($rsp['types'], array('label'=>'All', 'num_forms'=>$count));



    return $rsp;
}
?>
