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
function ciniki_forms_formGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'form_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Form'),
        'section_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Section'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'forms', 'private', 'checkAccess');
    $rc = ciniki_forms_checkAccess($ciniki, $args['tnid'], 'ciniki.forms.formGet');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
    $intl_currency = $rc['settings']['intl-default-currency'];

    //
    // Load maps
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'forms', 'private', 'maps');
    $rc = ciniki_forms_maps($ciniki);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $maps = $rc['maps'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'datetimeFormat');
    $datetime_format = ciniki_users_datetimeFormat($ciniki, 'php');

    //
    // Return default for new Form
    //
    if( $args['form_id'] == 0 ) {
        $form = array('id'=>0,
            'name'=>'',
            'permalink'=>'',
            'type'=>'',
            'status'=>'10',
            'flags'=>0x01,
            'max_submissions'=>'',
            'max_customer_submissions'=>'1',
            'fee_label' => '',
            'fee_amount'=>'',
            'fee_label' => '',
            'submit_label' => '',
            'dt_start'=>'',
            'dt_end'=>'',
            'guidelines'=>'',
            'termsofuse'=>'',
            'thankyou'=>'',
            'alreadysubmitted'=>'',
            'loginmsg'=>'',
        );
    }

    //
    // Get the details for an existing Form
    //
    else {
        $strsql = "SELECT ciniki_forms.id, "
            . "ciniki_forms.name, "
            . "ciniki_forms.permalink, "
            . "ciniki_forms.type, "
            . "ciniki_forms.status, "
            . "ciniki_forms.flags, "
            . "ciniki_forms.max_submissions, "
            . "ciniki_forms.max_customer_submissions, "
            . "ciniki_forms.fee_label, "
            . "ciniki_forms.fee_amount, "
            . "ciniki_forms.cartsubmit_label, "
            . "ciniki_forms.submit_label, "
            . "ciniki_forms.dt_start, "
            . "ciniki_forms.dt_end, "
            . "ciniki_forms.guidelines, "
            . "ciniki_forms.termsofuse, "
            . "ciniki_forms.thankyou, "
            . "ciniki_forms.alreadysubmitted, "
            . "ciniki_forms.loginmsg "
            . "FROM ciniki_forms "
            . "WHERE ciniki_forms.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_forms.id = '" . ciniki_core_dbQuote($ciniki, $args['form_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.forms', array(
            array('container'=>'forms', 'fname'=>'id', 
                'fields'=>array('name', 'permalink', 'type', 'status', 'flags', 
                    'max_submissions', 'max_customer_submissions', 'fee_label', 'fee_amount', 'cartsubmit_label', 'submit_label', 
                    'dt_start', 'dt_end', 'guidelines', 'termsofuse', 'thankyou', 'alreadysubmitted', 'loginmsg',
                    ),
                'naprices'=>array('fee_amount'),
                'utctotz'=>array(
                    'dt_start'=>array('format'=>$datetime_format, 'timezone'=>$intl_timezone),
                    'dt_end'=>array('format'=>$datetime_format, 'timezone'=>$intl_timezone),
                    ),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.8', 'msg'=>'Form not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['forms'][0]) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.29', 'msg'=>'Unable to find Form'));
        }
        $form = $rc['forms'][0];

        //
        // Load the sections 
        //
        $strsql = "SELECT ciniki_form_sections.id, "
            . "ciniki_form_sections.label, "
            . "ciniki_form_sections.flags, "
            . "ciniki_form_sections.sequence, "
            . "ciniki_form_sections.min_repeats, "
            . "ciniki_form_sections.max_repeats "
            . "FROM ciniki_form_sections "
            . "WHERE ciniki_form_sections.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_form_sections.form_id = '" . ciniki_core_dbQuote($ciniki, $args['form_id']) . "' "
            . "ORDER BY sequence, label "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.forms', array(
            array('container'=>'sections', 'fname'=>'id', 
                'fields'=>array('id', 'label', 'flags', 'sequence', 'min_repeats', 'max_repeats')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $form['sections'] = isset($rc['sections']) ? $rc['sections'] : array();
        $form['section_ids'] = array();
        foreach($form['sections'] as $iid => $section) {
            $form['section_ids'][] = $section['id'];
        }

        if( isset($args['section_id']) && $args['section_id'] > 0 ) {
            //
            // Get the list of field refs
            //
            ciniki_core_loadMethod($ciniki, 'ciniki', 'forms', 'private', 'fieldRefsAvailable');
            $rc = ciniki_forms_fieldRefsAvailable($ciniki, $args['tnid']);
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.24', 'msg'=>'Unable to load field references', 'err'=>$rc['err']));
            }
            $refs = $rc['refs'];

            //
            // Load the fields
            //
            $strsql = "SELECT ciniki_form_fields.id, "
                . "ciniki_form_fields.section_id, "
                . "ciniki_form_fields.ftype, "
                . "ciniki_form_fields.ftype AS type_text, "
                . "ciniki_form_fields.flags, "
                . "ciniki_form_fields.sequence, "
                . "ciniki_form_fields.field_ref, "
                . "ciniki_form_fields.label "
                . "FROM ciniki_form_fields "
                . "WHERE ciniki_form_fields.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "AND ciniki_form_fields.section_id = '" . ciniki_core_dbQuote($ciniki, $args['section_id']) . "' "
                . "ORDER BY sequence, label "
                . "";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
            $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.forms', array(
                array('container'=>'fields', 'fname'=>'id', 
                    'fields'=>array('id', 'section_id', 'ftype', 'type_text', 'flags', 'sequence', 'field_ref', 'label'),
                    'maps'=>array('type_text'=>$maps['field']['ftype']),
                    ),
                ));
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            $form['fields'] = isset($rc['fields']) ? $rc['fields'] : array();
            $form['field_ids'] = array();
            foreach($form['fields'] as $iid => $field) {
                $form['fields'][$iid]['field_ref_text'] = '';
                if( isset($refs[$field['field_ref']]) ) {
                    $form['fields'][$iid]['field_ref_text'] = $refs[$field['field_ref']]['module'] . ' - ' . $refs[$field['field_ref']]['name'];
                }
                $form['field_ids'][] = $field['id'];
            }
        }

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
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.136', 'msg'=>'Unable to load jurors', 'err'=>$rc['err']));
        }
        $form['jurors'] = isset($rc['jurors']) ? $rc['jurors'] : array();
    }

    return array('stat'=>'ok', 'form'=>$form);
}
?>
