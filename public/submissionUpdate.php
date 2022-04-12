<?php
//
// Description
// ===========
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_forms_submissionUpdate(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'submission_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Submission'),
        'form_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Form'),
        'object'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Object'),
        'object_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Object ID'),
        'customer_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Customer'),
        'invoice_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Invoice'),
        'status'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Status'),
        'label'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Label'),
        'dt_terms_accepted'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'datetimetoutc', 'name'=>'Terms Accepted'),
        'dt_last_save'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'datetimetoutc', 'name'=>'Last Saved'),
        'dt_last_submitted'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'datetimetoutc', 'name'=>'Last Submitted'),
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
    $rc = ciniki_forms_checkAccess($ciniki, $args['tnid'], 'ciniki.forms.submissionUpdate');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load the submission
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'forms', 'private', 'submissionLoad');
    $rc = ciniki_forms_submissionLoad($ciniki, $args['tnid'], $args['submission_id']);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.184', 'msg'=>'Invalid submission', 'err'=>$rc['err']));
    }
    $form = $rc['form'];
    $submission = isset($rc['form']['submission']) ? $rc['form']['submission'] : array();

    //
    // Start transaction
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.forms');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Update the Submission in the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.forms.submission', $args['submission_id'], $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.forms');
        return $rc;
    }

    //
    // Check for any field updates
    //
    foreach($form['sections'] as $sid => $section) {
        
        $repeats = 1;
        if( ($section['flags']&0x01) == 0x01 ) {
            $repeats = $section['max_repeats'];
        }
        if( isset($section['fields']) ) {
            for($repeat = 1; $repeat <= $repeats; $repeat++) {
                foreach($section['fields'] as $fid => $field) {
                    if( $field['ftype'] == 'content' ) {
                        continue;
                    }
                    $arg = 'd_' . $field['id'];
                    if( $repeats > 1 ) {
                        $arg .= '-' . $repeat;
                    }

                    //
                    // Handle address arguments
                    //
                    if( $field['ftype'] == 'address' ) {
                        if( isset($submission['data'][$field['id']]['repeats'][$repeat]['data']) ) {
                            $address = json_decode($submission['data'][$field['id']]['repeats'][$repeat]['data'], true);
                        } else {
                            $address = array(
                                'address1' => '',
                                'address2' => '',
                                'city' => '',
                                'province' => '',
                                'postal' => '',
                                'country' => '',
                                );
                        }
                        $found = 'no';
                        if( isset($ciniki['request']['args'][$arg . '-address1']) ) {
                            $found = 'yes';
                            $address['address1'] = trim($ciniki['request']['args'][$arg . '-address1']);
                        }
                        if( isset($ciniki['request']['args'][$arg . '-address2']) ) {
                            $found = 'yes';
                            $address['address2'] = trim($ciniki['request']['args'][$arg . '-address2']);
                        }
                        if( isset($ciniki['request']['args'][$arg . '-city']) ) {
                            $found = 'yes';
                            $address['city'] = trim($ciniki['request']['args'][$arg . '-city']);
                        }
                        if( isset($ciniki['request']['args'][$arg . '-province']) ) {
                            $found = 'yes';
                            $address['province'] = trim($ciniki['request']['args'][$arg . '-province']);
                        }
                        if( isset($ciniki['request']['args'][$arg . '-postal']) ) {
                            $found = 'yes';
                            $address['postal'] = trim($ciniki['request']['args'][$arg . '-postal']);
                        }
                        if( isset($ciniki['request']['args'][$arg . '-country']) ) {
                            $found = 'yes';
                            $address['country'] = trim($ciniki['request']['args'][$arg . '-country']);
                        }
                        //
                        // Only set the argument if one of the variables passed, otherwise ignore
                        //
                        if( $found == 'yes' ) {
                            $ciniki['request']['args'][$arg] = json_encode($address);
                        }
                    }
                    elseif( $field['ftype'] == 'radio' 
                        && isset($ciniki['request']['args'][$arg]) 
                        && $ciniki['request']['args'][$arg] == '0' 
                        ) {
                        $ciniki['request']['args'][$arg] = '';
                    }

                    //
                    // Check if argument passed to update
                    //
                    if( isset($ciniki['request']['args'][$arg]) ) {
                        $new_data = trim($ciniki['request']['args'][$arg]);

                        //
                        // Add the data if not already exists
                        //
                        if( !isset($submission['data'][$field['id']]['repeats'][$repeat]['data']) ) {
                            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
                            $rc = ciniki_core_objectAdd($ciniki, $args['tnid'], 'ciniki.forms.data', array(
                                'submission_id' => $args['submission_id'],
                                'field_id' => $field['id'],
                                'repeat_num' => $repeat,
                                'data' => $new_data,
                                ), 0x04);
                            if( $rc['stat'] != 'ok' ) {
                                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.185', 'msg'=>'Unable to update the data', 'err'=>$rc['err']));
                            }
                             
                        } 
                        //
                        // Check if this is an update
                        //
                        elseif( isset($submission['data'][$field['id']]['repeats'][$repeat]['data_id']) 
                            && $submission['data'][$field['id']]['repeats'][$repeat]['data'] != $new_data
                            ) {
                            $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.forms.data', 
                                $submission['data'][$field['id']]['repeats'][$repeat]['data_id'], 
                                array('data' => $new_data), 
                                0x04);
                            if( $rc['stat'] != 'ok' ) {
                                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.185', 'msg'=>'Unable to update the data', 'err'=>$rc['err']));
                            }
                        }
                    }
                }
            }
        }
    }

    //
    // Commit the transaction
    //
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.forms');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Update the last_change date in the tenant modules
    // Ignore the result, as we don't want to stop user updates if this fails.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'updateModuleChangeDate');
    ciniki_tenants_updateModuleChangeDate($ciniki, $args['tnid'], 'ciniki', 'forms');

    //
    // Update the web index if enabled
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'hookExec');
    ciniki_core_hookExec($ciniki, $args['tnid'], 'ciniki', 'web', 'indexObject', array('object'=>'ciniki.forms.submission', 'object_id'=>$args['submission_id']));

    return array('stat'=>'ok');
}
?>
