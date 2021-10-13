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
            'phone'=>'Phone',
            'email'=>'Email',
            'url'=>'Website',
            'text'=>'Text',
            'textarea'=>'Text Multiline',
            'select'=>'Select',
            'radio'=>'Radio Buttons',
            'checkbox'=>'Checkbox',
            'content'=>'Content',
            'image'=>'Image',
            'document'=>'Document',
        ),
    );
    //
    return array('stat'=>'ok', 'maps'=>$maps);
}
?>
