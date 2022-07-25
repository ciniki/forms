<?php
//
// Description
// -----------
// This function will apply the defaults for a form
//
// Arguments
// ---------
// ciniki:
// settings:        The web settings structure.
// tnid:     The ID of the tenant to get post for.
//
// args:            The possible arguments for posts
//
//
// Returns
// -------
//
function ciniki_forms_formDefaultsApply(&$ciniki, $tnid, $form) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');

    if( isset($form['sections']) ) {
        foreach($form['sections'] as $sid => $section) {
            if( isset($section['fields']) ) {
                foreach($section['fields'] as $fid => $field) {
                    //
                    // Check if default value exists
                    //
                    if( isset($field['defaults']) && ($section['flags']&0x01) == 0x01 ) {
                        // 
                        // Apply the repeat defaults
                        //
                        for($i = 1; $i <= $section['max_repeats']; $i++ ) {
                            $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.forms.data', array(
                                'submission_id' => $form['submission_id'],
                                'field_id' => $field['id'],
                                'repeat_num' => $i,
                                'data' => isset($field['defaults'][$i]) ? (is_array($field['defaults'][$i]) ? json_encode($field['defaults'][$i]) : $field['defaults'][$i]) : '',
                                ), 0x04);
                            if( $rc['stat'] != 'ok' ) {
                                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.183', 'msg'=>'Unable to add data', 'err'=>$rc['err']));
                            }
                        }
                    }
                    elseif( isset($field['default']) ) {
                        if( ($section['flags']&0x01) == 0x01 ) {
                            // 
                            // Apply the default to each repeat
                            //
                            for($i = 1; $i <= $section['max_repeats']; $i++ ) {
                                $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.forms.data', array(
                                    'submission_id' => $form['submission_id'],
                                    'field_id' => $field['id'],
                                    'repeat_num' => $i,
                                    'data' => is_array($field['default']) ? json_encode($field['default']) : $field['default'],
                                    ), 0x04);
                                if( $rc['stat'] != 'ok' ) {
                                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.178', 'msg'=>'Unable to add data', 'err'=>$rc['err']));
                                }
                            }
                        } else {
                            $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.forms.data', array(
                                'submission_id' => $form['submission_id'],
                                'field_id' => $field['id'],
                                'repeat_num' => 1,
                                'data' => is_array($field['default']) ? json_encode($field['default']) : $field['default'],
                                ), 0x04);
                            if( $rc['stat'] != 'ok' ) {
                                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.179', 'msg'=>'Unable to add data', 'err'=>$rc['err']));
                            }
                        }
                    }
                }
            }
        }
    } 

    return array('stat'=>'ok');
}
?>
