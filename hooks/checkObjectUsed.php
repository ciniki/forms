<?php
//
// Description
// -----------
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_forms_hooks_checkObjectUsed($ciniki, $tnid, $args) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbSingleCount');

    // Set the default to not used
    $used = 'no';
    $count = 0;
    $msg = '';

    //
    // Check if customer is used anywhere
    //
    if( $args['object'] == 'ciniki.customers.customer' ) {
        //
        // Check the jurors
        //
        $strsql = "SELECT 'items', COUNT(*) "
            . "FROM ciniki_form_jurors "
            . "WHERE customer_id = '" . ciniki_core_dbQuote($ciniki, $args['object_id']) . "' "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "";
        $rc = ciniki_core_dbCount($ciniki, $strsql, 'ciniki.forms', 'num');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['num']['items']) && $rc['num']['items'] > 0 ) {
            $used = 'yes';
            $count = $rc['num']['items'];
            $msg .= ($msg!=''?' ':'') . "There " . ($count==1?'is':'are') . " $count form" . ($count==1?'':'s') . " this customer is a juror on.";
        }
        //
        // Check the submissions
        //
        $strsql = "SELECT 'items', COUNT(*) "
            . "FROM ciniki_form_submissions "
            . "WHERE customer_id = '" . ciniki_core_dbQuote($ciniki, $args['object_id']) . "' "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "";
        $rc = ciniki_core_dbCount($ciniki, $strsql, 'ciniki.forms', 'num');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['num']['items']) && $rc['num']['items'] > 0 ) {
            $used = 'yes';
            $count = $rc['num']['items'];
            $msg .= ($msg!=''?' ':'') . "There " . ($count==1?'is':'are') . " $count form submission" . ($count==1?'':'s') . " this customer.";
        }
    }

    //
    // Check if image is used anywhere
    //
    if( $args['object'] == 'ciniki.images.image' ) {
        //
        // Check the form submissions for images
        //
        $count = 0;
        $strsql = "SELECT COUNT(*) AS items "
            . "FROM ciniki_form_data, ciniki_form_fields "
            . "WHERE data = '" . ciniki_core_dbQuote($ciniki, $args['object_id']) . "' "
            . "AND ciniki_form_data.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND ciniki_form_data.field_id = ciniki_form_fields.id "
            . "AND ciniki_form_fields.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "";
        $rc = ciniki_core_dbSingleCount($ciniki, $strsql, 'ciniki.forms', 'num');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( $rc['num'] > 0 ) {
            $count += $rc['num'];
        }

        //
        // Check if used
        //
        if( $count > 0 ) {
            $used = 'yes';
            $msg .= ($msg!=''?' ':'') . "There " . ($rc['num']==1?'is':'are') . " {$rc['num']} form submission" . ($rc['num']==1?'':'s') . " using this image.";
        }
    }

    return array('stat'=>'ok', 'used'=>$used, 'count'=>$count, 'msg'=>$msg);
}
?>
