<?php
//
// Description
// -----------
// This function returns the int to text mappings for the module.
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_forms_maps(&$ciniki) {
    //
    // Build the maps object
    //
    $maps = array();
    $maps['form'] = array(
        'status' => array(
            '10'=>'Draft',
            '50'=>'Active',
            '90'=>'Archive',
        ),
    );
    $maps['field'] = array(
        'ftype' => array(
            'date'=>'Date',
            'number'=>'Number',
            'price'=>'Price',
            'phone'=>'Phone Number',
            'email'=>'Email Address',
            'url'=>'Website',
            'text'=>'Text',
            'textarea'=>'Multiline',
            'select'=>'Dropdown',
            'radio'=>'Radio List',
            'checkbox'=>'Checkbox',
            'content'=>'Information',
            'image'=>'Image',
            'document'=>'Document',
        ),
    );
    $maps['submission'] = array(
        'status' => array(
            '10'=>'In Progress',
            '80'=>'Paid',
            '90'=>'Submitted',
        ),
    );
    //
    return array('stat'=>'ok', 'maps'=>$maps);
}
?>
