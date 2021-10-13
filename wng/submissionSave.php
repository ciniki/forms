<?php
//
// Description
// -----------
// Save the form submission via the api. Typically only 1 section is sent at a time.
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_forms_wng_submissionSave(&$ciniki, $tnid, $request) {
    
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');

    //
    // Make sure customer is logged in
    //
    if( !isset($request['session']['customer']['id']) || $request['session']['customer']['id'] <= 0 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.62', 'msg'=>'Not signed in'));
    }
    
    if( !isset($request['args']['customer_id']) || $request['args']['customer_id'] <= 0 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.63', 'msg'=>'Not signed in'));
    }
    if( $request['args']['customer_id'] != $request['args']['customer_id'] ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.64', 'msg'=>'Incorrect account'));
    }

    //
    // Make sure the form id is specified
    //
    if( !isset($request['args']['form_id']) || $request['args']['form_id'] <= 0 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.65', 'msg'=>'No form specified'));
    }

    //
    // Load the form
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'forms', 'wng', 'formLoad');
    $rc = ciniki_forms_wng_formLoad($ciniki, $tnid, $request, $request['args']['form_id']);
    if( $rc['stat'] == 'noauth' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.66', 'msg'=>'Not signed in'));
    }
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.67', 'msg'=>'Unable to load form', 'err'=>$rc['err']));
    }
    $form = $rc['form'];
        
    //
    // Load the existing submission
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'forms', 'private', 'submissionLoad');
    $rc = ciniki_forms_submissionLoad($ciniki, $tnid, $form);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.68', 'msg'=>'', 'err'=>$rc['err']));
    }

    //
    // Apply any posted updates to the form
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'forms', 'wng', 'formPOSTApply');
    $rc = ciniki_forms_wng_formPOSTApply($ciniki, $tnid, $request, $form);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.69', 'msg'=>'Unable to apply posted updates to form', 'err'=>$rc['err']));
    }

    //
    // Check if submission_id specified
    //
    if( isset($form['submission_id']) && $form['submission_id'] > 0 ) {
        error_log("Update Submission");
        
    } else {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
        $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.forms.submission', array(
            'form_id' => $form['id'],
            'object' => $form['object'],
            'object_id' => $form['object_id'],
            'customer_id' => $form['customer_id'],
            'invoice_id' => $form['invoice_id'],
            'status' => 10,
            ), 0x04);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.71', 'msg'=>'Unable to add the submission', 'err'=>$rc['err']));
        }
        $form['submission_id'] = $rc['id'];

        //
        // Setup API args with submission id
        //
        $api_args = array(
            'form_id' => $form['id'],
            'submission_id' => $form['submission_id'],
            'object' => $form['object'],
            'object_id' => $form['object_id'],
            'customer_id' => $form['customer_id'],
            );
    }

    //
    // Check for any changes that need to be saved
    //
    $image_urls = array();
    if( isset($form['sections']) ) {
        foreach($form['sections'] as $sid => $section) {
            if( isset($section['fields']) && isset($section['flags']) && ($section['flags']&0x01) == 0x01 ) {
                for($i = 1; $i <= $section['max_repeats']; $i++) {
                    foreach($section['fields'] as $fid => $field) {
                        //
                        // Check if submission data found 
                        //
                        if( isset($field['data_ids'][$i]) && $field['data_ids'][$i] > 0 ) {
                            //
                            // Check for changes in old to new values
                            //
                            if( isset($field['old_values'][$i]) && $field['old_values'][$i] != $field['values'][$i] ) {
                                $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.forms.data', $field['data_ids'][$i], array(
                                    'data' => is_array($field['values'][$i]) ? $json_encode($field['values'][$i]) : $field['values'][$i],
                                    ), 0x04);
                                if( $rc['stat'] != 'ok' ) {
                                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.74', 'msg'=>'Unable to add data', 'err'=>$rc['err']));
                                }
                                if( $field['ftype'] == 'image' ) {
                                    $image_urls["{$field['id']}-{$i}"] = $request['api_url'] . "/ciniki/forms/submissionImage/" . $form['id'] . '/' . $form['submission_id'] . '/' . $field['values'][$i];
                                }
                            }
                        } elseif( isset($field['values'][$i]) ) {
                            // 
                            // No previous data saved, add new data
                            //
                            $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.forms.data', array(
                                'submission_id' => $form['submission_id'],
                                'field_id' => $field['id'],
                                'repeat_num' => $i,
                                'data' => is_array($field['values'][$i]) ? $json_encode($field['values'][$i]) : $field['values'][$i],
                                ), 0x04);
                            if( $rc['stat'] != 'ok' ) {
                                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.72', 'msg'=>'Unable to add the field', 'err'=>$rc['err']));
                            }
                            if( $field['ftype'] == 'image' ) {
                                $image_urls["{$field['id']}-{$i}"] = $request['api_url'] . "/ciniki/forms/submissionImage/" . $form['id'] . '/' . $form['submission_id'] . '/' . $field['values'][$i];
                            }
                        }
                    }
                }
            }
            elseif( isset($section['fields']) ) {
                foreach($section['fields'] as $fid => $field) {
                    //
                    // Check if submission field data exists
                    //
                    if( isset($field['data_id']) && $field['data_id'] > 0 ) {
                        //
                        // Check for changes in data
                        //
                        if( isset($field['old_value']) && $field['old_value'] != $field['value'] ) {
                            $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.forms.data', $field['data_id'], array(
                                'data' => is_array($field['value']) ? json_encode($field['value']) : $field['value'],
                                ), 0x04);
                            if( $rc['stat'] != 'ok' ) {
                                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.75', 'msg'=>'Unable to add data', 'err'=>$rc['err']));
                            }
                            if( $field['ftype'] == 'image' ) {
                                $image_urls["{$field['id']}"] = $request['api_url'] . "/ciniki/forms/submissionImage/" . $form['id'] . '/' . $form['submission_id'] . '/' . $field['value'];
                            }
                        }
                    } elseif( isset($field['value']) ) {
                        // 
                        // No previous data saved, add new data
                        //
                        $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.forms.data', array(
                            'submission_id' => $form['submission_id'],
                            'field_id' => $field['id'],
                            'repeat_num' => 1,
                            'data' => is_array($field['value']) ? json_encode($field['value']) : $field['value'],
                            ), 0x04);
                        if( $rc['stat'] != 'ok' ) {
                            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.73', 'msg'=>'Unable to add data', 'err'=>$rc['err']));
                        }
                        if( $field['ftype'] == 'image' ) {
                            $image_urls["{$field['id']}"] = $request['api_url'] . "/ciniki/forms/submissionImage/" . $form['id'] . '/' . $form['submission_id'] . '/' . $field['value'];
                        }
                    }
                }
            }
        }
    }
   
    $rsp = array('stat'=>'ok');
    if( isset($api_args) ) {
        $rsp['api_args'] = $api_args;
    }
    
    if( isset($image_urls) && count($image_urls) > 0 ) {
        $rsp['image_urls'] = $image_urls;
    }

    error_log(print_r($rsp,true));

    return $rsp;
}
?>
