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
function ciniki_forms_wng_submissionLoad(&$ciniki, $tnid, $request, &$form) {

    if( !isset($form['id']) || $form['id'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.55', 'msg'=>'No form id specified'));
    }
    if( !isset($form['submission_id']) || !is_numeric($form['submission_id']) || $form['submission_id'] <= 0 ) {
        if( !isset($form['object']) || !isset($form['object_id']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.56', 'msg'=>'No object specified'));
        }
        if( !isset($form['customer_id']) || !isset($form['customer_id']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.51', 'msg'=>'No customer specified'));
        }
    }

    //
    // Load the submission for the form, object, and customer
    //
    $strsql = "SELECT submissions.id, "
        . "submissions.uuid, "
        . "submissions.object, "
        . "submissions.object_id, "
        . "submissions.customer_id, "
        . "submissions.invoice_id, "
        . "submissions.status, "
        . "submissions.label, "
        . "submissions.dt_terms_accepted, "
        . "submissions.dt_last_submitted, "
        . "data.id AS data_id, "
        . "data.field_id, "
        . "data.repeat_num, "
        . "data.data "
        . "FROM ciniki_form_submissions AS submissions "
        . "LEFT JOIN ciniki_form_data AS data ON ("
            . "submissions.id = data.submission_id "
            . "AND data.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE submissions.form_id = '" . ciniki_core_dbQuote($ciniki, $form['id']) . "' "
        . "AND submissions.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    if( isset($form['submission_id']) && $form['submission_id'] > 0 ) {
        $strsql .= "AND submissions.id = '" . ciniki_core_dbQuote($ciniki, $form['submission_id']) . "' ";
    } else {
        $strsql .= "AND submissions.object = '" . ciniki_core_dbQuote($ciniki, $form['object']) . "' "
            . "AND submissions.object_id = '" . ciniki_core_dbQuote($ciniki, $form['object_id']) . "' "
            . "AND submissions.customer_id = '" . ciniki_core_dbQuote($ciniki, $form['customer_id']) . "' "
            . "";
    }
    $strsql .= "ORDER BY data.field_id, data.repeat_num "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.forms', array(
        array('container'=>'submissions', 'fname'=>'id', 
            'fields'=>array('id', 'uuid', 'object', 'object_id', 'customer_id', 'invoice_id', 'status', 'label',
                'dt_terms_accepted', 'dt_last_submitted',
                )),
        array('container'=>'fields', 'fname'=>'field_id', 
            'fields'=>array('id'=>'field_id', 'data_id', 'data'),
            ),
        array('container'=>'repeats', 'fname'=>'repeat_num', 
            'fields'=>array('id'=>'repeat_num', 'data_id', 'data'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.52', 'msg'=>'Unable to load submission', 'err'=>$rc['err']));
    }
    if( !isset($rc['submissions']) || count($rc['submissions']) == 0 ) {
        //
        // No submission yet
        //
        $form['submission_id'] = 'new';
        return array('stat'=>'ok');
    }
    $form['submission'] = array_shift($rc['submissions']);
    $form['submission_id'] = $form['submission']['id'];
    $form['invoice_id'] = $form['submission']['invoice_id'];
    $form['invoice_status'] = 0;

    //
    // Load the invoice status 
    //
    if( isset($form['submission']['invoice_id']) && $form['submission']['invoice_id'] > 0 ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'hooks', 'invoiceStatus');
        $rc = ciniki_sapos_hooks_invoiceStatus($ciniki, $tnid, array('invoice_id'=>$form['submission']['invoice_id']));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.57', 'msg'=>'Unable to load invoice', 'err'=>$rc['err']));
        }
        if( !isset($rc['invoice']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.54', 'msg'=>'Unable to load invoice', 'err'=>$rc['err']));
        }
        $form['invoice'] = $rc['invoice'];
        $form['invoice_status'] = $rc['invoice']['status'];
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
                        if( isset($form['submission']['fields'][$field['id']]['repeats'][$i]['data']) ) {
                            $form['sections'][$sid]['fields'][$fid]['data_ids'][$i] = $form['submission']['fields'][$field['id']]['repeats'][$i]['data_id'];
                            $form['sections'][$sid]['fields'][$fid]['values'][$i] = $form['submission']['fields'][$field['id']]['repeats'][$i]['data'];
                        }
                    }
                }
            }
            elseif( isset($section['fields']) ) {
                foreach($section['fields'] as $fid => $field) {
                    //
                    // When special termsofuse field, add the value
                    if( $field['ftype'] == 'termsofuse' ) {
                        if( $form['submission']['dt_terms_accepted'] == '' 
                            || $form['submission']['dt_terms_accepted'] == '0000-00-00 00:00:00' 
                            ) {
                            $form['sections'][$sid]['fields'][$fid]['value'] = 'off';
                        } else {
                            $form['sections'][$sid]['fields'][$fid]['value'] = 'on';
                        }
                    }
                    //
                    // Check if submission data found for field
                    //
                    elseif( isset($form['submission']['fields'][$field['id']]['data']) ) {
                        $form['sections'][$sid]['fields'][$fid]['data_id'] = $form['submission']['fields'][$field['id']]['data_id'];
                        // FIXME: Add other field type handlers
                        if( $field['ftype'] == 'address' ) {
                            $form['sections'][$sid]['fields'][$fid]['value'] = json_decode($form['submission']['fields'][$field['id']]['data'], true);

                        } else {
                            $form['sections'][$sid]['fields'][$fid]['value'] = $form['submission']['fields'][$field['id']]['data'];
                        }
                    }
                }
            }
        }
    } 

    return array('stat'=>'ok');
}
?>
