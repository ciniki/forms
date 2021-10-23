<?php
//
// Description
// -----------
// Check the for required fields and validate any input.
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_forms_submissionValidate(&$ciniki, $tnid, $form) {
    
    //
    // Check for any changes that need to be saved
    //
    $problems = array();
    if( isset($form['sections']) ) {
        foreach($form['sections'] as $sid => $section) {
            if( isset($section['fields']) && isset($section['flags']) && ($section['flags']&0x01) == 0x01 ) {
                for($i = 1; $i <= $section['max_repeats']; $i++) {
                    foreach($section['fields'] as $fid => $field) {
                        //
                        // Check if submission data found 
                        //
                        if( $i > $section['min_repeats'] ) {
                            break;
                        }
                        
                        if( isset($field['required']) && $field['required'] == 'yes' ) {
                            if( $field['ftype'] == 'checkbox' && $field['values'][$i] != 'on' ) {
                                $problems["{$field['id']}-{$i}"] = $section['label'] . ' - ' . $field['label'] . ': Missing';
                            }
                            elseif( $field['ftype'] == 'image' 
                                && (!isset($field['values'][$i]) || $field['values'][$i] == '' || $field['values'][$i] <= 0) 
                                ) {
                                $problems["{$field['id']}-{$i}"] = $section['label'] . ' - ' . $field['label'] . ': Missing image';
                            }
                            elseif( !isset($field['values'][$i]) || $field['values'][$i] == '' ) {
                                $problems["{$field['id']}-{$i}"] = $section['label'] . ' - ' . $field['label'] . ': Missing';
                            }
                        }
                    }
                }
            }
            elseif( isset($section['fields']) ) {
                foreach($section['fields'] as $fid => $field) {
                    //
                    // Ensure terms of use have been accepted
                    //
                    if( $field['id'] == 'termsofuse' ) {
                        if( !isset($field['value']) || $field['value'] != 'on' ) {
                            $problems["{$field['id']}"] = 'You must accept the terms of use';
                        }
                    }
                    //
                    // Check if other fields are required
                    //
                    elseif( isset($field['required']) && $field['required'] == 'yes' ) {
                        if( $field['ftype'] == 'checkbox' && (!isset($field['value']) || $field['value'] != 'on') ) {
                            $problems["{$field['id']}"] = $section['label'] . ' - ' . $field['label'] . ': Missing';
                        }
                        elseif( $field['ftype'] == 'image' 
                            && (!isset($field['value']) || $field['value'] == '' || $field['value'] <= 0) 
                            ) {
                            $problems["{$field['id']}"] = $section['label'] . ' - ' . $field['label'] . ': Missing image';
                        }
                        elseif( !isset($field['value']) || $field['value'] == '' ) {
                            $problems["{$field['id']}"] = $section['label'] . ' - ' . $field['label'] . ': Missing';
                        }

                    }
                }
            }
        }
    }
   
    //
    // If problems are found in the form, return list
    //
    if( count($problems) > 0 ) {
        return array('stat'=>'fail', 'problems'=>$problems, 'form'=>$form);
    }

    return array('stat'=>'ok');
}
?>
