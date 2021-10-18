<?php
//
// Description
// ===========
// This method will return all the information about an form.
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
function ciniki_forms_formLoad($ciniki, $tnid, $form_id) {
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
    // Load the form
    //
    $strsql = "SELECT forms.id, "
        . "forms.name, "
        . "forms.permalink, "
        . "forms.type, "
        . "forms.status, "
        . "forms.flags, "
        . "forms.max_submissions, "
        . "forms.fee_label, "
        . "forms.fee_amount, "
        . "forms.cartsubmit_label, "
        . "forms.submit_label, "
        . "forms.dt_start, "
        . "forms.dt_end, "
        . "forms.guidelines, "
        . "forms.termsofuse, "
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
        . "IF((fields.flags&0x01)=0x01, 'yes', 'no') AS field_required, "
        . "fields.field_ref, "
        . "fields.label AS field_label, "
        . "fields.description AS field_description, "
        . "fields.options AS field_options "
        . "FROM ciniki_forms AS forms "
        . "LEFT JOIN ciniki_form_sections AS sections ON ("
            . "forms.id = sections.form_id "
            . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_form_fields AS fields ON ("
            . "sections.id = fields.section_id "
            . "AND fields.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE forms.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND forms.id = '" . ciniki_core_dbQuote($ciniki, $form_id) . "' "
        . "ORDER BY sections.sequence, sections.label, fields.sequence, fields.label "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.forms', array(
        array('container'=>'forms', 'fname'=>'id', 
            'fields'=>array('id', 'name', 'permalink', 'type', 'status', 'flags', 
                'max_submissions', 'fee_label', 'fee_amount', 'cartsubmit_label', 'submit_label', 
                'dt_start', 'dt_end', 'guidelines', 'termsofuse',
                ),
            'utctotz'=>array(
                'dt_start'=>array('format'=>$datetime_format, 'timezone'=>$intl_timezone),
                'dt_end'=>array('format'=>$datetime_format, 'timezone'=>$intl_timezone),
                ),
            ),
        array('container'=>'sections', 'fname'=>'section_id',
            'fields'=>array('id'=>'section_id', 'label'=>'section_label', 'flags'=>'section_flags', 
                'repeat-prefix'=>'repeat_prefix', 'min_repeats', 'max_repeats', 'description'=>'section_description',
                ),
            ),
        array('container'=>'fields', 'fname'=>'field_id',
            'fields'=>array('id'=>'field_id', 'ftype', 'label'=>'field_label', 'field_ref', 'flags'=>'field_flags', 
                'required'=>'field_required',
                'description'=>'field_description', 'options'=>'field_options'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.28', 'msg'=>'Form not found', 'err'=>$rc['err']));
    }
    if( !isset($rc['forms'][0]) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.9', 'msg'=>'Unable to find Form'));
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
    // Setup default submission details
    //
    $form['submission_id'] = 0;
    $form['object'] = '';
    $form['object_id'] = '';
    $form['customer_id'] = 0;
    $form['submission_id'] = 0;
    $form['invoice_id'] = 0;

    //
    // Load the defaults for the form
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'forms', 'private', 'formDefaultsLoad');
    $rc = ciniki_forms_formDefaultsLoad($ciniki, $tnid, $form);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.25', 'msg'=>'Unable to load form defaults', 'err'=>$rc['err']));
    }

    return array('stat'=>'ok', 'form'=>$form);
}
?>
