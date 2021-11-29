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
function ciniki_forms_submissions($ciniki) {
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
    // Load the jurors
    //
    $strsql = "SELECT jurors.id, "
        . "jurors.customer_id, "
        . "customers.display_name "
        . "FROM ciniki_form_jurors AS jurors "
        . "LEFT JOIN ciniki_customers AS customers ON ("
            . "jurors.customer_id = customers.id "
            . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE jurors.form_id = '" . ciniki_core_dbQuote($ciniki, $args['form_id']) . "' "
        . "AND jurors.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.forms', array(
        array('container'=>'jurors', 'fname'=>'id', 
            'fields'=>array('id', 'customer_id', 'display_name')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.150', 'msg'=>'Unable to load jurors', 'err'=>$rc['err']));
    }
    $form['jurors'] = isset($rc['jurors']) ? $rc['jurors'] : array();
    $form['num_jurors'] = count($form['jurors']);

    //
    // Get the list of submissions
    //
    $strsql = "SELECT submissions.id, "
        . "submissions.form_id, "
        . "submissions.object, "
        . "submissions.object_id, "
        . "submissions.customer_id, "
        . "customers.display_name, "
        . "submissions.invoice_id, "
        . "submissions.status, "
        . "submissions.status AS status_text, "
        . "submissions.label, "
        . "submissions.dt_terms_accepted, "
        . "submissions.dt_last_save, "
        . "submissions.dt_last_submitted AS dt_last_submitted_date, "
        . "submissions.dt_last_submitted AS dt_last_submitted_time, "
        . "IFNULL(COUNT(votes.vote), 0) AS num_votes, "
        . "IFNULL(SUM(votes.vote), '-') AS rank "
        . "FROM ciniki_form_submissions AS submissions "
        . "LEFT JOIN ciniki_customers AS customers ON ("
            . "submissions.customer_id = customers.id "
            . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "LEFT JOIN ciniki_form_votes AS votes ON ("
            . "submissions.id = votes.submission_id "
            . "AND votes.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE submissions.form_id = '" . ciniki_core_dbQuote($ciniki, $args['form_id']) . "' "
        . "AND submissions.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . $status_sql
        . $object_sql
        . "GROUP BY submissions.id "
        . "ORDER BY dt_last_submitted DESC "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.forms', array(
        array('container'=>'submissions', 'fname'=>'id', 
            'fields'=>array('id', 'form_id', 'object', 'object_id', 'customer_id', 'display_name', 
                'invoice_id', 'status', 'status_text', 'label',
                'dt_terms_accepted', 'dt_last_save', 'dt_last_submitted_date', 'dt_last_submitted_time',
                'num_votes', 'rank',
                ),
            'maps'=>array('status_text'=>$maps['submission']['status']),
            'utctotz'=>array(
                'dt_terms_accepted'=>array('timezone'=>$intl_timezone, 'format'=>$date_format),
                'dt_last_save'=>array('timezone'=>$intl_timezone, 'format'=>$date_format),
                'dt_last_submitted_date'=>array('timezone'=>$intl_timezone, 'format'=>$date_format),
                'dt_last_submitted_time'=>array('timezone'=>$intl_timezone, 'format'=>$time_format),
                ),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $submissions = isset($rc['submissions']) ? $rc['submissions'] : array();
    $submission_ids = array();
    foreach($submissions as $iid => $submission) {
        $submission_ids[] = $submission['id'];
    }
    $rsp = array('stat'=>'ok', 'form'=>$form, 'submissions'=>$submissions, 'nplist'=>$submission_ids);

    //
    // Get the submission stats
    //
    $strsql = "SELECT submissions.status, COUNT(*) AS num_submissions "
        . "FROM ciniki_form_submissions AS submissions "
        . "WHERE submissions.form_id = '" . ciniki_core_dbQuote($ciniki, $args['form_id']) . "' "
        . "AND submissions.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . $object_sql
        . "GROUP BY submissions.status "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbCount');
    $rc = ciniki_core_dbCount($ciniki, $strsql, 'ciniki.forms', 'statuses');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.141', 'msg'=>'Unable to load get the number of items', 'err'=>$rc['err']));
    }
    $statuses = $rc['statuses'];
    $total = 0;
    foreach($statuses as $s) {
        $total+=$s;
    }
    $rsp['statuses'] = array(
        '_0' => array('id'=>0, 'label'=>'All', 'num_submissions'=>$total),
        '_90' => array('id'=>90, 'label'=>'Submitted', 'num_submissions'=>(isset($statuses[90]) ? $statuses[90] : 0)),
        '_80' => array('id'=>80, 'label'=>'Paid', 'num_submissions'=>(isset($statuses[80]) ? $statuses[80] : 0)),
        '_10' => array('id'=>10, 'label'=>'In Progress', 'num_submissions'=>(isset($statuses[10]) ? $statuses[10] : 0)),
        );

    //
    // FIXME: Get any objects the form is linked to
    //

    //
    // FIXME: Get object names, hooks/formSubmissionObjectNames
    // This will hook into other module (programs) and get the text that should be displayed for 
    // each object/object_id combination that is contained in the remote module
    //

    //
    // Setup the form details
    //
    $rsp['form_details'] = array(
        array('label'=>'Name', 'value'=>$form['name']),
        array('label'=>'Status', 'value'=>$form['status_text']),
        );
    if( $form['dt_start'] != '' ) {
        $rsp['form_details'][] = array('label'=>'Start', 'value'=>$form['dt_start']);
    }
    if( $form['dt_end'] != '' ) {
        $rsp['form_details'][] = array('label'=>'End', 'value'=>$form['dt_end']);
    }

    return $rsp;
}
?>
