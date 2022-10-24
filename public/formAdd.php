<?php
//
// Description
// -----------
// This method will add a new form for the tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to add the Form to.
//
// Returns
// -------
//
function ciniki_forms_formAdd(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'name'=>array('required'=>'yes', 'blank'=>'no', 'trim'=>'yes', 'name'=>'Name'),
        'permalink'=>array('required'=>'no', 'blank'=>'yes', 'trim'=>'yes', 'name'=>'Permalink'),
        'type'=>array('required'=>'no', 'blank'=>'yes', 'trim'=>'yes', 'name'=>'Type'),
        'status'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Status'),
        'flags'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Options'),
        'max_submissions'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'number', 'name'=>'Max Submissions'),
        'fee_label'=>array('required'=>'no', 'blank'=>'yes', 'trim'=>'yes', 'name'=>'Fee Label'),
        'fee_amount'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'number', 'name'=>'Submission Fee'),
        'cartsubmit_label'=>array('required'=>'no', 'blank'=>'yes', 'trim'=>'yes', 'name'=>'Cart Submit Label'),
        'submit_label'=>array('required'=>'no', 'blank'=>'yes', 'trim'=>'yes', 'name'=>'Submit Label'),
        'dt_start'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'datetimetoutc', 'trim'=>'yes', 'name'=>'Start Date'),
        'dt_end'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'datetimetoutc', 'trim'=>'yes', 'defaulttime'=>'23:59', 'name'=>'End Date'),
        'max_customer_submissions'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'number', 'name'=>'Max Customer Submissions'),
        'guidelines'=>array('required'=>'no', 'blank'=>'yes', 'trim'=>'yes', 'name'=>'Guidelines'),
        'termsofuse'=>array('required'=>'no', 'blank'=>'yes', 'trim'=>'yes', 'name'=>'Terms of Use'),
        'thankyou'=>array('required'=>'no', 'blank'=>'yes', 'trim'=>'yes', 'name'=>'Thank You'),
        'emailthankyou'=>array('required'=>'no', 'blank'=>'yes', 'trim'=>'yes', 'name'=>'Email Thank You'),
        'alreadysubmitted'=>array('required'=>'no', 'blank'=>'yes', 'trim'=>'yes', 'name'=>'Existing Submission'),
        'loginmsg'=>array('required'=>'no', 'blank'=>'yes', 'trim'=>'yes', 'name'=>'Login Required Message'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'forms', 'private', 'checkAccess');
    $rc = ciniki_forms_checkAccess($ciniki, $args['tnid'], 'ciniki.forms.formAdd');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Setup permalink
    //
    if( !isset($args['permalink']) || $args['permalink'] == '' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'makePermalink');
        $args['permalink'] = ciniki_core_makePermalink($ciniki, $args['name']);
    }

    //
    // Make sure the permalink is unique within active forms. Non active forms can have the same permalinks
    //
    if( isset($args['status']) && $args['status'] == 50 ) {
        $strsql = "SELECT id, name, permalink "
            . "FROM ciniki_forms "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND permalink = '" . ciniki_core_dbQuote($ciniki, $args['permalink']) . "' "
            . "AND status = 50 "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.forms', 'item');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( $rc['num_rows'] > 0 ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.4', 'msg'=>'You already have a form with that name, please choose another.'));
        }
    }

    //
    // Start transaction
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.forms');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Add the form to the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    $rc = ciniki_core_objectAdd($ciniki, $args['tnid'], 'ciniki.forms.form', $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.forms');
        return $rc;
    }
    $form_id = $rc['id'];

    //
    // Commit the transaction
    //
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.forms');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Update the last_change date in the tenant modules
    // Ignore the result, as we don't want to stop user updates if this fails.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'updateModuleChangeDate');
    ciniki_tenants_updateModuleChangeDate($ciniki, $args['tnid'], 'ciniki', 'forms');

    //
    // Update the web index if enabled
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'hookExec');
    ciniki_core_hookExec($ciniki, $args['tnid'], 'ciniki', 'web', 'indexObject', array('object'=>'ciniki.forms.form', 'object_id'=>$form_id));

    return array('stat'=>'ok', 'id'=>$form_id);
}
?>
