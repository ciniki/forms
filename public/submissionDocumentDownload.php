<?php
//
// Description
// ===========
// This method will return all the information about an submission.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the submission is attached to.
// submission_id:          The ID of the submission to get the details for.
//
// Returns
// -------
//
function ciniki_forms_submissionDocumentDownload($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'submission_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Submission'),
        'data_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Field'),
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
    $rc = ciniki_forms_checkAccess($ciniki, $args['tnid'], 'ciniki.forms.submissionDocumentDownload');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    $strsql = "SELECT data.id, "
        . "fields.uuid AS field_uuid, "
        . "data.repeat_num, "
        . "data.data, "
        . "submissions.uuid AS submission_uuid "
        . "FROM ciniki_form_data AS data "
        . "INNER JOIN ciniki_form_fields AS fields ON ("
            . "data.field_id = fields.id "
            . "AND fields.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "INNER JOIN ciniki_form_submissions AS submissions ON ("
            . "data.submission_id = submissions.id "
            . "AND submissions.id = '" . ciniki_core_dbQuote($ciniki, $args['submission_id']) . "' "
            . "AND submissions.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE data.id = '" . ciniki_core_dbQuote($ciniki, $args['data_id']) . "' "
        . "AND data.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.forms', 'data');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.217', 'msg'=>'Unable to load data', 'err'=>$rc['err']));
    }
    if( !isset($rc['data']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.218', 'msg'=>'Unable to find requested data'));
    }
    $data = $rc['data'];
    
    //
    // Get the tenant storage directory
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'hooks', 'storageDir');
    $rc = ciniki_tenants_hooks_storageDir($ciniki, $args['tnid'], array());
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $storage_filename = $rc['storage_dir'] . '/ciniki.forms/documents/' 
        . "{$data['submission_uuid'][0]}/{$data['submission_uuid']}_{$data['repeat_num']}_{$data['field_uuid']}";
    if( !file_exists($storage_filename) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.form.219', 'msg'=>'File does not exist'));
    }

    header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); 
    header("Last-Modified: " . gmdate("D,d M YH:i:s") . " GMT"); 
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: no-cache');
    // Set mime header
    $finfo = finfo_open(FILEINFO_MIME);
    if( $finfo ) { header('Content-Type: ' . finfo_file($finfo, $storage_filename)); }
    // Specify Filename
    header('Content-Disposition: attachment;filename="' . $data['data'] . '"');
    header('Content-Length: ' . filesize($storage_filename));
    header('Cache-Control: max-age=0');

    $fp = fopen($storage_filename, 'rb');
    fpassthru($fp);

    return array('stat'=>'binary');
}
?>
