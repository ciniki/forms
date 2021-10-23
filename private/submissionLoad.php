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
    // Load the submission for the form, object, and customer
    //
    $strsql = "SELECT submissions.id, "
        . "submissions.form_id, "
        . "submissions.object, "
        . "submissions.object_id, "
        . "submissions.customer_id, "
        . "submissions.invoice_id, "
        . "submissions.status, "
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
        . "WHERE submissions.id = '" . ciniki_core_dbQuote($ciniki, $submission_id) . "' "
        . "AND submissions.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "ORDER BY data.field_id, data.repeat_num "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.forms', array(
        array('container'=>'submissions', 'fname'=>'id', 
            'fields'=>array('id', 'form_id', 'object', 'object_id', 'customer_id', 'invoice_id', 'status', 
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
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.109', 'msg'=>'Unable to load submission', 'err'=>$rc['err']));
    }
    if( !isset($rc['submissions']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.110', 'msg'=>'Unable to load submission', 'err'=>$rc['err']));
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
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.130', 'msg'=>'Unable to load invoice', 'err'=>$rc['err']));
        }
        if( !isset($rc['invoice']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.129', 'msg'=>'Unable to load invoice', 'err'=>$rc['err']));
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
                    // Check if submission data found for field
                    //
                    if( isset($form['submission']['fields'][$field['id']]['data']) ) {
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

    return array('stat'=>'ok', 'form'=>$form);
}
?>
