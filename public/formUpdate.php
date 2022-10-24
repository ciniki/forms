<?php
//
// Description
// ===========
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_forms_formUpdate(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'form_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Form'),
        'name'=>array('required'=>'no', 'blank'=>'no', 'trim'=>'yes', 'name'=>'Name'),
        'permalink'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Permalink'),
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
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'forms', 'private', 'checkAccess');
    $rc = ciniki_forms_checkAccess($ciniki, $args['tnid'], 'ciniki.forms.formUpdate');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    if( isset($args['name']) || (isset($args['status']) && $args['status'] == 50) ) {
        //
        // Load the existing record
        //
        $strsql = "SELECT ciniki_forms.id, "
            . "ciniki_forms.name, "
            . "ciniki_forms.permalink, "
            . "ciniki_forms.type, "
            . "ciniki_forms.status, "
            . "ciniki_forms.flags, "
            . "ciniki_forms.fee_amount, "
            . "ciniki_forms.max_submissions, "
            . "ciniki_forms.dt_start, "
            . "ciniki_forms.dt_end "
            . "FROM ciniki_forms "
            . "WHERE ciniki_forms.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_forms.id = '" . ciniki_core_dbQuote($ciniki, $args['form_id']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.forms', 'form');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.12', 'msg'=>'Unable to load form', 'err'=>$rc['err']));
        }
        if( !isset($rc['form']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.13', 'msg'=>'Unable to find requested form'));
        }
        $form = $rc['form'];
        
        if( isset($args['name']) ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'makePermalink');
            $args['permalink'] = ciniki_core_makePermalink($ciniki, $args['name']);
        }

        //
        // Make sure the permalink is unique within active forms
        //
        $strsql = "SELECT id, name, permalink "
            . "FROM ciniki_forms "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND permalink = '" . ciniki_core_dbQuote($ciniki, (isset($args['permalink']) ? $args['permalink'] : $form['permalink'])) . "' "
            . "AND id <> '" . ciniki_core_dbQuote($ciniki, $args['form_id']) . "' "
            . "AND status = 50 "    
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.forms', 'item');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( $rc['num_rows'] > 0 ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.10', 'msg'=>'You already have an form with this name, please choose another.'));
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
    // Update the Form in the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.forms.form', $args['form_id'], $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.forms');
        return $rc;
    }

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
    ciniki_core_hookExec($ciniki, $args['tnid'], 'ciniki', 'web', 'indexObject', array('object'=>'ciniki.forms.form', 'object_id'=>$args['form_id']));

    return array('stat'=>'ok');
}
?>
