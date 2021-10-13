<?php
//
// Description
// -----------
// This method will search a field for the search string provided.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:     The ID of the tenant to search.
//
// start_needle:    The search string to search the field for.
//
// limit:           (optional) Limit the number of results to be returned. 
//                  If the limit is not specified, the default is 25.
// 
// Returns
// -------
// <results>
//      <result name="Landscape" />
//      <result name="Portrait" />
// </results>
//
function ciniki_forms_formFieldSearch($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'field'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Field'),
        'start_needle'=>array('required'=>'yes', 'blank'=>'yes', 'name'=>'Search'), 
        'limit'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Limit'), 
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
    $rc = ciniki_forms_checkAccess($ciniki, $args['tnid'], 'ciniki.forms.formFieldSearch'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // Reject if an unknown field
    //
    if( $args['field'] != 'type' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.38', 'msg'=>'Unvalid search field'));
    }
    //
    // Get the number of faqs in each status for the tenant, 
    // if no rows found, then return empty array
    //
    $strsql = "SELECT DISTINCT " . $args['field'] . " AS value "
        . "FROM ciniki_forms "
        . "WHERE ciniki_forms.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND (" . $args['field']  . " LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "AND " . $args['field'] . " <> '' "
            . ") "
        . "ORDER BY " . $args['field'] . " "
        . "";
    if( isset($args['limit']) && $args['limit'] != '' && $args['limit'] > 0 ) {
        $strsql .= "LIMIT " . ciniki_core_dbQuote($ciniki, $args['limit']) . " ";
    } else {
        $strsql .= "LIMIT 25 ";
    }
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.forms', array(
        array('container'=>'results', 'fname'=>'value', 'fields'=>array('value')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    return $rc;
}
?>
