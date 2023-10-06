<?php
//
// Description
// -----------
// This method will return the list of Submissions for a form.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to get Submission for.
//
// Returns
// -------
//
function ciniki_forms_submissionsExcel($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'form_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Form'),
        'status'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Submission Status'),
        'object'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Object'),
        'object_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Object ID'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'forms', 'private', 'checkAccess');
    $rc = ciniki_forms_checkAccess($ciniki, $args['tnid'], 'ciniki.forms.submissions');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load tenant details
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'tenantDetails');
    $rc = ciniki_tenants_tenantDetails($ciniki, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['details']) && is_array($rc['details']) ) {   
        $tenant_details = $rc['details'];
    } else {
        $tenant_details = array();
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
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'datetimeFormat');
    $datetime_format = ciniki_users_datetimeFormat($ciniki, 'php');
    
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
    // Setup filters
    //
    $status_sql = '';
    if( isset($args['status']) && $args['status'] != '' && $args['status'] != '0' ) {
        $status_sql = "AND submissions.status = '" . ciniki_core_dbQuote($ciniki, $args['status']) . "' ";
    }
    $object_sql = '';
    if( isset($args['object']) && $args['object'] != '' ) {
        if( !isset($args['object_id']) ) {
            $args['object_id'] = '';
        }
        $object_sql = "AND submissions.object = '" . ciniki_core_dbQuote($ciniki, $args['object']) . "' "
            . "AND submissions.object_id = '" . ciniki_core_dbQuote($ciniki, $args['object_id']) . "' "
            . "";
    }

    //
    // Load the form
    //
    $strsql = "SELECT ciniki_forms.id, "
        . "ciniki_forms.name, "
        . "ciniki_forms.type, "
        . "ciniki_forms.status, "
        . "ciniki_forms.status AS status_text, "
        . "ciniki_forms.flags, "
        . "ciniki_forms.max_submissions, "
        . "ciniki_forms.fee_amount, "
        . "ciniki_forms.dt_start, "
        . "ciniki_forms.dt_end "
        . "FROM ciniki_forms "
        . "WHERE ciniki_forms.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND ciniki_forms.id = '" . ciniki_core_dbQuote($ciniki, $args['form_id']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.forms', array(
        array('container'=>'forms', 'fname'=>'id', 
            'fields'=>array('name', 'type', 'status', 'status_text', 'flags', 
                'max_submissions', 'fee_amount', 
                'dt_start', 'dt_end', 
                ),
            'maps'=>array('status_text'=>$maps['form']['status']),
            'naprices'=>array('fee_amount'),
            'utctotz'=>array(
                'dt_start'=>array('format'=>$datetime_format, 'timezone'=>$intl_timezone),
                'dt_end'=>array('format'=>$datetime_format, 'timezone'=>$intl_timezone),
                ),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.138', 'msg'=>'Form not found', 'err'=>$rc['err']));
    }
    if( !isset($rc['forms'][0]) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.139', 'msg'=>'Unable to find Form'));
    }
    $form = $rc['forms'][0];

    //
    // Get the list of submissions
    //
    $strsql = "SELECT submissions.id, "
        . "submissions.form_id, "
        . "submissions.object, "
        . "submissions.object_id, "
        . "submissions.customer_id, "
        . "submissions.invoice_id, "
        . "submissions.status, "
        . "submissions.status AS status_text, "
        . "submissions.label, "
        . "submissions.dt_terms_accepted, "
        . "submissions.dt_last_save, "
        . "submissions.dt_last_submitted AS dt_last_submitted_date, "
        . "submissions.dt_last_submitted AS dt_last_submitted_time "
//        . "data.field_id, "
//        . "data.repeat_num, "
//        . "data.data "
        . "FROM ciniki_form_submissions AS submissions "
//        . "LEFT JOIN ciniki_form_data AS data ON ("
//            . "submissions.id = data.submission_id "
//            . "AND data.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
//            . ") "
        . "WHERE submissions.form_id = '" . ciniki_core_dbQuote($ciniki, $args['form_id']) . "' "
        . "AND submissions.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . $status_sql
        . $object_sql
        . "GROUP BY submissions.id "
        . "ORDER BY dt_last_submitted "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.forms', array(
        array('container'=>'submissions', 'fname'=>'id', 
            'fields'=>array('id', 'form_id', 'object', 'object_id', 'customer_id', 
                'invoice_id', 'status', 'status_text', 'label',
                'dt_terms_accepted', 'dt_last_save', 'dt_last_submitted_date', 'dt_last_submitted_time',
//                'num_votes', 'rank',
                ),
            'maps'=>array('status_text'=>$maps['submission']['status']),
            'utctotz'=>array(
                'dt_terms_accepted'=>array('timezone'=>$intl_timezone, 'format'=>$date_format),
                'dt_last_save'=>array('timezone'=>$intl_timezone, 'format'=>$date_format),
                'dt_last_submitted_date'=>array('timezone'=>$intl_timezone, 'format'=>$date_format),
                'dt_last_submitted_time'=>array('timezone'=>$intl_timezone, 'format'=>$time_format),
                ),
            ),
//        array('container'=>'data', 'fname'=>'field_id', 'fields'=>array()),
//        array('container'=>'repeats', 'fname'=>'repeat_num', 'fields'=>array('data')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $submissions = isset($rc['submissions']) ? $rc['submissions'] : array();

    $submission_ids = array_keys($submissions);

    //
    // Create the Excel
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'forms', 'templates', 'submissionsExcel');
    $rc = ciniki_forms_templates_submissionsExcel($ciniki, $args['tnid'], array(
        'title' => $form['name'] . ' - Submissions',
        'tenant_details' => $tenant_details, 
        'submission_ids' => $submission_ids,
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
   
    $filename = $form['name'] . ' - Submissions';
    if( isset($rc['excel']) ) {
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . $filename . '.xls"');
        header('Cache-Control: max-age=0');
        
        $objWriter = PHPExcel_IOFactory::createWriter($rc['excel'], 'Excel5');
        $objWriter->save('php://output');
    }

    return array('stat'=>'exit');
}
?>
