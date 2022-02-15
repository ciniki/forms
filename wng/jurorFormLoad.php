<?php
//
// Description
// ===========
// This function will load the form for a juror.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the form is attached to.
// form_id:          The ID of the form to get the details for.
//
// Returns
// -------
//
function ciniki_forms_wng_jurorFormLoad($ciniki, $tnid, $request, $form_permalink, $juror_customer_id) {
    //
    // Load tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
    $intl_currency = $rc['settings']['intl-default-currency'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');
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
    // Load the form
    //
    $strsql = "SELECT forms.id, "
        . "forms.name, "
        . "forms.permalink, "
        . "forms.status, "
        . "forms.flags, "
        . "jurors.id AS juror_id, "
        . "sections.id AS section_id, "
        . "sections.label AS section_label, "
        . "sections.flags AS section_flags, "
        . "sections.repeat_prefix, "
        . "sections.min_repeats, "
        . "sections.max_repeats, "
        . "sections.description AS section_description, "
        . "fields.id AS field_id, "
        . "fields.ftype, "
        . "fields.flags AS field_flags, "
        . "fields.field_ref, "
        . "fields.label AS field_label, "
        . "fields.description AS field_description, "
        . "fields.options AS field_options "
        . "FROM ciniki_forms AS forms "
        . "INNER JOIN ciniki_form_jurors AS jurors ON ("
            . "forms.id = jurors.form_id "
            . "AND jurors.customer_id = '" . ciniki_core_dbQuote($ciniki, $juror_customer_id) . "' "
            . "AND jurors.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_form_sections AS sections ON ("
            . "forms.id = sections.form_id "
            . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_form_fields AS fields ON ("
            . "sections.id = fields.section_id "
            . "AND (fields.flags&0x02) = 0 "        // Make sure field is visible to juror
            . "AND fields.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE forms.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND forms.permalink = '" . ciniki_core_dbQuote($ciniki, $form_permalink) . "' "
        . "AND forms.status = 50 "
        . "AND (forms.flags&0x30) = 0x30 "     // Juried form and open for voting
        . "ORDER BY sections.sequence, sections.label, fields.sequence, fields.label "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.forms', array(
        array('container'=>'forms', 'fname'=>'id', 
            'fields'=>array('id', 'name', 'permalink', 'status', 'flags', 'juror_id'),
            ),
        array('container'=>'sections', 'fname'=>'section_id',
            'fields'=>array('id'=>'section_id', 'label'=>'section_label', 'flags'=>'section_flags', 
                'repeat_prefix', 'min_repeats', 'max_repeats', 'description'=>'section_description',
                ),
            ),
        array('container'=>'fields', 'fname'=>'field_id',
            'fields'=>array('id'=>'field_id', 'ftype', 'label'=>'field_label', 'field_ref', 'flags'=>'field_flags', 
                'description'=>'field_description', 'options'=>'field_options'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.160', 'msg'=>'Form not found', 'err'=>$rc['err']));
    }
    if( !isset($rc['forms'][0]) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.161', 'msg'=>'Unable to find Form'));
    }
    $form = $rc['forms'][0];
    if( isset($form['sections']) ) {
        foreach($form['sections'] as $sid => $section) {
            if( isset($section['fields']) ) {
                foreach($section['fields'] as $fid => $field) {
                    //
                    // Expand the options for the field
                    //
                    if( $field['options'] != '' ) {
                        $options = json_decode($field['options'], true);
                        foreach($options as $k => $v) {
                            $form['sections'][$sid]['fields'][$fid][$k] = $v;
                        }
                    }
                }
            }
        }
    } 

    //
    // Check form status
    //
    if( $form['status'] != 50 ) {
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.forms.159', 'msg'=>'Form expired'));
    }

    $maps['vote']['vote'][0] = 'None';

    //
    // Load the list of submissions
    //
    $strsql = "SELECT submissions.id, "
        . "submissions.uuid, "
        . "IFNULL(votes.id, 0) AS vote_id, "
        . "IFNULL(votes.vote, 0) AS vote, "
        . "IFNULL(votes.vote, 0) AS vote_text, "
        . "IFNULL(votes.notes, '') AS notes "
        . "FROM ciniki_form_submissions AS submissions "
        . "LEFT JOIN ciniki_form_votes AS votes ON ("
            . "submissions.id = votes.submission_id "
            . "AND votes.juror_id = '" . ciniki_core_dbQuote($ciniki, $form['juror_id']) . "' "
            . "AND votes.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE submissions.form_id = '" . ciniki_core_dbQuote($ciniki, $form['id']) . "' "
        . "AND submissions.status = 90 "    // Submitted status
        . "AND submissions.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "ORDER BY id "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.forms', array(
        array('container'=>'submissions', 'fname'=>'id', 
            'fields'=>array('id', 'uuid', 'vote_id', 'vote', 'vote_text', 'notes'),
            'maps'=>array('vote_text'=>$maps['vote']['vote']),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.162', 'msg'=>'Unable to load submissions', 'err'=>$rc['err']));
    }
    $submissions = isset($rc['submissions']) ? $rc['submissions'] : array();

    //
    // Setup submission numbering that will also be used
    //
    $submission_number = 1;
    $form['submissions'] = array();
    foreach($submissions as $sid => $s) {
        $s['number'] = $submission_number;
        $form['submissions'][$submission_number] = $s;
        $submission_number++;
    }

    return array('stat'=>'ok', 'form'=>$form);
}
?>
