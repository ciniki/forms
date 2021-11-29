<?php
//
// Description
// -----------
// This function will update the label for the submission using the fields specified as labels.
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_forms_submissionLabelUpdate(&$ciniki, $tnid, $submission_id) {

    //
    // Load the submission
    //
    $strsql = "SELECT submissions.id, "
        . "submissions.label, "
        . "DATE_FORMAT(submissions.date_added, '%b %e, %Y %l:%i %p') AS date_added, "
        . "DATE_FORMAT(submissions.dt_last_submitted, '%b %e, %Y %l:%i %p') AS date_submitted, "
        . "data.field_id, "
        . "data.data "
        . "FROM ciniki_form_submissions AS submissions "
        . "LEFT JOIN ciniki_form_sections AS sections ON ("
            . "submissions.form_id = sections.form_id "
            . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_form_fields AS fields ON ("
            . "sections.id = fields.section_id "
            . "AND (fields.flags&0x04) = 0x04 "
            . "AND fields.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_form_data AS data ON ("
            . "fields.id = data.field_id "
            . "AND submissions.id = data.submission_id "
            . "AND data.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE submissions.id = '" . ciniki_core_dbQuote($ciniki, $submission_id) . "' "
        . "AND submissions.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.forms', array(
        array('container'=>'submissions', 'fname'=>'id', 
            'fields'=>array('id', 'label', 'date_added', 'date_submitted'),
            ),
        array('container'=>'fields', 'fname'=>'field_id', 'fields'=>array('data')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.172', 'msg'=>'Unable to load submissions', 'err'=>$rc['err']));
    }
    if( !isset($rc['submissions'][0]) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.173', 'msg'=>'Submission not found'));
    }
    $submission = $rc['submissions'][0];

    //
    // Create the new label
    //
    $label = '';
    if( isset($submission['fields']) ) {
        foreach($submission['fields'] as $field) {
            if( $field['data'] != '' ) {
                $label .= ($label != '' ? ' - ' : '') . $field['data'];
            }
        }
    }
    if( $label == '' ) {
        if( $submission['date_submitted'] != '' ) {
            $label = $submission['date_submitted'];
        }
        elseif( $submission['date_added'] != '' ) {
            $label = $submission['date_added'];
        }
    }

    if( $label != $submission['label'] ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
        $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.forms.submission', $submission_id, array(
            'label' => $label,
            ), 0x04);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.174', 'msg'=>'Unable to update the submission', 'err'=>$rc['err']));
        }
    }

    return array('stat'=>'ok');
}
?>
