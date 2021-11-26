<?php
//
// Description
// -----------
// This function will return the report details for a requested report block.
//
// Arguments
// ---------
// ciniki:
// tnid:     The ID of the tenant.
// args:            The possible arguments for.
//
//
// Returns
// -------
//
function ciniki_forms_reporting_block(&$ciniki, $tnid, $args) {

    //
    // Check to make sure the module is enabled
    //
    if( !isset($ciniki['tenant']['modules']['ciniki.forms']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.156', 'msg'=>"That report is not available."));
    }

    //
    // Check to make sure the report is specified
    //
    if( !isset($args['block_ref']) || !isset($args['options']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.167', 'msg'=>"No block specified."));
    }

    //
    // The array to store the report data
    //

    //
    // Return the list of reports for the tenant
    //
    if( $args['block_ref'] == 'ciniki.forms.newsubmissions' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'forms', 'reporting', 'blockNewSubmissions');
        return ciniki_forms_reporting_blockNewSubmissions($ciniki, $tnid, $args['options']);
    } elseif( $args['block_ref'] == 'ciniki.forms.votes' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'forms', 'reporting', 'blockVotes');
        return ciniki_forms_reporting_blockVotes($ciniki, $tnid, $args['options']);
    }

    return array('stat'=>'ok');
}
?>
