<?php

/*
 * $RCSfile$
 *
 * Gallery - a web based photo album viewer and editor
 * Copyright (C) 2000-2005 Bharat Mediratta
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or (at
 * your option) any later version.
 *
 * This program is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */
/**
 * Gallery 2 integration for phpBB2.
 * @version $Revision$ $Date$
 * @author Dariush Molavi <dari@nukedgallery.net>
 * @author Mike Rollins
 */

class g2helper
{

  var $fullpath;
  var $embedPath;
  var $relativeG2Path;
  var $embedUri;
  var $loginRedirect;
  var $cookiePath;

  function g2helper($db)
    {
      
      $sql = "SELECT * FROM phpbb_gallery2";
      $result = $db->sql_query($sql);
      $row = $db->sql_fetchrow($result);

      $this->fullpath       = $row['fullPath'];;
      $this->embedPath      = $row['embedPath'];
      $this->relativeG2Path = $row['relativePath'];
      $this->embedUri       = $row['embedURI'];
      $this->loginRedirect  = $row['loginPath'];
      $this->cookiePath     = $row['cookiePath'];

      require_once($this->fullpath . 'embed.php'); 

    }

  function init_admin()
    {
      $ret = GalleryEmbed::init(array( 'embedUri'       => $this->embedUri,
                                       'embedPath'      => $this->embedPath,
                                       'relativeG2Path' => $this->relativeG2Path,
                                       'loginRedirect'  => $this->loginRedirect,
                                       'activeUserId'   => "admin"));
      if ($ret->isError())
        {
          echo $ret->getAsHtml();
          exit;
        }
    }

  function init($userdata)
    {
      if($userdata['user_level'] == ADMIN)
        {
          $ret = GalleryEmbed::init(array( 'embedUri'       => $this->embedUri,
                                           'embedPath'      => $this->embedPath,
                                           'relativeG2Path' => $this->relativeG2Path, 
                                           'loginRedirect'  => $this->loginRedirect,
                                           'activeUserId'   => "admin"));
          if ($ret->isError()) 
            {
              echo $ret->getAsHtml();
              exit;
            }
        }
      else if($userdata['user_id'] != ANONYMOUS) 
        {
          $ret = GalleryEmbed::init(array( 'embedUri'       => $this->embedUri, 
                                           'embedPath'      => $this->embedPath, 
                                           'relativeG2Path' => $this->relativeG2Path, 
                                           'loginRedirect'  => $this->loginRedirect, 
                                           'activeUserId'   => $userdata['user_id'])); 

          //echo "KEYS: " . join("<li>",array_keys($userdata)) ;
          //echo "<li>PASSWD: " . $userdata['user_password'];

          if ($ret->isError()) 
            { 
              if( $ret->getErrorCode() & ERROR_MISSING_OBJECT )
                {
                  $this->createUser($userdata['user_id'], array( 'username'       => $userdata['username'],
                                                                 'hashedpassword' => $userdata['user_password'],
                                                                 'hashmethod'     => 'md5',
                                                                 'email'          => $userdata['user_email'],
                                                                 'fullname'       => $userdata['username']      ));

                  $ret = GalleryEmbed::init(array( 'embedUri'       => $this->embedUri,
                                                   'embedPath'      => $this->embedPath,
                                                   'relativeG2Path' => $this->relativeG2Path,
                                                   'loginRedirect'  => $this->loginRedirect,
                                                   'activeUserId'   => $userdata['user_id']));
                  if ($ret->isError()) 
                    { 
                      echo $ret->getAsHtml();
                      exit;
                    }

                }
              else
                {
                  echo $ret->getAsHtml();
                  exit;
                }
          }
	} 
      else 
        {
	  $ret = GalleryEmbed::init(array( 'embedUri'       => $this->embedUri, 
                                           'embedPath'      => $this->embedPath, 
                                           'relativeG2Path' => $this->relativeG2Path, 
                                           'loginRedirect'  => $this->loginRedirect, 
                                           'activeUserId'   => 0)); 
          if ($ret->isError()) 
            { 
              echo $ret->getAsHtml();
              exit; 
            } 
        }

  }

  function done()
    {
      $ret = GalleryEmbed::done();
      if ($ret->isError()) 
        {
          $ret->getAsHtml();
        }
    }

  function changePassword($id,$password)
    {
      //$ret = GalleryEmbed::updateUser( $id, array('password' => $password) );
      $ret = GalleryEmbed::updateUser( $id, array('hashedpassword' => $password,
                                                  'hashmethod' => 'md5'));
      if ($ret->isError())
        {
          if( $ret->getErrorCode() & ERROR_MISSING_OBJECT )
            {
              // This seems to happen when a new user is created.
              // Since we currently create the user on the first
              // access to the gallery, we are not going to worry
              // about this error.
            }
          else
            {
              echo $ret->getAsHtml();
              exit;
            }

        }
    }

  function deleteUser($id)
    {
      $ret = GalleryEmbed::deleteUser($id);
      if ($ret->isError())
        {
          if( $ret->getErrorCode() & ERROR_MISSING_OBJECT )
            {
              // This happen when there is no gallery account for the user.
              echo "<center><b>Note: There was no gallery account for this user.</b></center>";
            }
          else
            {
              echo $ret->getAsHtml();
              exit;
            }
        }
    }

  function createUser($id, $userdata)
    {
      $this->init_admin();
      $ret = GalleryEmbed::createUser( $id, $userdata);
      if ($ret->isError())
        {
          echo $ret->getAsHtml();
          exit;
        }
      $this->done();
    }
}

?>