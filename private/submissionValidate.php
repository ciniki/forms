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
                            elseif( $field['ftype'] == 'address' ) {
                                $missing = '';
                                if( !isset($field['values'][$i]['address1']) || $field['values'][$i]['address1'] == '' ) {
                                    $missing .= ($missing != '' ? ', ' : '') . 'Line 1';
                                }
                                if( !isset($field['values'][$i]['city']) || $field['values'][$i]['city'] == '' ) {
                                    $missing .= ($missing != '' ? ', ' : '') . 'City';
                                }
                                if( !isset($field['values'][$i]['province']) || $field['values'][$i]['province'] == '' ) {
                                    $missing .= ($missing != '' ? ', ' : '') . 'Province/State';
                                }
                                if( !isset($field['values'][$i]['postal']) || $field['values'][$i]['postal'] == '' ) {
                                    $missing .= ($missing != '' ? ', ' : '') . 'Postal/Zip Code';
                                }
                                if( $missing != '' ) {
                                    $problems["{$field['id']}"] = $section['label'] . ' - ' . $field['label'] . ': Missing ' . $missing;
                                }
                            }
                            elseif( !isset($field['values'][$i]) || $field['values'][$i] == '' ) {
                                $problems["{$field['id']}-{$i}"] = $section['label'] . ' - ' . $field['label'] . ': Missing';
                            }
                        }
                        //
                        // Check textareas for number of words
                        //
                        elseif( $field['ftype'] == 'textarea' 
                            && isset($field['max-words']) && $field['max-words'] > 0 
                            && isset($field['values'][$i]) 
                            && str_word_count($field['values'][$i]) > $field['max-words']
                            ) {
                            $problems["{$field['id']}"] = $section['label'] . ' - ' . $field['label'] . ': Limit of ' . $field['max-words'] . ' words exceeded.';
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
                        elseif( $field['ftype'] == 'address' ) {
                            $missing = '';
                            if( !isset($field['value']['address1']) || $field['value']['address1'] == '' ) {
                                $missing .= ($missing != '' ? ', ' : '') . 'Line 1';
                            }
                            if( !isset($field['value']['city']) || $field['value']['city'] == '' ) {
                                $missing .= ($missing != '' ? ', ' : '') . 'City';
                            }
                            if( !isset($field['value']['province']) || $field['value']['province'] == '' ) {
                                $missing .= ($missing != '' ? ', ' : '') . 'Province/State';
                            }
                            if( !isset($field['value']['postal']) || $field['value']['postal'] == '' ) {
                                $missing .= ($missing != '' ? ', ' : '') . 'Postal/Zip Code';
                            }
                            if( $missing != '' ) {
                                $problems["{$field['id']}"] = $section['label'] . ' - ' . $field['label'] . ': Missing ' . $missing;
                            }
                        }
                        elseif( !isset($field['value']) || $field['value'] == '' ) {
                            $problems["{$field['id']}"] = $section['label'] . ' - ' . $field['label'] . ': Missing';
                        }
                    }
                    //
                    // Check textareas for number of words
                    //
                    elseif( $field['ftype'] == 'textarea' 
                        && isset($field['max-words']) && $field['max-words'] > 0 
                        && isset($field['value']) 
                        && str_word_count($field['value']) > $field['max-words']
                        ) {
                        $problems["{$field['id']}"] = $section['label'] . ' - ' . $field['label'] . ': Limit of ' . $field['max-words'] . ' words exceeded.';
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
