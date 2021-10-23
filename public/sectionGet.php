<?php
//
// Description
// ===========
// This method will return all the information about an section.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the section is attached to.
// section_id:          The ID of the section to get the details for.
//
// Returns
// -------
//
function ciniki_forms_sectionGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'section_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Section'),
        'form_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Form'),
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
    $rc = ciniki_forms_checkAccess($ciniki, $args['tnid'], 'ciniki.forms.sectionGet');
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
    // Return default for new Section
    //
    if( $args['section_id'] == 0 ) {
        //
        // Get the next sequence number
        //
        $strsql = "SELECT MAX(sequence) AS num "
            . "FROM ciniki_form_sections "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND form_id = '" . ciniki_core_dbQuote($ciniki, $args['form_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.forms','item');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $seq = (isset($rc['item']['num']) ? $rc['item']['num'] + 1 : 1);
        
        $section = array('id'=>0,
            'form_id' => $args['form_id'],
            'label' => '',
            'flags' => 0,
            'sequence'=> $seq,
            'repeat_prefix'=>'',
            'min_repeats'=>'1',
            'max_repeats'=>'25',
        );
    }

    //
    // Get the details for an existing Section
    //
    else {
        $strsql = "SELECT ciniki_form_sections.id, "
            . "ciniki_form_sections.form_id, "
            . "ciniki_form_sections.label, "
            . "ciniki_form_sections.flags, "
            . "ciniki_form_sections.sequence, "
            . "ciniki_form_sections.repeat_prefix, "
            . "ciniki_form_sections.min_repeats, "
            . "ciniki_form_sections.max_repeats, "
            . "ciniki_form_sections.description "
            . "FROM ciniki_form_sections "
            . "WHERE ciniki_form_sections.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_form_sections.id = '" . ciniki_core_dbQuote($ciniki, $args['section_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.forms', array(
            array('container'=>'sections', 'fname'=>'id', 
                'fields'=>array('form_id', 'label', 'flags', 'sequence', 
                    'repeat_prefix', 'min_repeats', 'max_repeats', 'description',
                    ),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.127', 'msg'=>'Section not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['sections'][0]) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.48', 'msg'=>'Unable to find Section'));
        }
        $section = $rc['sections'][0];
    }

    return array('stat'=>'ok', 'section'=>$section);
}
?>
