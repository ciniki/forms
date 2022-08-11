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
function ciniki_forms_wng_formLoad($ciniki, $tnid, $request, $form_id, $customer_id) {
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
        . "forms.max_customer_submissions, "
        . "forms.fee_label, "
        . "forms.fee_amount, "
        . "forms.cartsubmit_label, "
        . "forms.submit_label, "
        . "forms.dt_start, "
        . "forms.dt_end, "
        . "forms.guidelines, "
        . "forms.termsofuse, "
        . "forms.thankyou, "
        . "forms.alreadysubmitted, "
        . "forms.loginmsg, "
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
        . "IF((fields.flags&0x08)=0x08, 'no', 'yes') AS field_editable, "
        . "fields.field_ref, "
        . "fields.field_size, "
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
                'max_submissions', 'max_customer_submissions', 'fee_label', 'fee_amount', 'cartsubmit_label', 'submit_label', 
                'dt_start', 'dt_end', 'guidelines', 'termsofuse', 'thankyou', 'alreadysubmitted', 'loginmsg', 
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
            'fields'=>array('id'=>'field_id', 'ftype', 'label'=>'field_label', 'field_ref', 'size'=>'field_size', 'flags'=>'field_flags', 
                'required'=>'field_required', 'editable'=>'field_editable',
                'description'=>'field_description', 'options'=>'field_options'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.28', 'msg'=>'Form not found', 'err'=>$rc['err']));
    }
    if( !isset($rc['forms'][0]) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.124', 'msg'=>'Unable to find Form'));
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
    // Get the total number of submissions for this form
    //
    if( $form['max_submissions'] > 0 ) {
        $strsql = "SELECT COUNT(*) AS num "
            . "FROM ciniki_form_submissions "
            . "WHERE form_id = '" . ciniki_core_dbQuote($ciniki, $form['id']) . "' "
            . "AND status = 90 "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbSingleCount');
        $rc = ciniki_core_dbSingleCount($ciniki, $strsql, 'ciniki.forms', 'num');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.70', 'msg'=>'Unable to load get the number of items', 'err'=>$rc['err']));
        }
        $form['num_submissions'] = isset($rc['num']) ? $rc['num'] : 0;
    }

    //
    // Check form status
    //
    if( $form['status'] != 50 ) {
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.forms.21', 'msg'=>'Form expired'));
    }
    $now = new DateTime('now', new DateTimezone('UTC'));
    if( $form['dt_start'] != '' ) {
        $dt_start = new DateTime($form['dt_start'], new DateTimezone('UTC'));
        if( $dt_start > $now ) {
            return array('stat'=>'404', 'err'=>array('code'=>'ciniki.forms.22', 'msg'=>'Form is not yet available'));
        }
    }
    if( $form['dt_end'] != '' ) {
        $dt_end = new DateTime($form['dt_end'], new DateTimezone('UTC'));
        if( $dt_end < $now ) {
            return array('stat'=>'404', 'err'=>array('code'=>'ciniki.forms.23', 'msg'=>'Form is expired'));
        }
    }

    //
    // Setup default submission details
    //
    $form['submission_id'] = 0;
    $form['object'] = '';
    $form['object_id'] = '';
    $form['customer_id'] = $customer_id;
    $form['invoice_id'] = 0;

    //
    // Check to make sure the person is logged in, or present them with login/create form
    //
    if( ($form['flags']&0x01) == 0x01 && ($customer_id == '' || $customer_id <= 0) ) {
        return array('stat'=>'noauth', 'form'=>$form, 'err'=>array('code'=>'ciniki.forms.83', 'msg'=>'Not signed in'));
    } 

    //
    // Setup customer id in the form
    //
    $form['customer_id'] = ($customer_id != '' || $customer_id > 0 ? $customer_id : 0);

    //
    // Load the defaults for the form
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'forms', 'private', 'formDefaultsLoad');
    $rc = ciniki_forms_formDefaultsLoad($ciniki, $tnid, $form);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.125', 'msg'=>'Unable to load form defaults', 'err'=>$rc['err']));
    }

    //
    // Setup the submission/payment/termsofuse section
    //
    $form['sections']['submit'] = array(
        'id' => 'submit',
        'label' => isset($form['submit_label']) && $form['submit_label'] != '' ? $form['submit_label'] : 'Submit',
        'fields' => array(),
        );
    if( isset($form['termsofuse']) && $form['termsofuse'] != '' ) {
        $form['sections']['submit']['fields']['termsofuse'] = array(
            'id' => 'termsofuse',
            'ftype' => 'termsofuse', 
            'prefix' => 'I agree to the',
            'required' => 'yes',
            'label' => 'Terms of Use',
            'description' => 'You must check this box before you can submit.',
            'value' => '',
            'tou' => $form['termsofuse'],
            );
    }
/*    if( isset($form['fee_amount']) && $form['fee_amount'] > 0 ) {
        $form['sections']['submit']['fields']['payment'] = array(
            'id' => 'payment',
            'ftype' => 'payment', 
            'label' => 'Submission Fee',
            'fee_amount' => $form['fee_amount'],
            );
    }
    $form['sections']['submit']['fields']['submit'] = array(
        'id' => 'submit',
        'ftype' => 'submit', 
        ); */

    return array('stat'=>'ok', 'form'=>$form);
}
?>
