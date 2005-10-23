<?php
/**
 * File: $Id$
 *
 * Search
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 *
 * @subpackage gallery2 module
 * @author andy staudacher <ast@gmx.ch>
*/

// Load the xarGallery2Helper class
include_once(dirname(__FILE__) .'/../xargallery2helper.php');

/**
 * the main user function lists the available objects defined in gallery2
 *
 */
function gallery2_user_search()
{
// Security Check
    if(!xarSecurityCheck('ReadGallery2', 0)) return;

    if (!xarVarFetch('q', 'isset', $q, NULL, XARVAR_DONT_SET)) {return;}
    if (!xarVarFetch('gallery2_check', 'isset', $gallery2_check, NULL, XARVAR_DONT_SET)) {return;}
    if (empty($gallery2_check)) {
        $gallery2_check = array();
    }
     // pager stuff
    if(!xarVarFetch('startnum', 'int:0', $startnum,  NULL, XARVAR_NOT_REQUIRED)) {return;}
    // search button was pressed
    if(!xarVarFetch('search',   'str',   $search,    NULL, XARVAR_NOT_REQUIRED)) {return;}
    
    // Default parameters
    if (!isset($startnum)) {
        $startnum = 1;
    }
    if (!isset($numitems)) {
        $numitems = 10;
    }
    
    // first check if the module has been configured
    if(!xarGallery2Helper::isConfigured()) {
      return;
    }

    if (!isset($q) || empty($q)) {
      return '';
    }

    // init G2 if not already done so
    if (!xarGallery2Helper::init(false, true)) {
      return;
    }


    $data['items'] = array();
    $mymodid = xarModGetIDFromName('gallery2');


    /**
     * Perform a search across all available searchable modules.
     *
     * @param string search criteria
     * @param int (optional) max number of results to return from each module, defaults to 3
     * @return array object GalleryStatus a status object
     *               array of {module_id} => results array plus 'name' key with module name
     * @see GallerySearchInterface_1_0::search for contents of results arrays
     * @static
     */

    /**
     * Search the module for the given criteria with the given options
     *
     * @param array array('option_key_1', 'option_key_3')
     * @param string search criteria
     * @param int which hit to start with
     * @param int how many hits to show
     * @return array(object GalleryStatus a status code,
     *               array('start' => 1..#,
     *                     'end' => 1..#,
     *                     'count' => #,
     *                     'results' => array(itemId => id,
     *                                        array(array('key' => 'localized title',
     *                                                    'value' => 'localized text'),
     *                                              array('key' => 'localized title',
     *                                                    'value' => 'localized text'),
     *                                              array('key' => 'localized title',
     *                                                    'value' => 'localized text')))))
     */

    // search G2 for the search string q
    list ($ret, $results) = GalleryEmbed::searchScan($q, $numitems);
    print "<pre>";
    print_r($results);
    print "</pre>";
    exit;
    if (!$ret->isSuccess()) {
      $msg = xarML('G2 did not return a success status upon a search request. Here is the error message from G2: <br /> [#(1)]', $ret->getAsHtml());
      xarErrorSet(XAR_SYSTEM_EXCEPTION, 'FUNCTION_FAILED', new SystemException($msg));
      return;
    }
    /*
    foreach ($objects as $itemid => $object) {
        // skip the internal objects
        if ($itemid < 3) continue;
        $modid = $object['moduleid'];
        // don't show data "belonging" to other modules for now
        if ($modid != $mymodid) {
            continue;
        }
        $label = $object['label'];
        $itemtype = $object['itemtype'];
        $fields = xarModAPIFunc('dynamicdata','user','getprop',
                                array('modid' => $modid,
                                      'itemtype' => $itemtype));
        $wherelist = array();
        foreach ($fields as $name => $field) {
            if (!empty($dd_check[$field['id']])) {
                $fields[$name]['checked'] = 1;
                if (!empty($q)) {
                    $wherelist[] = $name . " LIKE '%" . $q . "%'";
                }
            }
        }
        if (!empty($q) && count($wherelist) > 0) {
            $where = join(' or ',$wherelist);
            $numitems = 20;
            $status = 1;
            $result = xarModAPIFunc('dynamicdata','user','showview',
                                    array('modid' => $modid,
                                          'itemtype' => $itemtype,
                                          'where' => $where,
                                          'numitems' => $numitems,
                                          'layout' => 'list',
                                          'status' => $status));
        } else {
            $result = null;
        }
        // nice(r) URLs
        if ($modid == $mymodid) {
            $modid = null;
        }
        if ($itemtype == 0) {
            $itemtype = null;
        }
        $data['items'][] = array(
                                 'link'   => xarModURL('dynamicdata','user','view',
                                                       array('modid' => $modid,
                                                             'itemtype' => empty($itemtype) ? null : $itemtype)),
                                 'label'  => $label,
                                 'fields' => $fields,
                                 'result' => $result,
                                );
    }
    */
    return $data;
}

?>