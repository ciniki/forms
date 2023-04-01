<?php
//
// Description
// -----------
// This function will check for juried forms for the logged in customer or in progress form submissions
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
            'selected' => isset($args['selected']) && $args['selected'] == 'forms' ? 'yes' : 'no',
            'items' => $forms,
            );
    }

    //
    // Get the list of active forms that are in progress for the customer
    //
    $strsql = "SELECT forms.id, "
        . "forms.name AS title, "
        . "forms.permalink, "
        . "submissions.uuid "
        . "FROM ciniki_form_submissions AS submissions "
        . "INNER JOIN ciniki_forms AS forms ON ("
            . "submissions.form_id = forms.id "
            . "AND forms.status = 50 "
            . "AND (forms.dt_end = '0000-00-00' OR forms.dt_end > NOW()) "
            . "AND forms.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE submissions.customer_id = '" . ciniki_core_dbQuote($ciniki, $request['session']['customer']['id']) . "' "
        . "AND submissions.status < 90 "
        . "AND submissions.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.forms', array(
        array('container'=>'forms', 'fname'=>'id', 'fields'=>array('id', 'title', 'permalink', 'uuid')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.151', 'msg'=>'Unable to load forms', 'err'=>$rc['err']));
    }
    $forms = isset($rc['forms']) ? $rc['forms'] : array();
    foreach($forms as $fid => $form) {
        $forms[$fid]['ref'] = 'ciniki.forms.submission';
        $forms[$fid]['url'] = $base_url . '/forms/' . $form['id'] . '/' . $form['uuid'];
    }

    if( count($forms) > 0 ) {
        $items[] = array(
            'title' => 'Forms in Progress', 
            'priority' => 750, 
            'selected' => isset($args['selected']) && $args['selected'] == 'forms' ? 'yes' : 'no',
            'items' => $forms,
            );
    }


    return array('stat'=>'ok', 'items'=>$items);
}
?>
