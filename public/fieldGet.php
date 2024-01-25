<?php
//
// Description
// ===========
// This method will return all the information about an form field.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the form field is attached to.
// field_id:          The ID of the form field to get the details for.
//
// Returns
// -------
//
function ciniki_forms_fieldGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'field_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Form Field'),
        'form_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Form'),
        'section_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Section'),
        'sectionlist'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Section List'),
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
    $rc = ciniki_forms_checkAccess($ciniki, $args['tnid'], 'ciniki.forms.fieldGet');
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

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');

    //
    // Return default for new Form Field
    //
    if( $args['field_id'] == 0 ) {
        //
        // Get the next sequence number
        //
        $strsql = "SELECT MAX(sequence) AS num "
            . "FROM ciniki_form_fields "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND section_id = '" . ciniki_core_dbQuote($ciniki, $args['section_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.forms','item');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $seq = (isset($rc['item']['num']) ? $rc['item']['num'] + 1 : 1);
    
        $field = array(
            'id' => 0,
            'section_id' => $args['section_id'],
            'ftype' => 'text',
            'flags' => '0',
            'sequence' => $seq,
            'prefill_ref' => '',
            'field_ref' => '',
            'field_size' => 'large',
            'label' => '',
            'description' => '',
            'options' => '',
            'pdf_label_size' => '',
            'pdf_value_size' => '',
        );
    }

    //
    // Get the details for an existing Form Field
    //
    else {
        $strsql = "SELECT ciniki_form_fields.id, "
            . "ciniki_form_fields.section_id, "
            . "ciniki_form_fields.ftype, "
            . "ciniki_form_fields.flags, "
            . "ciniki_form_fields.sequence, "
            . "ciniki_form_fields.prefill_ref, "
            . "ciniki_form_fields.field_ref, "
            . "ciniki_form_fields.field_size, "
            . "ciniki_form_fields.label, "
            . "ciniki_form_fields.description, "
            . "ciniki_form_fields.formula, "
            . "ciniki_form_fields.options, "
            . "ciniki_form_fields.pdf_label_size, "
            . "ciniki_form_fields.pdf_value_size "
            . "FROM ciniki_form_fields "
            . "WHERE ciniki_form_fields.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_form_fields.id = '" . ciniki_core_dbQuote($ciniki, $args['field_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.forms', array(
            array('container'=>'fields', 'fname'=>'id', 
                'fields'=>array('section_id', 'ftype', 'flags', 'sequence', 
                    'prefill_ref', 'field_ref', 'field_size', 'label', 'description', 'formula', 'options',
                    'pdf_label_size', 'pdf_value_size',
                    ),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.42', 'msg'=>'Form Field not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['fields'][0]) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.43', 'msg'=>'Unable to find Form Field'));
        }
        $field = $rc['fields'][0];
        
        if( $field['options'] != '' ) {
            $options = json_decode($field['options'], true);
            foreach($options as $k => $v) {
                $field[$k] = $v;
            }
        }

    }
    $rsp = array('stat'=>'ok', 'field'=>$field);

    //
    // Get the list of sections if form_id specified
    //
    if( isset($args['sectionlist']) && $args['sectionlist'] == 'yes' && isset($args['form_id']) && $args['form_id'] > 0 ) {
        $strsql = "SELECT ciniki_form_sections.id, "
            . "ciniki_form_sections.label "
            . "FROM ciniki_form_sections "
            . "WHERE ciniki_form_sections.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_form_sections.form_id = '" . ciniki_core_dbQuote($ciniki, $args['form_id']) . "' "
            . "ORDER BY sequence, label "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.forms', array(
            array('container'=>'sections', 'fname'=>'id', 
                'fields'=>array('id', 'name'=>'label')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $rsp['sections'] = isset($rc['sections']) ? $rc['sections'] : array();
    }

    //
    // Get the list of field refs
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'forms', 'private', 'fieldRefsAvailable');
    $rc = ciniki_forms_fieldRefsAvailable($ciniki, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.31', 'msg'=>'Unable to load field references', 'err'=>$rc['err']));
    }
    $rsp['refs'] = $rc['refs'];

    return $rsp;
}
?>
