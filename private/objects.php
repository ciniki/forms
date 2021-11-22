<?php
//
// Description
// -----------
// This function returns the list of objects for the module.
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_forms_objects(&$ciniki) {
    //
    // Build the objects
    //
    $objects = array();
    $objects['form'] = array(
        'name' => 'Form',
        'sync' => 'yes',
        'o_name' => 'form',
        'o_container' => 'forms',
        'table' => 'ciniki_forms',
        'fields' => array(
            'name' => array('name'=>'Name'),
            'permalink' => array('name'=>'Permalink', 'default'=>''),
            'type' => array('name'=>'Type', 'default'=>''),
            'status' => array('name'=>'Status', 'default'=>'10'),
            'flags' => array('name'=>'Options', 'default'=>'0'),
            'max_submissions' => array('name'=>'Max Submissions', 'default'=>''),
            'fee_label' => array('name'=>'Fee Label', 'default'=>''),
            'fee_amount' => array('name'=>'Submission Fee', 'default'=>''),
            'cartsubmit_label' => array('name'=>'Cart Submit Label', 'default'=>''),
            'submit_label' => array('name'=>'Submit Label', 'default'=>''),
            'dt_start' => array('name'=>'Start Date', 'default'=>''),
            'dt_end' => array('name'=>'End Date', 'default'=>''),
            'guidelines' => array('name'=>'Guidelines', 'default'=>''),
            'termsofuse' => array('name'=>'Terms of Use', 'default'=>''),
            'thankyou' => array('name'=>'Thank You Message', 'default'=>''),
            'alreadysubmitted' => array('name'=>'Already Submitted Message', 'default'=>''),
            'loginmsg' => array('name'=>'Login Required Message', 'default'=>''),
            ),
        'history_table' => 'ciniki_forms_history',
        );
    $objects['section'] = array(
        'name' => 'Section',
        'sync' => 'yes',
        'o_name' => 'section',
        'o_container' => 'sections',
        'table' => 'ciniki_form_sections',
        'fields' => array(
            'form_id' => array('name'=>'Form', 'ref'=>'ciniki.forms.form'),
            'label' => array('name'=>'Label', 'default'=>''),
            'sequence' => array('name'=>'Order', 'default'=>'1'),
            'flags' => array('name'=>'Options', 'default'=>'0'),
            'repeat_prefix' => array('name'=>'Repeat Label', 'default'=>''),
            'min_repeats' => array('name'=>'Min Repeats', 'default'=>'1'),
            'max_repeats' => array('name'=>'Max Repeats', 'default'=>'0'),
            'description' => array('name'=>'Description', 'default'=>''),
            ),
        'history_table' => 'ciniki_forms_history',
        );
    $objects['juror'] = array(
        'name' => 'Juror',
        'sync' => 'yes',
        'o_name' => 'juror',
        'o_container' => 'jurors',
        'table' => 'ciniki_form_jurors',
        'fields' => array(
            'form_id' => array('name'=>'Form', 'ref'=>'ciniki.forms.form'),
            'customer_id' => array('name'=>'Customer', 'ref'=>'ciniki.customers.customer'),
            ),
        'history_table' => 'ciniki_forms_history',
        );
    $objects['vote'] = array(
        'name' => 'Vote',
        'sync' => 'yes',
        'o_name' => 'vote',
        'o_container' => 'votes',
        'table' => 'ciniki_form_votes',
        'fields' => array(
            'submission_id' => array('name'=>'Form', 'ref'=>'ciniki.forms.submission'),
            'juror_id' => array('name'=>'Customer', 'ref'=>'ciniki.customers.customer'),
            'vote' => array('name'=>'Vote', 'default'=>'0'),
            'notes' => array('name'=>'Notes', 'default'=>''),
            ),
        'history_table' => 'ciniki_forms_history',
        );
    $objects['field'] = array(
        'name' => 'Form Field',
        'sync' => 'yes',
        'o_name' => 'field',
        'o_container' => 'fields',
        'table' => 'ciniki_form_fields',
        'fields' => array(
            'section_id' => array('name'=>'Form', 'ref'=>'ciniki.forms.section'),
            'ftype' => array('name'=>'Type', 'default'=>''),
            'flags' => array('name'=>'Options', 'default'=>'0'),
            'sequence' => array('name'=>'Order', 'default'=>'1'),
            'field_ref' => array('name'=>'Linked Field', 'default'=>''),
            'label' => array('name'=>'Label', 'default'=>''),
            'description' => array('name'=>'Description', 'default'=>''),
            'options' => array('name'=>'Options', 'default'=>''),
            ),
        'history_table' => 'ciniki_forms_history',
        );
    $objects['submission'] = array(
        'name' => 'Submission',
        'sync' => 'yes',
        'o_name' => 'submission',
        'o_container' => 'submissions',
        'table' => 'ciniki_form_submissions',
        'fields' => array(
            'form_id' => array('name'=>'Form', 'ref'=>'ciniki.forms.form'),
            'object' => array('name'=>'Object', 'default'=>''),
            'object_id' => array('name'=>'Object ID', 'default'=>''),
            'customer_id' => array('name'=>'Customer', 'ref'=>'ciniki.customers.customer'),
            'invoice_id' => array('name'=>'Invoice', 'ref'=>'ciniki.sapos.invoice'),
            'status' => array('name'=>'Status', 'default'=>'10'),
            'dt_terms_accepted' => array('name'=>'Terms Accepted', 'default'=>''),
            'dt_last_save' => array('name'=>'Last Saved', 'default'=>''),
            'dt_last_submitted' => array('name'=>'Last Submitted', 'default'=>''),
            ),
        'history_table' => 'ciniki_forms_history',
        );
    $objects['data'] = array(
        'name' => 'data',
        'sync' => 'yes',
        'o_name' => 'data',
        'o_container' => 'data',
        'table' => 'ciniki_form_data',
        'fields' => array(
            'submission_id' => array('name'=>'Submission', 'ref'=>'ciniki.forms.submission'),
            'field_id' => array('name'=>'Field', 'ref'=>'ciniki.forms.field'),
            'repeat_num' => array('name'=>'Repeat Number', 'default'=>'1'),
            'data' => array('name'=>'Data', 'default'=>''),
            ),
        'history_table' => 'ciniki_forms_history',
        );


    //
    return array('stat'=>'ok', 'objects'=>$objects);
}
?>
