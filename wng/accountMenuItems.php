<?php
//
// Description
// -----------
// This function will check for an existing cart to load into the session
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_forms_wng_accountMenuItems($ciniki, $tnid, $request, $args) {

    $items = array();

    $settings = isset($request['site']['settings']) ? $request['site']['settings'] : array();
    $base_url = isset($args['base_url']) ? $args['base_url'] : '';

    //
    // Get the list of forms the logged in customer is a juror for
    //
    $strsql = "SELECT forms.id, "
        . "forms.name AS title, "
        . "forms.permalink "
        . "FROM ciniki_form_jurors AS jurors "
        . "INNER JOIN ciniki_forms AS forms ON ("
            . "jurors.form_id = forms.id "
            . "AND forms.status = 50 "          // Active form
            . "AND (forms.flags&0x30) = 0x30 "  // Juried form and voting open
            . "AND forms.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE jurors.customer_id = '" . ciniki_core_dbQuote($ciniki, $request['session']['customer']['id']) . "' "
        . "AND jurors.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.forms', array(
        array('container'=>'forms', 'fname'=>'id', 'fields'=>array('title', 'permalink')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.151', 'msg'=>'Unable to load forms', 'err'=>$rc['err']));
    }
    $forms = isset($rc['forms']) ? $rc['forms'] : array();
    foreach($forms as $fid => $form) {
        $forms[$fid]['ref'] = 'ciniki.forms.juror';
        $forms[$fid]['url'] = $base_url . '/forms/' . $form['permalink'];
    }

    if( count($forms) > 0 ) {
        $items[] = array(
            'title' => 'Jurying', 
            'priority' => 750, 
            'selected' => 'no',
            'items' => $forms,
            );
    }

    return array('stat'=>'ok', 'items'=>$items);
}
?>
