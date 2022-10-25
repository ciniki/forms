<?php
//
// Description
// -----------
// Apply any changes via _POST variable to form.
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_forms_wng_formPOSTApply(&$ciniki, $tnid, $request, &$form) {

    //
    // Apply any posted updates to the form
    //
    if( isset($form['sections']) ) {
        foreach($form['sections'] as $sid => $section) {
            //
            // Process a repeatable section
            //
            if( isset($section['flags']) && ($section['flags']&0x01) == 0x01 ) {
                for($i = 1; $i <= $section['max_repeats']; $i++) {
                    foreach($section['fields'] as $fid => $field) {
                        //
                        // FIXME: Add other field types eg: address
                        //
                        $new_value = null;
                        if( $field['ftype'] == 'address' ) {
                            $new_value = isset($field['values'][$i]) ? $field['values'][$i] : array();
                            foreach(['address1', 'address2', 'city', 'province', 'postal', 'country'] as $subfield) {
                                if( isset($request['args']["f-{$field['id']}-{$subfield}"]) ) {
                                    $new_value[$subfield] = trim($request['args']["f-{$field['id']}-{$subfield}"]);
                                }
                            }
                        } 
                        elseif( $field['ftype'] == 'checkbox' && isset($request['args']["f-{$field['id']}-{$i}"]) ) {
                            $new_value = $request['args']["f-{$field['id']}-{$i}"] == 'on' ? 'on' : 'off';
                        }
                        elseif( $field['ftype'] == 'image' && isset($_FILES["f-{$field['id']}-{$i}"]) ) {
                            $file = $_FILES["f-{$field['id']}-{$i}"];
                            if( !isset($file['tmp_name']) || $file['tmp_name'] == '' ) {
                                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.163', 'problem'=>'other', 'msg'=>'There was an error uploading your image, please try again or contact us for help.'));
                            }
                            try {
                                $image = new Imagick($file['tmp_name']);
                            } catch(exception $e) {
                                error_log($e->getMessage());
                                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.164', 'problem'=>'other', 'msg'=>'There was an error uploading your image, please try again or contact us for help.'));
                            }
                            //
                            // Check image size
                            //
                            if( (isset($field['min-width']) && $field['min-width'] > 0 
                                    && $image->getImageWidth() < $field['min-width'])
                                || (isset($field['min-height']) && $field['min-height'] > 0 
                                    && $image->getImageHeight() < $field['min-height'])
                                ) {
                                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.148', 'problem'=>'tosmall', 'msg'=>'The image you selected does not meet the minimum size requirements. It must be at least ' . $field['min-width'] . ' pixels wide and ' . $field['min-height'] . ' pixels tall.'));
                            }
                            if( (isset($field['max-width']) && $field['max-width'] > 0 
                                    && $image->getImageWidth() > $field['max-width'])
                                || (isset($field['max-height']) && $field['max-height'] > 0 
                                    && $image->getImageHeight() > $field['max-height'])
                                ) {
                                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.149', 'problem'=>'tolarge', 'msg'=>'The image you selected is too large. It must be at no more than ' . $field['max-width'] . ' pixels wide and ' . $field['max-height'] . ' pixels tall.'));
                            }
                            ciniki_core_loadMethod($ciniki, 'ciniki', 'images', 'hooks', 'insertFromImagick');
                            $rc = ciniki_images_hooks_insertFromImagick($ciniki, $tnid, array(
                                'image' => $image,
                                'original_filename' => $file['name'],
                                ));
                            if( $rc['stat'] == 'ok' || $rc['stat'] == 'exists' ) {
                                $new_value = $rc['id'];
                            } else {
                                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.97', 'msg'=>'Unable to upload image', 'err'=>$rc['err']));
                            }
                        }
                        elseif( isset($request['args']["f-{$field['id']}-{$i}"]) ) {
                            $new_value = trim($request['args']["f-{$field['id']}-{$i}"]);
                        }
                        if( $new_value != null ) {
                            if( isset($field['values'][$i]) && $new_value != $field['values'][$i] ) {
                                $form['sections'][$sid]['fields'][$fid]['old_values'][$i] = $field['values'][$i];
                            }
                            $form['sections'][$sid]['fields'][$fid]['values'][$i] = $new_value;
                        }
                    }
                }
            }
            elseif( isset($section['fields']) ) {
                foreach($section['fields'] as $fid => $field) {
                    //
                    // Skip some fields
                    //
                    if( !isset($field['id']) ) {
                        continue;
                    }
                    if( $field['ftype'] == 'break' ) {
                        continue;
                    }
                    $new_value = null;
                    if( $field['ftype'] == 'address' ) {
                        $new_value = isset($field['value']) ? $field['value'] : array();
                        foreach(['address1', 'address2', 'city', 'province', 'postal', 'country'] as $subfield) {
                            if( isset($request['args']["f-{$field['id']}-{$subfield}"]) ) {
                                $new_value[$subfield] = trim($request['args']["f-{$field['id']}-{$subfield}"]);
                            }
                        }
                    } 
                    elseif( $field['ftype'] == 'checkbox' ) {
                        if( isset($request['args']["f-{$field['id']}"]) ) {
                            $new_value = $request['args']["f-{$field['id']}"] == 'on' ? 'on' : 'off';
                        }
                    }
                    elseif( $field['ftype'] == 'image' && isset($_FILES["f-{$field['id']}"]) ) {
                        $file = $_FILES["f-{$field['id']}"];
                        if( !isset($file['tmp_name']) || $file['tmp_name'] == '' ) {
                            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.165', 'problem'=>'other', 'msg'=>'There was an error uploading your image, please try again or contact us for help.'));
                        }
                        try {
                            $image = new Imagick($file['tmp_name']);
                        } catch(exception $e) {
                            error_log($e->getMessage());
                            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.166', 'problem'=>'other', 'msg'=>'There was an error uploading your image, please try again or contact us for help.'));
                        
                        }
                        //
                        // Check image size
                        //
                        if( (isset($field['min-width']) && $field['min-width'] > 0 
                                && $image->getImageWidth() < $field['min-width'])
                            || (isset($field['min-height']) && $field['min-height'] > 0 
                                && $image->getImageHeight() < $field['min-height'])
                            ) {
                            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.146', 'problem'=>'tosmall', 'msg'=>'The image you selected does not meet the minimum size requirements. It must be at least ' . $field['min-width'] . ' pixels wide and ' . $field['min-height'] . ' pixels tall.'));
                        }
                        if( (isset($field['max-width']) && $field['max-width'] > 0 
                                && $image->getImageWidth() > $field['max-width'])
                            || (isset($field['max-height']) && $field['max-height'] > 0 
                                && $image->getImageHeight() > $field['max-height'])
                            ) {
                            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.147', 'problem'=>'tolarge', 'msg'=>'The image you selected is too large. It must be at no more than ' . $field['max-width'] . ' pixels wide and ' . $field['max-height'] . ' pixels tall.'));
                        }
                        ciniki_core_loadMethod($ciniki, 'ciniki', 'images', 'hooks', 'insertFromImagick');
                        $rc = ciniki_images_hooks_insertFromImagick($ciniki, $tnid, array(
                            'image' => $image,
                            'original_filename' => $file['name'],
                            ));
                        if( $rc['stat'] == 'ok' || $rc['stat'] == 'exists' ) {
                            $new_value = $rc['id'];
                        } else {
                            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.76', 'msg'=>'Unable to upload image', 'err'=>$rc['err']));
                        }
                    } 
                    elseif( $field['ftype'] == 'termsofuse' ) {
                        if( isset($request['args']['termsofuse']) ) {
                            if( $request['args']['termsofuse'] == 'on' ) {
                                $new_value = $request['args']['termsofuse'];
                            } else {
                                $new_value = 'off';
                            }
                        }
                    }
                    elseif( isset($request['args']["f-{$field['id']}"]) ) {
                        $new_value = trim($request['args']["f-{$field['id']}"]);
                    }
                    // FIXME: Add other field type handlers
                    if( !is_null($new_value) ) {
                        if( isset($field['value']) && $new_value != $field['value'] ) {
                            $form['sections'][$sid]['fields'][$fid]['old_value'] = $field['value'];
                        }
                        $form['sections'][$sid]['fields'][$fid]['value'] = $new_value;
                    }
                }
            }
        }
    } 

    return array('stat'=>'ok');
}
?>
