<?php
//
// Description
// -----------
// This function will fill in the default field values for the forms from other modules
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_forms_formDefaultsLoad(&$ciniki, $tnid, &$form) {

    //
    // Check each module for blocks available
    //
    foreach($ciniki['tenant']['modules'] as $module) {
        //
        // Check if the module has the hooks/formFieldRefs
        //
        $rc = ciniki_core_loadMethod($ciniki, $module['package'], $module['module'], 'hooks', 'formDefaultsLoad');
        if( $rc['stat'] == 'ok' ) {
            $fn = $rc['function_call'];
            $rc = $fn($ciniki, $tnid, array('form'=>$form));
            if( $rc['stat'] == 'ok' && isset($rc['form']) ) {
                $form = $rc['form'];
            }
        }
    }

    return array('stat'=>'ok', 'form'=>$form);
}
?>
