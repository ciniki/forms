<?php
//
// Description
// ===========
// This method will return all the information about an submission.
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
function ciniki_forms_submissionGet($ciniki) {
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
    $rc = ciniki_forms_checkAccess($ciniki, $args['tnid'], 'ciniki.forms.submissionGet');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'forms', 'private', 'submissionLoad');
    $rc = ciniki_forms_submissionLoad($ciniki, $args['tnid'], $args['submission_id']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['form']['submission']['fields']) ) {
        unset($rc['form']['submission']['fields']);
    }
    $form = $rc['form'];
    $submission = $rc['form']['submission'];

    //
    // Build submission details
    //
    $form['submission_details'] = array(
        array('label'=>'Name', 'value'=>$form['name']),
        array('label'=>'Label', 'value'=>$submission['label']),
        array('label'=>'Terms Accepted', 'value'=>$submission['dt_terms_accepted_display']),
        array('label'=>'Submitted', 'value'=>$submission['dt_last_submitted_display']),
        array('label'=>'Status', 'value'=>$submission['status_text']),
        );

    //
    // Load customer details
    //
    if( isset($submission['customer_id']) && $submission['customer_id'] > 0 ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'hooks', 'customerDetails2');
        $rc = ciniki_customers_hooks_customerDetails2($ciniki, $args['tnid'], 
            array('customer_id'=>$submission['customer_id'], 'phone'=>'yes', 'emails'=>'yes', 'address'=>'yes')
        );
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $form['customer'] = $rc['customer'];
        $form['customer_details'] = $rc['details'];

        //
        // Check if course instructor
        //
        if( ciniki_core_checkModuleActive($ciniki, 'ciniki.courses') ) {
            $strsql = "SELECT instructors.id, "
                . "instructors.primary_image_id, "
                . "instructors.short_bio, "
                . "instructors.full_bio, "
                . "instructors.url "
                . "FROM ciniki_course_instructors AS instructors "
                . "WHERE customer_id = '" . ciniki_core_dbQuote($ciniki, $submission['customer_id']) . "' "
                . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "";
            $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.forms', 'instructor');
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.186', 'msg'=>'Unable to load instructor', 'err'=>$rc['err']));
            }
            if( isset($rc['instructor']) ) {
                $form['instructor'] = $rc['instructor'];
            }
        }
    }

    //
    // Load the votes
    //
    if( ($form['flags']&0x10) == 0x10 ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'forms', 'private', 'submissionVotesLoad');
        $rc = ciniki_forms_submissionVotesLoad($ciniki, $args['tnid'], $form['id'], $submission['id']);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $form['votes'] = isset($rc['votes']) ? $rc['votes'] : array();
    }

    return array('stat'=>'ok', 'form'=>$form);
}
?>
