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
function ciniki_forms_reporting_blockVotes(&$ciniki, $tnid, $args) {
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

/*    $date_text = '';
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
*/
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
    // Load the jurors
    //
    $strsql = "SELECT forms.id, "
        . "COUNT(jurors.id) AS num_jurors "
        . "FROM ciniki_forms AS forms "
        . "INNER JOIN ciniki_form_jurors AS jurors ON ("
            . "forms.id = jurors.form_id "
            . "AND jurors.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE forms.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND forms.status < 90 "
        . "AND (forms.flags&0x30) = 0x30 "      // Juried form and Jurying Open
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQueryList2');
    $rc = ciniki_core_dbQueryList2($ciniki, $strsql, 'ciniki.forms', 'jurors');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.171', 'msg'=>'Unable to load the list of ', 'err'=>$rc['err']));
    }
    $jurors = isset($rc['jurors']) ? $rc['jurors'] : array();

    //
    // Get the list of submissions for the last X days
    //
    $strsql = "SELECT forms.id, "
        . "forms.name, "
        . "IFNULL(customers.display_name, '') AS display_name, "
        . "votes.vote, "
        . "submissions.id AS submission_id, "
        . "IFNULL(submissions.dt_last_submitted, '') AS dt_last_submitted, "
        . "IFNULL(COUNT(votes.vote), 0) AS num_votes, "
        . "IFNULL(SUM(votes.vote), '-') AS rank "
        . "FROM ciniki_forms AS forms "
        . "LEFT JOIN ciniki_form_submissions AS submissions ON ("
            . "forms.id = submissions.form_id "
            . "AND submissions.status = 90 "
            . "AND submissions.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_customers AS customers ON ("
            . "submissions.customer_id = customers.id "
            . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_form_votes AS votes ON ("
            . "submissions.id = votes.submission_id "
            . "AND votes.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE forms.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND forms.status < 90 "
        . "AND (forms.flags&0x30) = 0x30 "      // Juried form and Jurying Open
        . "GROUP BY forms.name, submissions.id "
        . "ORDER BY forms.name, submissions.id "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.forms', array(
        array('container'=>'forms', 'fname'=>'id', 'fields'=>array('id', 'name')),
        array('container'=>'submissions', 'fname'=>'submission_id', 
            'fields'=>array('id'=>'submission_id', 'name', 'display_name', 
                'dt_last_submitted', 'num_votes', 'rank',
                ),
            'utctotz'=>array(
                'dt_last_submitted'=>array('timezone'=>$intl_timezone, 'format'=>$datetime_format),
                ),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.168', 'msg'=>'Unable to find new form submissions.', 'err'=>$rc['err']));
    }

    if( isset($rc['forms']) && count($rc['forms']) > 0 ) {
        $forms = $rc['forms'];
        //
        // Create the report blocks
        //
        foreach($forms as $form) {
            if( !isset($form['submissions']) ) {
                $chunk = array(
                    'type'=>'message',
                    'title' => $form['name'],
                    'content'=>'No votes',
                    );
            } else {
                $textlist = '';
                foreach($form['submissions'] as $sid => $s) {
                    $form['submissions'][$sid]['votes'] = $s['num_votes'] . ' of ' 
                        . (isset($jurors[$form['id']]) ? $jurors[$form['id']] : '?');
                    $textlist .= $s['display_name'] . ", ";
                    $textlist .= $s['dt_last_submitted'] . ",";
                    $textlist .= $s['rank'] . "";
                    $textlist .= $form['submissions'][$sid]['votes'] . "";
                    $textlist .= "\n";
                }
                $chunk = array(
                    'type'=>'table',
                    'title' => $form['name'],
                    'columns'=>array(
                        array('label'=>'Customer', 'pdfwidth'=>'40%', 'field'=>'display_name'),
                        array('label'=>'Submitted', 'pdfwidth'=>'30%', 'field'=>'dt_last_submitted'),
                        array('label'=>'Rank', 'pdfwidth'=>'15%', 'field'=>'rank'),
                        array('label'=>'Voted', 'pdfwidth'=>'15%', 'field'=>'votes'),
                        ),
                    'data'=>$form['submissions'],
        //            'editApp'=>array('app'=>'ciniki.forms.main', 'args'=>array('form_id'=>'d.id')),
                    'textlist'=>$textlist,
                    );
            }
            $chunks[] = $chunk;
        }
    } 
    //
    // No forms 
    //
    else {
        $chunks[] = array('type'=>'message', 'content'=>'No active forms with voting enabled.');
    }
    
    return array('stat'=>'ok', 'chunks'=>$chunks);
}
?>
