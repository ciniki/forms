<?php
//
// Description
// -----------
// Return the list of available field refs for ciniki.forms module.
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_forms_hooks_formDefaultsLoad(&$ciniki, $tnid, $args) {
   
    if( !isset($args['form']) ) {
        return array('stat'=>'ok');
    }
    $form = $args['form'];
   
    //
    // Process the customer if specified
    //
    if( isset($form['customer_id']) && $form['customer_id'] != '' && $form['customer_id'] > 0 ) {
        //
        // Load the customers submission data from active forms
        //
        $strsql = "SELECT forms.id AS form_id, "
            . "data.field_id, "
            . "data.repeat_num, "
            . "data.data "
            . "FROM ciniki_forms AS forms "
            . "INNER JOIN ciniki_form_submissions AS submissions ON ("
                . "forms.id = submissions.form_id "
                . "AND submissions.customer_id = '" . ciniki_core_dbQuote($ciniki, $form['customer_id']) . "' "
                . "AND submissions.status >= 80 "
                . "AND submissions.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "INNER JOIN ciniki_form_data AS data ON ("
                . "submissions.id = data.submission_id "
                . "AND data.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "WHERE forms.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND forms.status = 50 "
            . "ORDER BY forms.id, data.field_id, data.repeat_num "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
        $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.forms', array(
            array('container'=>'forms', 'fname'=>'form_id', 
                'fields'=>array(),
                ),
            array('container'=>'fields', 'fname'=>'field_id', 
                'fields'=>array('form_id', 'field_id', 'repeat_num', 'data'),
                ),
            array('container'=>'repeats', 'fname'=>'repeat_num', 
                'fields'=>array('form_id', 'field_id', 'repeat_num', 'data'),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.182', 'msg'=>'Unable to load fields', 'err'=>$rc['err']));
        }
        $data = isset($rc['forms']) ? $rc['forms'] : array();

        if( isset($form['sections']) ) {
            foreach($form['sections'] as $sid => $section) {
                if( isset($section['fields']) ) {
                    foreach($section['fields'] as $fid => $field) {
                        //
                        // Check if field ref is for customer and if the data exists
                        //
                        if( isset($field['prefill_ref']) && $field['prefill_ref'] != '' 
                            && preg_match("/^ciniki\.forms\.([^\.]+)\.([^\.]+)/", $field['prefill_ref'], $m)
                            && isset($data[$m[1]]['fields'][$m[2]]['data'])
                            ) {
                            if( isset($section['flags']) && ($section['flags']&0x01) == 0x01 ) {
                                $form['sections'][$sid]['fields'][$fid]['defaults'] = array(); 
                                for($i = 1; $i <= $section['max_repeats']; $i++ ) {
                                    if( isset($data[$m[1]]['fields'][$m[2]]['repeats'][$i]) ) {
                                        $form['sections'][$sid]['fields'][$fid]['defaults'][$i] = $data[$m[1]]['fields'][$m[2]]['repeats'][$i]['data'];
                                    }
                                }
                            } else {
                                $form['sections'][$sid]['fields'][$fid]['default'] = $data[$m[1]]['fields'][$m[2]]['data'];
                            }
                        }
                    }
                }
            }
        }
    }

    return array('stat'=>'ok', 'form'=>$form);
}
?>
