<?php
//
// Description
// -----------
// Load the list of available field refs from other modules
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_forms_fieldRefsAvailable(&$ciniki, $tnid) {

    //
    // Check each module for blocks available
    //
    $refs = array();
    foreach($ciniki['tenant']['modules'] as $module) {
        //
        // Check if the module has the hooks/formFieldRefs
        //
        $rc = ciniki_core_loadMethod($ciniki, $module['package'], $module['module'], 'hooks', 'formFieldRefs');
        if( $rc['stat'] == 'ok' ) {
            $fn = $rc['function_call'];
            $rc = $fn($ciniki, $tnid, array());
            if( $rc['stat'] == 'ok' ) {
                
                $refs = array_merge($refs, $rc['refs']);
            }
        }
    }

    //
    // Sort the references
    //
//    uasort($refs, function($a, $b) {
//        if( $a['module'] == $b['module'] ) {
//            return strcasecmp($a['name'], $b['name']);
//        }
//        return strcasecmp($a['module'], $b['module']);
//        });

    return array('stat'=>'ok', 'refs'=>$refs);
}
?>
