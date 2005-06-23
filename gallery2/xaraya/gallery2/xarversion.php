<?php
/**
 * File: $Id$
 * 
 * Example initialization functions
 * 
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * 
 * @subpackage gallery2
 * @author gallery2 module developer team 
 */
$modversion['name']           = 'Gallery2';
$modversion['id']             = '35';
$modversion['version']        = '0.6.6';
$modversion['description']    = 'G2 integration module';
$modversion['credits']        = '';
$modversion['help']           = 'http://gallery.sourceforge.net';
$modversion['changelog']      = '';
$modversion['license']        = 'xardocs/license.txt';
$modversion['official']       = 1;
$modversion['author']         = 'Andy Staudacher';
$modversion['contact']        = 'ast [please add @gmx.ch]';
$modversion['admin']          = 1; // modify config page
$modversion['user']           = 1; // everything else
$modversion['securityschema'] = array('Gallery2::' => '::');
$modversion['class']          = 'User'; // ATM only user functionality
$modversion['category']       = 'Content';
$conditions_mod_modules = array(  'minversion' => '2.3.0',
                            'maxversion' => '',
                            'effect'     => 'max',
                            'activate'   => 'before');

$conditions_mod_roles = array('minversion' => '1.1.0',
                            'maxversion' => '',
                            'effect'     => 'max',
                            'activate'   => 'before');
// FIXME ASAP modify roles module version nr dependency
$mastermodules = array( 1 => $conditions_mod_modules, // modules
                       27 => $conditions_mod_roles); // roles
$modversion['dependency']     = $mastermodules;
?>
