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
function ciniki_forms_wng_submissionImage(&$ciniki, $tnid, $request) {
    
    //
    // Make sure customer is logged in
    //
    if( !isset($request['session']['customer']['id']) || $request['session']['customer']['id'] <= 0 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.78', 'msg'=>'Not signed in'));
    }
    if( !isset($request['uri_split'][($request['cur_uri_pos']+2)]) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.79', 'msg'=>'No image specified'));
    }
    $form_id = $request['uri_split'][$request['cur_uri_pos']];
    $submission_id = $request['uri_split'][($request['cur_uri_pos']+1)];
    $image_id = $request['uri_split'][($request['cur_uri_pos']+2)];
    
    //
    // Load the form
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'forms', 'wng', 'formLoad');
    $rc = ciniki_forms_wng_formLoad($ciniki, $tnid, $request, $form_id, $request['session']['customer']['id']);
    if( $rc['stat'] == 'noauth' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.80', 'msg'=>'Not signed in'));
    }
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.81', 'msg'=>'Unable to load form', 'err'=>$rc['err']));
    }
    $form = $rc['form'];
    $form['submission_id'] = $submission_id;
        
    //
    // Load the existing submission
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'forms', 'wng', 'submissionLoad');
    $rc = ciniki_forms_wng_submissionLoad($ciniki, $tnid, $request, $form);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.82', 'msg'=>'', 'err'=>$rc['err']));
    }

    //
    // Find the requested image id
    //
    if( isset($form['sections']) ) {
        foreach($form['sections'] as $sid => $section) {
            if( isset($section['fields']) && isset($section['flags']) && ($section['flags']&0x01) == 0x01 ) {
                for($i = 1; $i <= $section['max_repeats']; $i++) {
                    foreach($section['fields'] as $fid => $field) {
                        //
                        // Check if the image matches
                        //
                        if( $field['ftype'] == 'image' && isset($field['values'][$i]) && $field['values'][$i] == $image_id ) {
                            ciniki_core_loadMethod($ciniki, 'ciniki', 'images', 'private', 'loadCacheOriginal');
                            $rc = ciniki_images_loadCacheOriginal($ciniki, $tnid, $image_id, 600, 600);
                            if( $rc['stat'] == 'ok' ) {
                                $image = $rc['image'];
                                $last_updated = $rc['last_updated'];
                            }
                        }
                    }
                }
            }
            elseif( isset($section['fields']) ) {
                foreach($section['fields'] as $fid => $field) {
                    //
                    // Check if the image matches
                    //
                    if( $field['ftype'] == 'image' && isset($field['value']) && $field['value'] == $image_id ) {
                        ciniki_core_loadMethod($ciniki, 'ciniki', 'images', 'private', 'loadCacheOriginal');
                        $rc = ciniki_images_loadCacheOriginal($ciniki, $tnid, $image_id, 600, 600);
                        if( $rc['stat'] == 'ok' ) {
                            $image = $rc['image'];
                            $last_updated = $rc['last_updated'];
                        }
                    }
                }
            }
        }
    }

    if( !isset($image) ) {
        $img_fname = $request['site']['cache_dir'] . '/theme/noimage_240.png';
        $img = new Imagick($img_fname);
        if( $img != null ) {
            header('Last-Modified: ' . gmdate('D, d M Y H:i:s', filemtime($img_fname)) . ' GMT', true, 200);
            header("Content-type: image/jpeg"); 

            echo $img->getImageBlob();
            return array('stat'=>'exit');
        }
    }

    //
    // Return image not found
    //
    if( isset($image) ) {
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $last_updated) . ' GMT', true, 200);
        header("Content-type: image/jpeg"); 

        echo $image;
        return array('stat'=>'exit');
    }

    return array('stat'=>'ok');
}
?>
