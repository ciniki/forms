<?php
//
// Description
// -----------
// This function will return the list of permission groups for this module.
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_forms_hooks_modPerms(&$ciniki, $tnid, $args) {

    $modperms = array(
        'label' => 'Forms',
        'perms' => array(
            'ciniki.forms' => 'Full Access',
            ),
        );

    return array('stat'=>'ok', 'modperms'=>$modperms);
}
?>
