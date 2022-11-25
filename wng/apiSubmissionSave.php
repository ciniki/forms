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
function ciniki_forms_wng_apiSubmissionSave(&$ciniki, $tnid, $request) {
    
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');

    //
    // Make sure customer is logged in
    //
//    if( !isset($request['session']['customer']['id']) || $request['session']['customer']['id'] <= 0 ) {
//        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.62', 'msg'=>'Not signed in'));
//    }
    
//    if( !isset($request['args']['customer_id']) || $request['args']['customer_id'] <= 0 ) {
//        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.63', 'msg'=>'Not signed in'));
//    }
//    if( $request['args']['customer_id'] != $request['args']['customer_id'] ) {
//        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.64', 'msg'=>'Incorrect account'));
//    }

    //
    // Make sure the form id is specified
    //
    if( !isset($request['args']['form_id']) || $request['args']['form_id'] <= 0 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.65', 'msg'=>'No form specified'));
    }

    $customer_id = 0;
    if( isset($request['session']['customer']['id']) && $request['session']['customer']['id'] > 0 ) {
        $customer_id = $request['session']['customer']['id'];
    }

    //
    // Load the form
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'forms', 'wng', 'formLoad');
    $rc = ciniki_forms_wng_formLoad($ciniki, $tnid, $request, $request['args']['form_id'], $customer_id);
    if( $rc['stat'] == 'noauth' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.66', 'msg'=>'Not signed in'));
    }
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.67', 'msg'=>'Unable to load form', 'err'=>$rc['err']));
    }
    $form = $rc['form'];

    //
    // Check if submission id specified
    //
    if( isset($request['args']['submission_id']) ) {
        $form['submission_id'] = $request['args']['submission_id'];
    }
        
    //
    // Load the existing submission
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'forms', 'wng', 'submissionLoad');
    $rc = ciniki_forms_wng_submissionLoad($ciniki, $tnid, $request, $form);
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

    $dt_now = new DateTime('NOW', new DateTimezone('UTC'));

    //
    // Check if submission_id specified
    //
    $update_args = array();
    if( isset($form['submission_id']) && $form['submission_id'] > 0 ) {
        $update_args['dt_last_save'] = $dt_now->format('Y-m-d H:i:s');
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
                        } elseif( isset($field['values'][$i]) && is_numeric($field['id']) ) {
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
                    // Check terms of use have been checked or unchecked
                    //
                    if( $field['id'] == 'termsofuse' ) {
                        if( isset($field['old_value']) && $field['old_value'] != $field['value'] ) {
                            if( $field['value'] == 'off' ) {
                                $update_args['dt_terms_accepted'] = '';
                            } elseif( $field['value'] == 'on' ) {
                                $update_args['dt_terms_accepted'] = $dt_now->format('Y-m-d H:i:s');
                            }
                        }
                    }
                    //
                    // Check if submission field data exists
                    //
                    elseif( isset($field['data_id']) && $field['data_id'] > 0 ) {
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
                    } 
                    // 
                    // No previous data saved, add new data
                    //
                    elseif( isset($field['value']) && is_numeric($field['id']) ) {
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
    
    if( count($update_args) > 0 ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
        $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.forms.submission', $form['submission_id'], $update_args, 0x04);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.84', 'msg'=>'Unable to update the submission', 'err'=>$rc['err']));
        }
    }

    //
    // Check for any updates to submission label
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'forms', 'private', 'submissionLabelUpdate');
    $rc = ciniki_forms_submissionLabelUpdate($ciniki, $tnid, $form['submission_id']);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.175', 'msg'=>'Unable to update label', 'err'=>$rc['err']));
    }

    $rsp = array('stat'=>'ok');
    if( isset($api_args) ) {
        $rsp['api_args'] = $api_args;
    }
    
    if( isset($image_urls) && count($image_urls) > 0 ) {
        $rsp['image_urls'] = $image_urls;
    }

    return $rsp;
}
?>
