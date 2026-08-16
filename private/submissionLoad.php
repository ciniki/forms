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
function ciniki_forms_submissionLoad(&$ciniki, $tnid, $submission_id) {

    //
    // Load the tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];

    //
    // Load the date format strings for the user
    //
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
    // Load the submission for the form, object, and customer
    //
    $strsql = "SELECT submissions.id, "
        . "submissions.uuid, "
        . "submissions.label, "
        . "submissions.form_id, "
        . "submissions.object, "
        . "submissions.object_id, "
        . "submissions.customer_id, "
        . "submissions.invoice_id, "
        . "submissions.status, "
        . "submissions.status AS status_text, "
        . "submissions.dt_terms_accepted, "
        . "submissions.dt_terms_accepted AS dt_terms_accepted_display, "
        . "submissions.dt_last_submitted, "
        . "submissions.dt_last_submitted AS dt_last_submitted_display, "
        . "submissions.dt_last_save, "
        . "submissions.dt_last_save AS dt_last_save_display, "
        . "data.id AS data_id, "
        . "data.field_id, "
        . "data.repeat_num, "
        . "data.data "
        . "FROM ciniki_form_submissions AS submissions "
        . "LEFT JOIN ciniki_form_data AS data ON ("
            . "submissions.id = data.submission_id "
            . "AND data.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE submissions.id = '" . ciniki_core_dbQuote($ciniki, $submission_id) . "' "
        . "AND submissions.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "ORDER BY data.field_id, data.repeat_num "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.forms', array(
        array('container'=>'submissions', 'fname'=>'id', 
            'fields'=>array('id', 'uuid', 'label', 'form_id', 'object', 'object_id', 'customer_id', 'invoice_id', 'status', 'status_text',
                'dt_terms_accepted', 'dt_last_submitted', 'dt_terms_accepted_display', 'dt_last_submitted_display',
                'dt_last_save', 'dt_last_save_display',
                ),
            'maps'=>array('status_text'=>$maps['submission']['status']),
            'utctotz'=>array(
                'dt_terms_accepted_display'=>array('timezone'=>$intl_timezone, 'format'=>$datetime_format),
                'dt_last_submitted_display'=>array('timezone'=>$intl_timezone, 'format'=>$datetime_format),
                'dt_last_save_display'=>array('timezone'=>$intl_timezone, 'format'=>$datetime_format),
                ),
            ),
        array('container'=>'data', 'fname'=>'field_id', 
            'fields'=>array('id'=>'field_id', 'data_id', 'data'),
            ),
        array('container'=>'repeats', 'fname'=>'repeat_num', 
            'fields'=>array('id'=>'repeat_num', 'data_id', 'data'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.109', 'msg'=>'Unable to load submission', 'err'=>$rc['err']));
    }
    if( !isset($rc['submissions']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.110', 'msg'=>'Unable to load submission'));
    }
    $submission = array_shift($rc['submissions']);

    //
    // Load the form
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'forms', 'private', 'formLoad');
    $rc = ciniki_forms_formLoad($ciniki, $tnid, $submission['form_id']);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.111', 'msg'=>'', 'err'=>$rc['err']));
    }
    $form = $rc['form'];
    $form['submission'] = $submission;
    $form['submission_id'] = $submission['id'];

    //
    // Load the invoice status 
    //
    if( isset($submission['invoice_id']) && $submission['invoice_id'] > 0 ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'hooks', 'invoiceStatus');
        $rc = ciniki_sapos_hooks_invoiceStatus($ciniki, $tnid, array('invoice_id'=>$submission['invoice_id']));
        //
        // Changed code so submission will still load when cart or invoice has been deleted
        //
        if( $rc['stat'] != 'ok' && $rc['stat'] != 'noexist' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.130', 'msg'=>'Unable to load invoice', 'err'=>$rc['err']));
        }
        elseif( isset($rc['invoice']) ) {
//            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.129', 'msg'=>'Unable to load invoice', 'err'=>$rc['err']));
            $form['invoice'] = $rc['invoice'];
            $form['invoice_status'] = $rc['invoice']['status'];
        }
    }

    //
    // Apply the submission data to the form values
    //
    if( isset($form['sections']) ) {
        foreach($form['sections'] as $sid => $section) {
            if( isset($section['fields']) && isset($section['flags']) && ($section['flags']&0x01) == 0x01 ) {
                for($i = 1; $i <= $section['max_repeats']; $i++) {
                    foreach($section['fields'] as $fid => $field) {
                        // FIXME: Add field type handlers
                        //
                        // Check if submission data found for field
                        //
                        if( isset($form['submission']['data'][$field['id']]['repeats'][$i]['data']) ) {
                            $form['sections'][$sid]['fields'][$fid]['data_ids'][$i] = $form['submission']['data'][$field['id']]['repeats'][$i]['data_id'];
                            $form['sections'][$sid]['fields'][$fid]['values'][$i] = $form['submission']['data'][$field['id']]['repeats'][$i]['data'];
                        }
                    }
                }
            }
            elseif( isset($section['fields']) ) {
                foreach($section['fields'] as $fid => $field) {
                    //
                    // Check if submission data found for field
                    //
                    if( isset($form['submission']['data'][$field['id']]['data']) ) {
                        $form['sections'][$sid]['fields'][$fid]['data_id'] = $form['submission']['data'][$field['id']]['data_id'];
                        // FIXME: Add other field type handlers
                        if( $field['ftype'] == 'address' ) {
                            $form['sections'][$sid]['fields'][$fid]['value'] = json_decode($form['submission']['data'][$field['id']]['data'], true);

                        } else {
                            $form['sections'][$sid]['fields'][$fid]['value'] = $form['submission']['data'][$field['id']]['data'];
                        }
                    }
                }
            }
        }
    } 

    return array('stat'=>'ok', 'form'=>$form);
}
?>
