<?php
//
// Description
// -----------
// This function will return the data for customer(s) to be displayed in the IFB display panel.
// The request might be for 1 individual, or multiple customer ids for a family.
//
// Arguments
// ---------
// ciniki:
// tnid:     The ID of the tenant to get forms for.
//
// Returns
// -------
//
function ciniki_forms_hooks_uiCustomersData($ciniki, $tnid, $args) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuoteIDs');

    //
    // Load the status maps for the text description of each status
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'forms', 'private', 'maps');
    $rc = ciniki_forms_maps($ciniki);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $maps = $rc['maps'];
    
    //
    // Default response
    //
    $rsp = array('stat'=>'ok', 'tabs'=>array());

    //
    // Get the list of registrations for the with latest first
    //
    $sections['ciniki.forms.submissions'] = array(
        'label' => 'Forms',
        'type' => 'simplegrid', 
        'num_cols' => 3,
        'headerValues' => array('Name', 'Form', 'Date', 'Status'),
        'cellClasses' => array('', '', 'multiline'),
        'noData' => 'No forms',
            'editApp' => array('app'=>'ciniki.forms.main', 'args'=>array('submission_id'=>'d.id;', 'source'=>'\'\'')),
        'cellValues' => array(
            '0' => "d.display_name",
            '1' => "d.name",
            '2' => "M.multiline(d.dt_last_submitted_display, d.status_text);",
            ),
        'data' => array(),
        );
    $strsql = "SELECT submissions.id, "
        . "customers.id AS customer_id, "
        . "customers.display_name, "
        . "forms.name, "
        . "submissions.dt_last_submitted, "
        . "IFNULL(DATE_FORMAT(submissions.dt_last_submitted, '%b %e, %Y'), '') AS dt_last_submitted_display, "
        . "submissions.status AS status_text "
        . "FROM ciniki_customers AS customers "
        . "INNER JOIN ciniki_form_submissions AS submissions ON ("
            . "customers.id = submissions.customer_id "
            . "AND submissions.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_forms AS forms ON ("
            . "submissions.form_id = forms.id "
            . "AND forms.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE customers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    if( isset($args['customer_id']) ) {
        $strsql .= "AND (customers.id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
            . "OR customers.parent_id  = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
            . ") ";
    } elseif( isset($args['customer_ids']) && count($args['customer_ids']) > 0 ) {
        $strsql .= "AND customers.id IN (" . ciniki_core_dbQuoteIDs($ciniki, $args['customer_ids']) . ") ";
    } else {
        return array('stat'=>'ok');
    }
    $strsql .= "ORDER BY submissions.dt_last_submitted DESC, customers.display_name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.customers', array(
        array('container'=>'registrations', 'fname'=>'id', 
            'fields'=>array('id', 'customer_id', 'display_name', 'name', 
                'dt_last_submitted', 'dt_last_submitted_display', 'status_text',
                ),
            'maps'=>array('status_text'=>$maps['submission']['status']),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['registrations']) ) {
        $sections['ciniki.forms.submissions']['data'] = isset($rc['registrations']) ? $rc['registrations'] : array();
        $rsp['tabs'][] = array(
            'id' => 'ciniki.forms.submissions',
            'label' => 'Forms',
            'priority' => 1000,
            'sections' => $sections,
            );
    }

    return $rsp;
}
?>
