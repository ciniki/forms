<?php
//
// Description
// -----------
// This function will return the list of available blocks to the ciniki.reporting module.
//
// Arguments
// ---------
// ciniki:
// tnid:     
// args:            The possible arguments for.
//
//
// Returns
// -------
//
function ciniki_forms_reporting_blocks(&$ciniki, $tnid, $args) {

    //
    // Check to make sure the module is enabled
    //
    if( !isset($ciniki['tenant']['modules']['ciniki.forms']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.158', 'msg'=>"I'm sorry, the block you requested does not exist."));
    }

    $blocks = array();

    $strsql = "SELECT DISTINCT type "
        . "FROM ciniki_forms "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND status < 90 "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.forms', array(
        array('container'=>'types', 'fname'=>'type', 'fields'=>array('id'=>'type', 'name'=>'type')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.169', 'msg'=>'Unable to load types', 'err'=>$rc['err']));
    }
    $types = isset($rc['types']) ? $rc['types'] : array();
    array_unshift($types, array('id'=>'0', 'name'=>'All Types'));

    //
    // Return the list of blocks for the tenant
    //
    $blocks['ciniki.forms.newsubmissions'] = array(
        'name'=>'New Submissions',
        'module' => 'Forms',
        'options'=>array(
            'days'=>array('label'=>'Number of Days', 'type'=>'text', 'size'=>'small', 'default'=>'7'),
            'months'=>array('label'=>'Number of Months', 'type'=>'text', 'size'=>'small', 'default'=>'0'),
            'type'=>array('label'=>'Form Type', 'type'=>'select', 'default'=>'0', 
                'complex_options'=>array('value'=>'id', 'name'=>'name'),
                'options'=>$types,
                ),
            ),
        );
    $blocks['ciniki.forms.votes'] = array(
        'name'=>'Votes',
        'module' => 'Forms',
        'options'=>array(
            'type'=>array('label'=>'Form Type', 'type'=>'select', 'default'=>'0', 
                'complex_options'=>array('value'=>'id', 'name'=>'name'),
                'options'=>$types,
                ),
            ),
        );

    return array('stat'=>'ok', 'blocks'=>$blocks);
}
?>
