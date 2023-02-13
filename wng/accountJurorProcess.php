<?php
//
// Description
// -----------
// This function will process the juror voting for forms.
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_forms_wng_accountJurorProcess(&$ciniki, $tnid, &$request, $item) {

    $blocks = array();

    if( !isset($item['ref']) ) {
        return array('stat'=>'ok', 'blocks'=>array(array(
            'type' => 'msg', 
            'level' => 'error',
            'content' => "Request error, please contact us for help.."
            )));
    }

    if( !isset($request['session']['customer']['id']) || $request['session']['customer']['id'] <= 0 ) {
        return array('stat'=>'ok', 'blocks'=>array(array(
            'type' => 'msg', 
            'level' => 'error',
            'content' => "You must be logged in to vote."
            )));
    }

    if( !isset($request['uri_split'][2]) ) {
        return array('stat'=>'ok', 'blocks'=>array(array(
            'type' => 'msg', 
            'level' => 'error',
            'content' => "Invalid request, no form requested."
            )));
    }
    $form_permalink = $request['uri_split'][2];

    $base_url = '/' . join('/', $request['uri_split']);

    //
    // Load the form
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'forms', 'wng', 'jurorFormLoad');
    $rc = ciniki_forms_wng_jurorFormLoad($ciniki, $tnid, $request, $form_permalink, $request['session']['customer']['id']);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'ok', 'blocks'=>array(array(
            'type' => 'msg', 
            'level' => 'error',
            'content' => "The form you requested is no longer available or is not allowing voting at this time."
            )));
    }
    $form = $rc['form'];

    //
    // Blocks title is reset below when showing a submission
    //
    $blocks[] = array(
        'type' => 'title', 
        'title' => $form['name'],
        );

    if( !isset($form['submissions']) || count($form['submissions']) == 0 ) {
        $blocks[] = array(
            'type' => 'msg', 
            'level' => 'error',
            'content' => "The form you requested is no longer available or is not allowing voting at this time."
            );
        return array('stat'=>'ok', 'blocks'=>$blocks);
    }

    //
    // Check if vote was submitted
    //
    if( isset($_POST['f-number']) && isset($_POST['f-vote']) ) {
        if( !isset($form['submissions'][$_POST['f-number']]) ) {
            $blocks[] = array(
                'type' => 'msg', 
                'level' => 'error',
                'content' => "The submission you request does not exist."
                );
            return array('stat'=>'ok', 'blocks'=>$blocks);
        }

        //
        // Show the submission
        //
        $submission = $form['submissions'][$_POST['f-number']];

        //
        // Add or update vote
        //
        if( isset($_POST['f-vote']) && $_POST['f-vote'] == 'No' ) {
            $_POST['f-vote'] = 1;
        } elseif( isset($_POST['f-vote']) && $_POST['f-vote'] == 'Maybe' ) {
            $_POST['f-vote'] = 2;
        } elseif( isset($_POST['f-vote']) && $_POST['f-vote'] == 'Yes' ) {
            $_POST['f-vote'] = 3;
        }
        if( $submission['vote_id'] == 0 ) {
            $add_args = array(
                'submission_id' => $submission['id'],
                'juror_id' => $form['juror_id'],
                );
            if( isset($_POST['f-notes']) && $_POST['f-notes'] != $submission['notes'] ) {
                $add_args['notes'] = $_POST['f-notes'];
            } else {
                $add_args['notes'] = '';
            }
            if( isset($_POST['f-vote']) && $_POST['f-vote'] != $submission['vote'] 
                && ($_POST['f-vote'] == 1 || $_POST['f-vote'] == 2 || $_POST['f-vote'] == 3)
                ) {
                $add_args['vote'] = $_POST['f-vote'];
            } else {
                $add_args['vote'] = 0;
            }
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
            $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.forms.vote', $add_args, 0x04);
            if( $rc['stat'] != 'ok' ) {
                error_log("ERR (ciniki.forms): " . print_r($rc, true));
                $blocks[] = array(
                    'type' => 'msg', 
                    'level' => 'error',
                    'content' => "Unable to add vote, please try again or contact us for help.",
                    );
                return array('stat'=>'ok', 'blocks'=>$blocks);
            }
        
        } else {
            $update_args = array();
            if( isset($_POST['f-notes']) && $_POST['f-notes'] != $submission['notes'] ) {
                $update_args['notes'] = $_POST['f-notes'];
            }
            if( isset($_POST['f-vote']) && $_POST['f-vote'] != $submission['vote'] 
                && $_POST['f-vote'] == 1 || $_POST['f-vote'] == 2 || $_POST['f-vote'] == 3 
                ) {
                $update_args['vote'] = $_POST['f-vote'];
            }
            if( count($update_args) > 0 ) {
                ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
                $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.forms.vote', $submission['vote_id'], $update_args, 0x04);
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.157', 'msg'=>'Unable to update the vote', 'err'=>$rc['err']));
                }
            }
        }
        
        //
        // Redirect to submission list
        //
        header("Location: {$request['base_url']}{$base_url}");
        return array('stat'=>'exit');
    }

    //
    // Check if submission specified
    //
    if( isset($_GET['s']) ) {
        if( !isset($form['submissions'][$_GET['s']]) ) {
            $blocks[] = array(
                'type' => 'msg', 
                'level' => 'error',
                'content' => "The submission you request does not exist."
                );
            return array('stat'=>'ok', 'blocks'=>$blocks);
        }

        //
        // Show the submission
        //
        $submission = $form['submissions'][$_GET['s']];

        //
        // Load submission data
        //
        $strsql = "SELECT data.id, "
            . "data.field_id, "
            . "data.repeat_num, "
            . "data.data "
            . "FROM ciniki_form_data AS data "
            . "WHERE data.submission_id = '" . ciniki_core_dbQuote($ciniki, $submission['id']) . "' "
            . "AND data.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "ORDER BY data.field_id, data.repeat_num "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
        $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.forms', array(
            array('container'=>'data', 'fname'=>'field_id', 
                'fields'=>array('id'=>'field_id', 'id', 'data'),
                ),
            array('container'=>'repeats', 'fname'=>'repeat_num', 
                'fields'=>array('id'=>'repeat_num', 'id', 'data'),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            $blocks[] = array(
                'type' => 'msg', 
                'level' => 'error',
                'content' => "Unable to load the submission."
                );
            return array('stat'=>'ok', 'blocks'=>$blocks);
        }
        $data = isset($rc['data']) ? $rc['data'] : array();

        $blocks = array();
        $blocks[] = array(
            'type' => 'title', 
            'title' => $form['name'],
            );
        foreach($form['sections'] as $section) {

            $repeats = 1;
            if( ($section['flags']&0x01) == 0x01 ) {
                $repeats = $section['max_repeats'];
            }


            for($repeat = 1; $repeat <= $repeats; $repeat++) {
//                if( $repeats > 1 && 
                $label = $section['label'];
                if( $repeats > 1 ) {
                    if( $section['repeat_prefix'] != '' ) {
                        $label = $section['repeat_prefix'] . ' ' . $repeat;
                    } else {
                        $label .= ' ' . $repeat;
                    }
                }

                $fields = array();
                if( isset($section['fields']) ) {
                    foreach($section['fields'] as $field) {
                        if( $field['ftype'] == 'break' ) {
                            if( count($fields) > 0 ) {
                                $blocks[] = array(
                                    'type' => 'definitiontable',
                                    'title' => $label,
                                    'class' => 'limit-width center',
                                    'data' => $fields,
                                    );
                                $fields = array();
                                $label = '';
                            }
                            continue;
                        } 
                        
                        //
                        // Add the field
                        //
                        $f = array('label'=>$field['label'], 'value'=>'');
                        if( $repeats > 1 ) {
                            if( isset($data[$field['id']]['repeats'][$repeat]['data']) ) {
                                $f['value'] = $data[$field['id']]['repeats'][$repeat]['data'];
                            }
                        } elseif( isset($data[$field['id']]['data']) ) {
                            $f['value'] = $data[$field['id']]['data'];
                        }
                        //
                        // Format field
                        //
                        if( $field['ftype'] == 'address' && $f['value'] != '' ) {
                            $addr = json_decode($f['value'], true);
                            $f['value'] = '';
                            if( isset($addr['address1']) && $addr['address1'] != '' ) {
                                $f['value'] .= ($f['value'] != '' ? '<br/>' : '') . $addr['address1'];
                            }
                            if( isset($addr['address2']) && $addr['address2'] != '' ) {
                                $f['value'] .= ($f['value'] != '' ? '<br/>' : '') . $addr['address2'];
                            }
                            $city = '';
                            if( isset($addr['city']) && $addr['city'] != '' ) {
                                $city .= ($city != '' ? ', ' : '') . $addr['city'];
                            }
                            if( isset($addr['province']) && $addr['province'] != '' ) {
                                $city .= ($city != '' ? ', ' : '') . $addr['province'];
                            }
                            if( isset($addr['postal']) && $addr['postal'] != '' ) {
                                $city .= ($city != '' ? '  ' : '') . $addr['postal'];
                            }
                            if( $city != '' ) {
                                $f['value'] .= ($f['value'] != '' ? '<br/>' : '') . $city;
                            }
                            if( isset($addr['country']) && $addr['country'] != '' ) {
                                $f['value'] .= ($f['value'] != '' ? '<br/>' : '') . $addr['country'];
                            }
                        }
                        elseif( $field['ftype'] == 'checkbox' ) {
                            if( $f['value'] == 'on' ) {
                                $f['value'] = 'Yes';
                            } else {
                                $f['value'] = 'No';
                            }
                        }
                        elseif( $field['ftype'] == 'url' && $f['value'] != '' ) {
                            $f['value'] = "<a target='_blank' href='{$f['value']}'>{$f['value']}</a>";
                        }
                        elseif( $field['ftype'] == 'image' && $f['value'] != '' && $f['value'] > 0 ) {
                            if( isset($_GET['i']) && $_GET['i'] == $f['value'] ) {
                                ciniki_core_loadMethod($ciniki, 'ciniki', 'images', 'hooks', 'loadOriginal');
                                $rc = ciniki_images_hooks_loadOriginal($ciniki, $tnid, array(
                                    'image_id' => $f['value'],
                                    ));
                                if( $rc['stat'] != 'ok' ) {
                                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.155', 'msg'=>'Unable to load image', 'err'=>$rc['err']));
                                }
                                $image = $rc['image'];
                                if( $image->getImageHeight() > 600 ) {
                                    $image->scaleImage(0, 600);
                                }
                                header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $rc['last_updated']) . ' GMT', true, 200);
                                $format = strtolower($image->getImageFormat());
                                if( $format == 'png' ) {
                                    header("Content-type: image/png"); 
                                } else {
                                    header("Content-type: image/jpeg"); 
                                }
                                echo $image->getImageBlob();
                                return array('stat'=>'exit');
                            }
                            $f['value'] = "<img src='?s={$submission['number']}&i={$f['value']}'/>";
                        }
                        $f['class'] = $field['ftype'];
                        $fields[] = $f;
                    }
                }

                //
                // Add the table for the section
                //
                if( count($fields) > 0 ) {
                    $blocks[] = array(
                        'type' => 'definitiontable',
                        'title' => $label,
                        'class' => 'limit-width center folding',
                        'data' => $fields,
                        );
                }
            }
        }

        //
        // Add voting buttons
        //
        //$blocks[] = array('type'=>'html', 'html'=>'<pre>' . print_r($request, true) . '</pre>');
        if( $submission['vote'] == 1 ) {
            $submission['vote'] = 'No';
        } elseif( $submission['vote'] == 2 ) {
            $submission['vote'] = 'Maybe';
        } elseif( $submission['vote'] == 3 ) {
            $submission['vote'] = 'Yes';
        }
        $blocks[] = array(
            'type' => 'form',
            'fields' => array(
                array(
                    'id' => 'number',
                    'ftype' => 'hidden',
                    'value' => $submission['number'],
                    ),
                array(
                    'id' => 'notes',
                    'ftype' => 'textarea',
                    'label' => 'Your Notes',
                    'value' => $submission['notes'],
                    ),
                array(
                    'id' => 'vote',
                    'ftype' => 'radio',
                    'label' => 'Your Vote',
                    'value' => $submission['vote'],
                    'option-1' => 'No',
                    'option-2' => 'Maybe',
                    'option-3' => 'Yes',
                    ),
                ),
            'submit-label' => 'Submit Vote',
            'cancel-label' => 'Back',
            );

    } 

    //
    // No form show the list of submissions
    //
    else {
        //
        // Add url to open submission and vote
        //
        foreach($form['submissions'] as $sid => $s) {
            $form['submissions'][$sid]['url'] = "<a class='button' href='?s={$s['number']}'>View</a>";
        }
        $blocks[] = array(
            'type' => 'table',
            'headers' => 'yes',
            'class' => 'limit-width center limit-width-40',
            'columns' => array(
                array(
                    'label' => 'Submission #',
                    'field' => 'label',
                    ),
                array(
                    'label' => 'Vote',
                    'class' => 'aligncenter',
                    'field' => 'vote_text',
                    ),
                array(
                    'label' => '',
                    'class' => 'alignright',
                    'field' => 'url',
                    ),
                ),
            'rows' => $form['submissions'],
            );
    }

    return array('stat'=>'ok', 'blocks'=>$blocks);
}
?>
