<?php
//
// Description
// -----------
// Return the report of new members
//
// Arguments
// ---------
// ciniki:
// tnid:         The ID of the tenant to get the birthdays for.
// args:                The options for the query.
//
// Additional Arguments
// --------------------
// days:                The number of days past to look for new members.
// 
// Returns
// -------
//
function ciniki_forms_reporting_blockNewSubmissions(&$ciniki, $tnid, $args) {
    //
    // Get the tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuoteIDs');

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

    $date_text = '';
    if( isset($args['months']) && $args['months'] != '' && $args['months'] > 0 && $args['months'] < 366 ) {
        $months = $args['months'];
        $date_text .= ($months > 1 ? $months . ' months' : 'month');
    } else {
        $months = 0;
    }
    if( isset($args['days']) && $args['days'] != '' && $args['days'] > 0 && $args['days'] < 366 ) {
        $days = $args['days'];
    } else {
        // Default to 0 when months specified, otherwise default to 7 days
        $days = ($months > 0 ? 0 : 7);
    }
    if( $days > 0 ) {
        $date_text .= $days . ' day' . ($days > 1 ? 's' : '');
    }

    $start_dt = new DateTime('now', new DateTimezone($intl_timezone));
    $end_dt = clone $start_dt;
    if( $days != 0 ) {
        $start_dt->sub(new DateInterval('P' . $days . 'D'));
    }
    if( $months != 0 ) {
        $start_dt->sub(new DateInterval('P' . $months . 'M'));
    }

    //
    // Store the report block chunks
    //
    $chunks = array();

    //
    // Check if type specified
    //
    $type_sql = '';
    if( isset($args['type']) && $args['type'] != '' && $args['type'] != '0' ) {
        $type_sql = "AND forms.type = '" . ciniki_core_dbQuote($ciniki, $args['type']) . "' ";
    }

    //
    // Get the list of submissions for the last X days
    //
    $strsql = "SELECT submissions.id, "
        . "forms.name, "
        . "submissions.form_id, "
        . "submissions.object, "
        . "submissions.object_id, "
        . "submissions.customer_id, "
        . "customers.display_name, "
        . "submissions.invoice_id, "
        . "submissions.status, "
        . "submissions.status AS status_text, "
        . "submissions.dt_terms_accepted, "
        . "submissions.dt_last_save, "
        . "submissions.dt_last_submitted AS dt_last_submitted_date, "
        . "submissions.dt_last_submitted AS dt_last_submitted_time "
        . "FROM ciniki_form_submissions AS submissions "
        . "INNER JOIN ciniki_forms AS forms ON ("
            . "submissions.form_id = forms.id "
            . $type_sql
            . "AND forms.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_customers AS customers ON ("
            . "submissions.customer_id = customers.id "
            . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE submissions.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND submissions.status = 90 "
        . "AND dt_last_submitted >= '" . ciniki_core_dbQuote($ciniki, $start_dt->format('Y-m-d')) . "' "
        . "AND dt_last_submitted <= '" . ciniki_core_dbQuote($ciniki, $end_dt->format('Y-m-d')) . "' "
        . "ORDER BY forms.name, dt_last_submitted DESC "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.forms', array(
        array('container'=>'submissions', 'fname'=>'id', 
            'fields'=>array('id', 'name', 'form_id', 'object', 'object_id', 'customer_id', 'display_name', 
                'invoice_id', 'status', 'status_text', 
                'dt_terms_accepted', 'dt_last_save', 'dt_last_submitted_date', 'dt_last_submitted_time',
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
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.168', 'msg'=>'Unable to find new form submissions.', 'err'=>$rc['err']));
    }

    if( isset($rc['submissions']) && count($rc['submissions']) > 0 ) {
        $submissions = $rc['submissions'];
        //
        // Create the report blocks
        //
        $chunk = array(
            'type'=>'table',
            'columns'=>array(
                array('label'=>'Form', 'pdfwidth'=>'40%', 'field'=>'name'),
                array('label'=>'Customer', 'pdfwidth'=>'30%', 'field'=>'display_name'),
                array('label'=>'Submission Date', 'pdfwidth'=>'30%', 'field'=>'dt_last_submitted_date'),
                ),
            'data'=>$submissions,
//            'editApp'=>array('app'=>'ciniki.forms.main', 'args'=>array('form_id'=>'d.id')),
            'textlist'=>'',
            );
        foreach($submissions as $sid => $submission) {
            //
            // Add emails to customer
            //
            $chunk['textlist'] .= $submission['name'] . ", ";
            $chunk['textlist'] .= $submission['display_name'] . ", ";
            $chunk['textlist'] .= $submission['dt_last_submitted_date'] . "\n";
            $chunk['textlist'] .= "\n";
        }
        $chunks[] = $chunk;
    } 
    //
    // No forms 
    //
    else {
        $chunks[] = array('type'=>'message', 'content'=>'No new submissions in the last ' . $date_text . '.');
    }
    
    return array('stat'=>'ok', 'chunks'=>$chunks);
}
?>
