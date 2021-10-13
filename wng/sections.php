<?php
//
// Description
// -----------
// Return the list of sections available from the forms module
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_forms_wng_sections(&$ciniki, $tnid, $args) {

    //
    // Check to make sure forms module is enabled
    //
    if( !isset($ciniki['tenant']['modules']['ciniki.forms']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.34', 'msg'=>'Module not enabled'));
    }

    $sections = array();

    //
    // Get the list of active forms
    //
    $strsql = "SELECT id, name "
        . "FROM ciniki_forms "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND status = 50 "
        . "AND (dt_start = '' OR dt_start = '0000-00-00 00:00:00' OR dt_start <= NOW()) "
        . "AND (dt_end >= NOW() OR dt_start = '0000-00-00 00:00:00' OR dt_end = '') "
        . "ORDER BY name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.forms', array(
        array('container'=>'forms', 'fname'=>'id', 'fields'=>array('id', 'name')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.30', 'msg'=>'Unable to load forms', 'err'=>$rc['err']));
    }
    $forms = isset($rc['forms']) ? $rc['forms'] : array();

    //
    // The latest forms section
    //
    $sections['ciniki.forms.form'] = array(
        'name' => 'Individual Form',
        'module' => 'Forms',
        'settings' => array(
            'form-id' => array('label'=>'form', 'type'=>'select', 'idnames'=>'yes', 'options'=>$forms),
            ),
        );
/*    $sections['ciniki.forms.list'] = array(
        'name' => 'List',
        'module' => 'Forms',
        'settings' => array(
            'title' => array('label'=>'Title', 'type'=>'text'),
            'button-text' => array('label'=>'Link Text', 'type'=>'text'),
            'button-class' => array('label'=>'Link Type', 'type'=>'toggle', 'default'=>'button', 
                'toggles'=>array(
                    'button' => 'Button',
                    'link' => 'Link',
                )),
            ),
        ); */

    return array('stat'=>'ok', 'sections'=>$sections);
}
?>
