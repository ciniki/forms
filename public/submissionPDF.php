<?php
//
// Description
// ===========
// This method will return the PDF of the submission.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the submission is attached to.
// submission_id:          The ID of the submission to get the details for.
//
// Returns
// -------
//
function ciniki_forms_submissionPDF($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'submission_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Submission'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'forms', 'private', 'checkAccess');
    $rc = ciniki_forms_checkAccess($ciniki, $args['tnid'], 'ciniki.forms.submissionPDF');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load tenant details
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'tenantDetails');
    $rc = ciniki_tenants_tenantDetails($ciniki, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['details']) && is_array($rc['details']) ) {   
        $tenant_details = $rc['details'];
    } else {
        $tenant_details = array();
    }

    //
    // Generate the PDF
    //
    $title = 'Submission';
    $filename = preg_replace('/[^a-zA-Z0-9_]/', '', preg_replace('/ /', '_', $title));
    ciniki_core_loadMethod($ciniki, 'ciniki', 'forms', 'templates', 'submissionsPDF');
    $rc = ciniki_forms_templates_submissionsPDF($ciniki, $args['tnid'], array(
        'tenant_details' => $tenant_details,
        'submission_ids' => array($args['submission_id']),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.190', 'msg'=>'Unable to generate PDF', 'err'=>$rc['err']));
    }
    if( isset($rc['pdf']) ) {
        $rc['pdf']->Output($filename . '.pdf', 'I');
    }

    return array('stat'=>'exit');
}
?>
