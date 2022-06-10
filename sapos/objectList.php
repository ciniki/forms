<?php
//
// Description
// ===========
// This method returns the list of objects that can be returned
// as invoice items.
//
// Arguments
// =========
// 
// Returns
// =======
//
function ciniki_forms_sapos_objectList($ciniki, $tnid) {

    $objects = array(
        //
        // this object should only be added to carts
        //
        'ciniki.forms.submission' => array(
            'name' => 'Form Submission',
            ),
        );

    return array('stat'=>'ok', 'objects'=>$objects);
}
?>
