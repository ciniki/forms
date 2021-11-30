<?php
//
// Description
// -----------
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_forms_submissionVotesLoad(&$ciniki, $tnid, $form_id, $submission_id) {

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
    // Load the submission for the form, object, and customer
    //
    $strsql = "SELECT jurors.id, "
        . "customers.display_name, "
        . "IFNULL(votes.vote, 0) AS vote, "
        . "IFNULL(votes.vote, 0) AS vote_text, "
        . "IFNULL(votes.notes, '') AS notes "
        . "FROM ciniki_form_jurors AS jurors "
        . "LEFT JOIN ciniki_form_votes AS votes ON ("
            . "jurors.id = votes.juror_id "
            . "AND votes.submission_id = '" . ciniki_core_dbQuote($ciniki, $submission_id) . "' "
            . "AND votes.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_customers AS customers ON ("
            . "jurors.customer_id = customers.id "
            . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE jurors.form_id = '" . ciniki_core_dbQuote($ciniki, $form_id) . "' "
        . "AND jurors.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.forms', array(
        array('container'=>'votes', 'fname'=>'id', 
            'fields'=>array('id', 'display_name', 'vote', 'vote_text', 'notes'),
            'maps'=>array('vote_text'=>$maps['vote']['vote']),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.180', 'msg'=>'Unable to load votes', 'err'=>$rc['err']));
    }
    $votes = isset($rc['votes']) ? $rc['votes'] : array();

    return array('stat'=>'ok', 'votes'=>$votes);
}
?>
