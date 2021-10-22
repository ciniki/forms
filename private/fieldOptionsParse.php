<?php
//
// Description
// -----------
// Parse the input arguments for 
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_forms_fieldOptionsParse(&$ciniki, $tnid, $ftype, $args, $json_options) {

   
    $options = json_decode($json_options, true);

    if( $ftype == 'text' ) {
        if( isset($args['max-characters']) ) {
            $options['max-characters'] = trim($args['max-characters']);
        }
    }
    elseif( $ftype == 'textarea' ) {
        if( isset($args['max-words']) ) {
            $options['max-words'] = trim($args['max-words']);
        }
        if( isset($args['size']) ) {
            $options['size'] = trim($args['size']);
        }
    }
    elseif( $ftype == 'select' || $ftype == 'radio' ) {
        for($i = 0; $i < 20; $i++ ) {
            if( isset($args["option-{$i}"]) ) {
                $options["option-{$i}"] = trim($args["option-{$i}"]);
            }
        }
    } 
    elseif( $ftype == 'image' ) {
        if( isset($args['min-width']) ) {
            $options['min-width'] = trim($args['min-width']);
        }
        if( isset($args['max-width']) ) {
            $options['max-width'] = trim($args['max-width']);
        }
        if( isset($args['min-height']) ) {
            $options['min-height'] = trim($args['min-height']);
        }
        if( isset($args['max-height']) ) {
            $options['max-height'] = trim($args['max-height']);
        }
    }
    //
    // Type with no arguments, clear options
    //
    else {
        $options = array();
    }
    $options = json_encode($options);

    return array('stat'=>'ok', 'options'=>$options);
}
?>
