<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

//  CI 2.0 Compatibility
if(!class_exists('CI_Model')) { class CI_Model extends Model {} }

/**
 * Model for managing the add-ons, their tags and ratings in the database.
 *
 * The model should prevent SQL injection even if it is directly accessible. If
 * you see anything not following this rule, please notify the developers!
 * 
 * Things that might get adressed in the future (TODO):
 * - look at performance. Those joins are nice to read, but maybe also slow. We might
 *   benchmark that. Also I guess there is much other inefficient stuff here!
 * - some kind of locking? As for example tagging is atomic (lookup, optional creation,
 *   linking), the multiple sql queries might cause issues when running simultaneously.
 *   Anyway, consistency should be ensured by the unique stuff in the schema.
 * - Add a table for addon requirements, and use the existing fields in xpi_parser for that.
 * - Make the addon_compartibility fields readable through this model
 * - Add a table for supported locales
 */

class Addons_model extends CI_Model
{
  public function __construct(){
    parent::__construct();
    
    $this->load->config('addons', TRUE);
    $this->storage = $this->config->item('addon-storage', 'addons');
    $this->img_storage = $this->config->item('addon-image-storage', 'addons');
    
    $this->load->library('xpi_parser');
  }
  
  /**
   * Notes to this Model:
   * 
   * Users are represented over their _username_.
   * 
   * Notes to structures:
   * 
   * Some information needs to be in structures described here.
   *
   * Add-ons: Array containing
   *   'id': internal id within featherweight
   *   'uuid': id in the install.rdf, used for updating
   *   'type': type number as in install.rdf
   *   'downloadcount': total number of downloads
   *   'creationdate': date of adding the addon to featherweight
   *   'changedate': date of last change to the add-on
   *   ---- Localized content depending on the session locale:
   *   'name': name of the add-on
   *   'description': description of the add-on [or null]
   *   'homepage': homepage of the add-on [or null]
   *   'email': support e-mail of the add-on [or null]
   *   ---- Content only readable, will get generated
   *   'rating': float of the average rating of this add-on (-1 for unrated)
   *
   * Credits: Array containing
   *   'authors': array of author's names [or empty array]
   *   'contributors': array of contributor's names [or empty array]
   *   'translators': array of translator's names [or empty array]
   *
   * Localized_content: Array of arrays containing
   *   'locale': locale this set of information belongs to
   *   'name': name of the add-on
   *   'description': description of the add-on [or null]
   *   'homepage': homepage of the add-on [or null]
   *   'email': support e-mail of the add-on [or null]
   *
   * Version: Array containing
   *   'version': the version number
   *   'osselection': the os the version was uploaded for
   *   'creationdate': the version's creation date
   *   'file': filename of the xpi file
   *   ---- Localized content depending on the session locale:
   *   'changelog': changelog text for this version
   *
   * Localized_changelog: Array of arrays containing
   *   'locale': locale this set of information belongs to
   *   'changelog': changelog text
   * 
   * Rating: Array containing
   *   'user': user that issued the rating
   *   'creationdate': date of adding the rating to featherweight
   *   'text': text the user submitted, or null
   *   'rating': integer rating from 1 to 5 (stars or whatever the skinners decide)
   *   ---- Content only readable, will get generated
   *   'numPrevious': count of previous ratings by the same user
   *
   *
   * Notes to the database's internals:
   * 
   * The credit types available are:
   * 0: author (in contrast to owner this is not parsed!)
   * 1: contributor
   * 2: translator
   *
   **/
  
  /**
   * Add-on list functions
   * Get the specified page of n add-ons out of a list by some criteria sorted by some other criteria
   * Session's locale setting will get used to determine what localized string
   * to use for sorting when choosing a localizeable criteria.
   *
   * Sort direction: true for ascending, false otherwise
   *
   * Sort criterias: see getSortCriteriaColumn
   **/
  
  // get a column for sorting
  protected function getSortCriteriaColumn($sortcirteria){
    switch ($sortcirteria){
      case 'downloadcount': return 'addonDownloadCount';
      case 'name': return 'metaName';
      case 'creationdate': return 'addonCreatedOn';
      case 'changedate': return 'addonChangedOn';
      default: throw new Exception('invalid sort criteria.');
    }
  }
  
  // SQL parts to be used in queries
  protected function getSelectAddonSQL($locale){
    return 'SELECT
      addons.id AS addonId,
      addons.addon_id AS addonUuid,
      addons.type AS addonType,
      addons.download_count AS addonDownloadCount,
      addons.created_on AS addonCreatedOn,
      addons.changed_on AS addonChangedOn,
      IF( ISNULL( meta.name ), meta_fallback.name, meta.name) AS metaName,
      IF( ISNULL( meta.description ), meta_fallback.description, meta.description) AS metaDescription,
      IF( ISNULL( meta.homepage ), meta_fallback.homepage, meta.homepage) AS metaHomepage,
      IF( ISNULL( meta.email ), meta_fallback.email, meta.email) AS metaEmail
      
      FROM
      addons addons
      LEFT JOIN addon_meta meta ON (addons.id = meta.addon_id AND meta.locale = '.$this->db->escape($locale).')
      INNER JOIN addon_meta meta_fallback ON (addons.id = meta_fallback.addon_id AND meta_fallback.locale = \'def\')';
  }
  
  // SQL Part for sorting of queried add-on lists
  protected function getSelectAddonSortSQL($sortby, $sortdirection){
    $direction = $sortdirection ? '' : 'DESC';
    return 'ORDER BY '.$this->getSortCriteriaColumn($sortby).' '.$direction;
  }
  
  // SQL Part for active Rating query
  protected function getAddonActiveRatingSQLCondition($id){
    // The following assumes that it is not possible to rate more than one time within a millisecond for one add-on
    return '
        addon_ratings.created_on IN (
          SELECT
            MAX(created_on)
          FROM
            addon_ratings joined_ratings
          WHERE
            addon_ratings.users_id = joined_ratings.users_id AND
            addon_ratings.addons_id = joined_ratings.addons_id
        )';
  }  
  
  // build a add-on structure from a queried row
  protected function getAddonFromQuery($row){
    // Calculate Rating
    $rating = -1;
    $query = $this->db->query('
      SELECT
        addon_ratings.rating AS rating
      FROM
        addon_ratings
      WHERE
        addon_ratings.addons_id = '.strval($row->addonId).'
        AND '.$this->getAddonActiveRatingSQLCondition($row->addonId));
    if ($query->num_rows() > 0){
      $rating = 0;
      foreach ($query->result() as $r){
        $rating += $r->rating;
      }
      $rating = $rating / $query->num_rows();
    }
    
    // Generate array
    return array(
      'id' => $row->addonId,
      'uuid' => $row->addonUuid,
      'type' => $row->addonType,
      'downloadcount' => $row->addonDownloadCount,
      'creationdate' => $row->addonCreatedOn,
      'changedate' => $row->addonChangedOn,
      
      'name' => $row->metaName,
      'description' => $row->metaDescription,
      'homepage' => $row->metaHomepage,
      'email' => $row->metaEmail,
      
      'rating' => $rating
    );
  }
  
  // build a list from a query
  protected function getAddonListFromQuery($query){
    $result = array();
    if ($query->num_rows() > 0){
      foreach ($query->result() as $row){
        $result[] = $this->getAddonFromQuery($row);
      }
    }
    return $result;
  }
  
  // list by tag
  public function getAddonsByTag($n, $page, $tag, $locale='def', $sortby='downloadcount', $sortdirection=false){
    $query = $this->db->query($this->getSelectAddonSQL($locale).'
      INNER JOIN addon_tags ON (addons.id = addon_tags.addon_id)
      INNER JOIN tags ON (addon_tags.tag_id = tags.id AND tags.tag = '.$this->db->escape($tag).')
      '.$this->getSelectAddonSortSQL($sortby, $sortdirection).'
      LIMIT '.strval(intval($page*$n)).','.strval(intval($n)));
    return $this->getAddonListFromQuery($query);
  }
  
  // list by owner
  public function getAddonsByOwner($n, $page, $owner, $locale='def', $sortby='downloadcount', $sortdirection=false){
    $query = $this->db->query($this->getSelectAddonSQL($locale).'
      INNER JOIN addon_ownerships ON (addons.id = addon_ownerships.addon_id)
      INNER JOIN users ON (addon_ownerships.user_id = users.id AND users.username = '.$this->db->escape($owner).')
      '.$this->getSelectAddonSortSQL($sortby, $sortdirection).'
      LIMIT '.strval(intval($page*$n)).','.strval(intval($n)));
    return $this->getAddonListFromQuery($query);
  }
  
  // list by type
  public function getAddonsByType($n, $page, $type, $locale='def', $sortby='downloadcount', $sortdirection=false){
    $query = $this->db->query($this->getSelectAddonSQL($locale).'
      WHERE addons.type = '.strval(intval($type)).'
      '.$this->getSelectAddonSortSQL($sortby, $sortdirection).'
      LIMIT '.strval(intval($page*$n)).','.strval(intval($n)));
    return $this->getAddonListFromQuery($query);
  }
  
  // free search
  public function getAddonsBySearchString($n, $page, $searchstring, $locale='def', $sortby='downloadcount', $sortdirection=false){
    // TODO: what stuff do we want to search on? Performance?
    throw new Exception('not implemented yet');
  }
  
  /**
   * Add-on grab functions
   * Get a single add-on by id or uuid
   **/
  // get by id
  public function getAddonById($id, $locale='def'){
    $query = $this->db->query($this->getSelectAddonSQL($locale).'
      WHERE addons.id = '.strval(intval($id)));
    return $this->getAddonFromQuery($query->row());
  }
  
  // get by uuid
  public function getAddonByUUID($uuid, $locale='def'){
    $query = $this->db->query($this->getSelectAddonSQL($locale).'
      WHERE addons.addon_id = '.$this->db->escape($uuid));
    return $this->getAddonFromQuery($query->row());
  }
  
  /**
   * Add-on get functions
   * Get additional information about an add-on
   **/
  // get an array of all tags for specific add-on
  public function getAddonTags($id){
    $query = $this->db->query('SELECT
        tags.tag AS tag
      FROM (tags, addon_tags)
      WHERE (tags.id = addon_tags.tag_id) AND (addon_tags.addon_id = '.strval(intval($id)).')');
    $result = array();
    if ($query->num_rows() > 0){
      foreach ($query->result() as $row){
        $result[] = $row->tag;
      }
    }
    return $result;
  }
  
  // get localized_content for specific add-on
  public function getAddonLocalizedContent($id){
    $query = $this->db->query('SELECT
        locale,
        name,
        description,
        homepage,
        email
      FROM addon_meta
      WHERE addon_meta.addon_id = '.strval(intval($id)));
    $result = array();
    if ($query->num_rows() > 0){
      foreach ($query->result() as $row){
        $result[] = array(
          'locale' => $row->locale,
          'name' => $row->name,
          'description' => $row->description,
          'homepage' => $row->homepage,
          'email' => $row->email
        );
      }
    }
    return $result;
  }
  
  // get credits for specific add-on
  public function getAddonCredits($id){
    $query = $this->db->query('SELECT
        addon_credits.type as type,
        addon_credits.name as name
      FROM addon_credits
      WHERE addon_credits.addon_id = '.strval(intval($id)));
    $result = array(
      'authors' => array(),
      'contributors' => array(),
      'translators' => array()
    );
    if ($query->num_rows() > 0){
      foreach ($query->result() as $row){
        switch ($row->type){
          case 0:
            $result['authors'][] = $row->name;
            break;
          case 1:
            $result['contributors'][] = $row->name;
            break;
          case 2:
            $result['translators'][] = $row->name;
            break;
          default:
            throw new Exception('Database integrity error: invalid data in addon_credits.type');
        }
      }
    }
    return $result;
  }
  
  // get list of users owning the add-on
  public function getAddonOwners($id){
    $query = $this->db->query('SELECT
        users.username AS username
      FROM (users, addon_ownerships)
      WHERE (users.id = addon_ownerships.users_id) AND (addon_ownerships.addons_id = '.strval(intval($id)).')');
    $result = array();
    if ($query->num_rows() > 0){
      foreach ($query->result() as $row){
        $result[] = $row->username;
      }
    }
    return $result;
  }
  
  // Helper to get the user id by username
  protected function getIdForUser($username){
    $query = $this->db->query('SELECT id FROM users WHERE (users.username = '.$this->db->escape($username).')');
    return strval($query->row()->id);
  }
  
  /**
   * Add-on creation/deletion functions
   * Creates a new add-on entry with the given values, or deletes an add-on permanently.
   **/
  // store new add-on (ownerlist is an array of owners), returns its id
  public function storeAddon($uuid, $type, $localized_content, $credits, $ownerlist){
    // Add the add-on itself
    $curtime = strval(time());
    $this->db->query('INSERT
      INTO addons
        (addon_id, type, created_on, changed_on)
      VALUES
        ('.$this->db->escape($uuid).', '.strval(intval($type)).', '.$curtime.', '.$curtime.')');
    $id = strval($this->db->insert_id());
    // Add the credits
    $this->setAddonCredits($id, $credits);
    // Add the localized content
    $this->setAddonLocalizedContent($id, $localized_content);
    // Add the corresponding owners
    foreach ($ownerlist as $owner){
      // Add the owner
      $this->db->query('INSERT INTO addon_ownerships (addon_id, user_id) VALUES ('.$id.','.$this->getIdForUser($owner).')');
    }
    return intval($id);
  }
  
  // remove add-on
  public function removeAddon($id){
    // TODO: files removal
    // The deletion itself
    $this->db->query('DELETE
      FROM addons
      WHERE id = '.strval(intval($id)));
  }
  
  /**
   * Add-on set functions
   * Edit add-on entries
   **/
  // change the localized content
  public function setAddonLocalizedContent($id, $localized_content){
    foreach ($localized_content as $content){			
      $this->db->query('INSERT
        INTO addon_meta
          (addon_id, locale, name, description, homepage, email)
        VALUES (
          '.strval(intval($id)).',
          '.$this->db->escape($content['locale']).',
          '.$this->db->escape($content['name']).',
          '.$this->db->escape($content['description']).',
          '.$this->db->escape($content['homepage']).',
          '.$this->db->escape($content['email']).')');
    }
  }
  
  // little helper for the next function, WARNING: $credit_type are considered as safe strings!
  protected function storeCreditPart($id, $credit_type, $namelist){
    foreach ($namelist as $name){
      $this->db->query('INSERT
        INTO addon_credits
          (addon_id, type, name)
        VALUES
          ('.strval(intval($id)).', '.$credit_type.', '.$this->db->escape($name).')');
    }
  }
  
  // change the credits
  public function setAddonCredits($id, $credits){
    $this->storeCreditPart($id, '0', $credits['authors']);
    $this->storeCreditPart($id, '1', $credits['contributors']);
    $this->storeCreditPart($id, '2', $credits['translators']);
  }
  
  // increment the download counter of an file
  public function incAddonVersionDownloadCount($id){
    $this->db->query('
      UPDATE addons SET download_count=download_count+1 WHERE id='.strval(intval($id)));
  }
  
  /**
   * Add-on tag functions
   * Used to tag and untag add-ons
   **/
  // tag specific add-on with the given tag
  public function tagAddon($id, $tag){
    // Create the tag if needed
    $query = $this->db->query('SELECT id FROM tags WHERE tag = '.$this->db->escape($tag));
    if ($query->num_rows() > 0){
      $tagid = strval($query->row()->id);
    } else {
      $this->db->query('INSERT INTO tags (tag) VALUES ('.$this->db->escape($tag).')');
      $tagid = strval($this->db->insert_id());
    }
    
    $this->db->query('INSERT
      INTO addon_tags
        (addon_id, tag_id)
      VALUES
        ('.strval(intval($id)).', '.$tagid.')');
  }
  
  // remove given tag from add-on
  public function untagAddon($id, $tag){
    // Get the tag's id
    $query = $this->db->query('SELECT id FROM tags WHERE tag = '.$this->db->escape($tag));
    if ($query->num_rows() > 0){
      $tagid = strval($query->row()->id);
    } else {
      throw new Exception('tag not found');
    }
    // Remove n:m relation
    $this->db->query('DELETE FROM addon_tags WHERE addon_id = '.strval(intval($id)).' AND tag_id = '.$tagid);
    // TODO: what is about tag removal? Maybe some cleanup methods by a cronjob or something? Or should a check be issued every time untagging something?
  }
  
  /**
   * Add-on versions functions
   * Used to store and recive files
   *
   * os selection: name of an OS ("win", "linux", ...) or null to not limit OS
   **/
  // helper for version detection, compares version strings
  protected function isVersionGreaterThan($version, $reference){
    // We'll use PHP's integrated stuff for that, at least for now
    return version_compare($version, $reference, '>');
  }
  
  protected function isVersionEqualOrGreaterThan($version, $reference){
    // We'll use PHP's integrated stuff for that, at least for now
    return version_compare($version, $reference, '>=');
  }
  
  // add a new version based on an uploaded file (which will get moved via move_uploaded_file!) and os selection. This will fail if the addon's version is lower or equal than the current one for that osselection or if important fields in install.rdf do not match!
  public function addAddonVersion($id, $file, $osselection, $localized_changelog, $debug_mode_do_not_move=false){
    // Parse the add-on's metadata
    $meta = $this->xpi_parser->getMetadata($file);
    if ($meta === null){
      throw new Exception('The add-on file could not get parsed by our xpi parser. Probably the add-on file is invalid.');
    }
    // Get the expected values
    $addon = $this->getAddonById($id);
    
    // Verify validity for this add-on
    $errormsg = 'Validation check error: the uploaded file does not match the add-on it was uploaded for: ';
    if ($meta['id'] != $addon['uuid']){
      throw new Exception($errormsg.'UUID not equal');
    }
    if ($meta['type'] != $addon['type']){
      throw new Exception($errormsg.'Type not equal');
    }
    $recent = $this->getMostRecentAddonVersion($id, $osselection);
    if (($recent != null) and (!$this->isVersionGreaterThan($meta['version'], $recent['version']))){
      throw new Exception($errormsg.'Version must get increased.');
    }
    
    // Add the new add-on version
    $curtime = strval(time());
    $this->db->query('INSERT
      INTO addon_files
        (addon_id, os, version, created_on)
      VALUES
        ('.strval(intval($id)).', '.$this->db->escape($osselection).', '.$this->db->escape($meta['version']).', '.$curtime.')');
    $fileid = strval($this->db->insert_id());
    
    // Add the compartibility elements
    foreach ($meta['targetApplication'] as $app){
      $this->db->query('INSERT
        INTO addon_compartibility
          (file_id, application_id, min_version, max_version)
        VALUES
          ('.$fileid.', '.$this->db->escape($app['id']).', '.$this->db->escape($app['minVersion']).', '.$this->db->escape($app['maxVersion']).')');
    }
    
    // Add the changelog
    $this->setAddonVersionLocalizedChangelog($id, $meta['version'], $osselection, $localized_changelog);
    
    // Store the file
    if ($debug_mode_do_not_move){
      copy($file, $this->storage.$fileid.'.xpi');
    } else {
      move_uploaded_file($file, $this->storage.$fileid.'.xpi');
    }
  }
  
  // Helper for generating a version out of a result
  protected function getVersionFromRow($row){
    return array(
      'version' => $row->version,
      'creationdate' => $row->creationdate,
      'osselection' => $row->osselection,
      'file' => $this->storage.$row->fileId.'.xpi',
      
      'changelog' => $row->changelog
    );
  }
  
  protected function getSelectVersionSQL($addonid, $locale='def', $additionalwhere='', $additionalcolumns='', $additionaljoin=''){
    return 'SELECT
        addon_files.version AS version,
        addon_files.created_on AS creationdate,
        addon_files.id AS fileId,
        addon_files.os AS osselection,
        IF( ISNULL( meta.text ), meta_fallback.text, meta.text) AS changelog
        '.$additionalcolumns.'
      FROM
        addon_files
        LEFT JOIN addon_changelog meta ON (addon_files.id = meta.file_id AND meta.locale = '.$this->db->escape($locale).')
        '.$additionaljoin.'
        INNER JOIN addon_changelog meta_fallback ON (addon_files.id = meta_fallback.file_id AND meta_fallback.locale = \'def\')
      WHERE (addon_files.addon_id = '.strval(intval($addonid)).') '.$additionalwhere;
  }
  
  // get the most recent version (for a specific OS)
  public function getMostRecentAddonVersion($id, $osselection, $locale='def'){
    // We use the index order here, as we can assume newly added rows are more recent versions.
    
    // Filtering OS selection
    $filter = '';
    if ($osselection !== null){
      $filter .= 'AND ((addon_files.os = '.$this->db->escape($osselection).') OR (addon_files.os IS NULL))';
    }
    // Query our version
    $query = $this->db->query($this->getSelectVersionSQL($id, $locale, $filter).'
      LIMIT 1');
    if ($query->num_rows() == 0){
      return null;
    }
    return $this->getVersionFromRow($query->row());
  }
  
  // get the most recent version for a specific application's version.
  public function getMostRecentAddonVersionForApplication($id, $applicationid, $version, $osselection, $locale='def'){
    // We use the index order here, as we can assume newly added rows are more recent versions.
    
    // Our additional rules we need for application requirement
    $join = 'INNER JOIN addon_compartibility comp ON (addon_files.id = comp.file_id AND comp.application_id = '.$this->db->escape($applicationid).')';
    $columns = ', addon_compartibility.min_version AS minVersion, addon_compartibility.max_version AS maxVersion';
    
    // Filtering OS selection
    $filter = '';
    if ($osselection !== null){
      $filter .= ' AND ((addon_files.os = '.$this->db->escape($osselection).') OR (addon_files.os IS NULL))';
    }
    
    // Query our version
    $query = $this->db->query($this->getSelectVersionSQL($id, $locale, $filter, $columns, $join));
    
    // Go through the results, and take the first one suitable for the application's version.
    if ($query->num_rows() > 0){
      foreach ($query->result() as $row){
        if (
            $this->isVersionEqualOrGreaterThan($version, $row->minVersion)
          and
            $this->isVersionEqualOrGreaterThan($row->maxVersion, $version)){
          return $this->getVersionFromRow($row);
        }
      }
    }
    
    throw new Exception('Application or version is not supported.');
  }
  
  // get array of n versions, ofsetting page pages
  public function getAddonVersionHistory($id, $n, $page, $locale='def'){
    // We use the index order here, as we can assume newly added rows are more recent versions.
    
    $query = $this->db->query($this->getSelectVersionSQL($id, $locale).'
      LIMIT '.strval(intval($page*$n)).','.strval(intval($n)));
    
    // Generate result array
    $result = array();
    if ($query->num_rows() > 0){
      foreach ($query->result() as $row){
        $result[] = $this->getVersionFromRow($row);
      }
    }
    
    return $result;
  }
  
  // helper for getting the file id
  protected function getAddonVersionFileId($id, $version, $osselection){
    $query = $this->db->query('
      SELECT id, os FROM addon_files WHERE version = '.$this->db->escape($version));
    $fileid = null;
    if ($query->num_rows() > 0){
      foreach ($query->result() as $row){
        if (($row->os == $osselection) or ($row->os === null)){
          return strval($row->id);
        }
      }
    }
    if ($fileid == null)
      throw new Exception('Add-on version not found');
  }
  
  // get localized changelog for a specific add-on version. $version is a version number string
  public function getAddonVersionLocalizedChangelog($id, $version, $osselection){
    $fileid = $this->getAddonVersionFileId($id, $version, $osselection);
    
    // Get the localized changlog
    $query = $this->db->query('
      SELECT locale, text FROM addon_changelog WHERE file_id = '.$fileid);
    
    // Generate result array
    $result = array();
    if ($query->num_rows() > 0){
      foreach ($query->result() as $row){
        $result[] = array(
          'locale' => $row->locale,
          'changelog' => $row->text
        );
      }
    }
    
    return $result;
  }
  
  // set localized changelog for a specific add-on version
  public function setAddonVersionLocalizedChangelog($id, $version, $osselection, $localized_changelog){
    $fileid = $this->getAddonVersionFileId($id, $version, $osselection);
    
    // Delete all entries, as we re-insert them. TODO: diff and update/insert/delete? locking?
    $this->db->query('DELETE FROM addon_changelog WHERE file_id = '.$fileid);
    
    // Go through the changelog
    foreach ($localized_changelog as $entry){
      // Insert changelog entry
      $locale = $entry['locale'];
      $text = $entry['changelog'];
      $this->db->query('
        INSERT INTO addon_changelog (file_id, locale, text)
        VALUES ('.$fileid.', '.$this->db->escape($locale).', '.$this->db->escape($text).')');
    }
  }
  
  // remove an existing version. Should have a additional user confirmation, as old versions can't get re-uploaded.
  public function removeAddonVersion($id, $version, $osselection){
    $fileid = $this->getAddonVersionFileId($id, $version, $osselection);
    
    // The database will cascade this to dependent tables
    $this->db->query('DELETE FROM addon_files WHERE id = '.$fileid);
  }
  
  /**
   * Ownership functions
   * Used to manage add-on ownerships
   */
  // add a user as owner for an add-on
  public function addAddonOwner($id, $owner){
    $uid = $this->getIdForUser($owner);
    
    // Insert relation
    $this->db->query('INSERT INTO addon_ownerships (addon_id, user_id) VALUES ('.strval(intval($id)).', '.$uid.')');
  }
  
  // remove a user from and add-on's list of owners
  public function removeAddonOwner($id, $owner){
    $uid = $this->getIdForUser($owner);
    
    // Remove relation
    $this->db->query('DELETE FROM addon_ownerships WHERE addon_id = '.strval(intval($id)).' AND user_id = '.$uid);
  }
  
  // checks if the given user is an owner of the given add-on
  public function isAddonOwner($id, $owner){
    $uid = $this->getIdForUser($owner);
    
    // Get relation
    $query = $this->db->query('SELECT 1 FROM addon_ownerships WHERE addon_id = '.strval(intval($id)).' AND user_id = '.$uid);
    return $query->num_rows() > 0;
  }
  
  /**
   * Rating functions
   * Used to rate add-ons, and manage these ratings.
   */
  // adds a rating. Note that this replaces the old rating for the addon if there is any (but the old ratings stay available).
  public function addAddonRating($id, $user, $rating, $text){
    $uid = $this->getIdForUser($user);
    $curtime = strval(time());
    
    // Insert data
    $this->db->query('
      INSERT INTO addon_ratings (users_id, addons_id, text, rating, created_on)
      VALUES ('.$uid.', '.strval(intval($id)).', '.$this->db->escape($text).', '.strval(intval($rating)).', '.$curtime.')');
  }
  
  // removes a rating. This should be available for trusted members only, as it destroys the rating history
  public function removeAddonRating($id, $rating){
    $uid = $this->getIdForUser($rating['user']);
    // The following query assumes that it is not possible to rate more than one time within a millisecond for one add-on.
    $this->db->query('DELETE FROM addon_ratings WHERE users_id = '.$uid.' AND creationdate = '.strval(intval($rating['creationdate'])).' AND addons_id = '.strval(intval($id)));
  }
  
  // Sql part for rating structure queries
  protected function getAddonRatingSQL(){
    return '
      SELECT
        users.username AS user,
        users.id AS userId,
        addon_ratings.created_on AS creationdate,
        addon_ratings.text AS text,
        addon_ratings.rating AS rating,
        addon_ratings.addons_id AS addonId
      FROM
        addon_ratings,
        INNER JOIN users ON (addon_ratings.users_id = users.id)';
  }
  
  // helper for generating a rating structure
  protected function generateRating($row){
    // Check for old versions
    $check = $this->db->query('SELECT COUNT(*) AS vCount FROM addon_ratings WHERE users_id = '.strval($row->userId).' AND addons_id = '.strval($row->addonId));
    $oldversions = $check->row()->vCount -1;
    
    // Generate result
    return array(
      'user' => $row->user,
      'creationdate' => $row->creationdate,
      'text' => $row->text,
      'rating' => $row->rating,
      
      'numPrevious' => $oldversions
    );
  }
  
  // get a list of n most recent active ratings, ofsetting page pages, for an add-on
  public function getAddonRatings($id, $n, $page){
    $query = $this->db->query($this->getAddonRatingSQL().'
      WHERE
        addon_ratings.addons_id = '.strval(intval($id)).'
        AND '.$this->getAddonActiveRatingSQLCondition($id).'
      LIMIT '.strval(intval($page*$n)).','.strval(intval($n)));
    
    $result = array();
    if ($query->num_rows() > 0){
      foreach ($query->result() as $row){
        $return[] = $this->generateRating($row);
      }
    }
    return $result;
  }
  
  // get a list of all ratings for a add-on by a specific user
  public function getAddonRatingsByUser($id, $user){
    $uid = $this->getIdForUser($user);
    
    $query = $this->db->query($this->getAddonRatingSQL().'
      WHERE
        addon_ratings.addons_id = '.strval(intval($id)).'
        addon_ratings.users_id = '.$uid);
    
    $result = array();
    if ($query->num_rows() > 0){
      foreach ($query->result() as $row){
        $return[] = $this->generateRating($row);
      }
    }
    return $result;
  }
  
  // get a list of n most recent active ratings, ofsetting page pages, issued by a specific user
  public function getUserRatings($user, $n, $page){ 
    $uid = $this->getIdForUser($user);
    
    $query = $this->db->query($this->getAddonRatingSQL().'
      WHERE
        addon_ratings.users_id = '.$uid.'
        AND '.$this->getAddonActiveRatingSQLCondition($id).'
      LIMIT '.strval(intval($page*$n)).','.strval(intval($n)));
    
    $result = array();
    if ($query->num_rows() > 0){
      foreach ($query->result() as $row){
        $return[] = $this->generateRating($row);
      }
    }
    return $result;
  }
  
  /**
   * Image functions
   * Used to get icons and screenshot file names. No Database involved here!
   */
  // upload a new PNG 32x32px icon file (which will get moved via move_uploaded_file!). Maximum size 10 KB. It replaces the old one, if any. If uploaded_icon is null, the image will get deleted.
  public function updateIcon($id, $uploaded_icon){
    if ($uploaded_icon !== null){
      // Validate icon TODO: support resizing, converting?
      $size = filesize($uploaded_image);
      if (($size === false) or ($size > 10*1024)){
        die('Invalid image file: Larger than 10 KB');
      }
      $file_info = getimagesize($uploaded_icon);
      if(empty($file_info) or ($file_info['mime'] != 'image/png') or ($file_info[0] != 32) or ($file_info[1] != 32)){
        die('Invalid image file: Not a 32x32px PNG');
      }
    }
    
    // Exchange the data
    $ifolder = $this->img_storage.strval(intval($id));
    $ipath = $ifolder.'/icon.png';
    if (!is_dir($ifolder)){
      mkdir($ifolder, 0750);
    } else {
      if (file_exists($ipath)){
        unlink($ipath);
      }
    }
    if ($uploaded_icon !== null){
      move_uploaded_file($uploaded_icon, $ipath);
    }
  }
  
  // Get icon file name
  public function getIcon($id){
    return $this->img_storage.strval(intval($id)).'/icon.png';
  }
  
  // Add a screenshot (JPG, GIF or PNG allowed, file size max. 200 KB)
  public function addScreenshot($id, $uploaded_image){
    // Validate image
    $size = filesize($uploaded_image);
    if (($size === false) or ($size > 200*1024)){
      die('Invalid image file: Larger than 200 KB');
    }
    $file_info = getimagesize($uploaded_image);
    if(empty($file_info)){
      die('Invalid image file: Image parsing failed');
    }
    $filetype = null;
    if ($file_info['mime'] != 'image/png'){
      $filetype = 'png';
    } else if ($file_info['mime'] != 'image/jpeg'){
      $filetype = 'jpg';
    } else if($file_info['mime'] != 'image/gif'){
      $filetype = 'gif';
    } else {
      die('Invalid image file: Only PNG, JPEG and GIF allowed');
    }
    
    // Add the image
    $ifolder = $this->img_storage.strval(intval($id));
    $number = 0;
    $existing = $this->getScreenshots($id);
    if (!empty($existing)){
      $filename = end($existing);
      $number = intval(substr($filename, 11, strlen($filename)-11-4))+1;
    }
    move_uploaded_file($uploaded_icon, $ifolder.'/'.strval($number).'.'.$filetype);
  }
  
  // Get screenshot file name array
  public function getScreenshots($id){
    $ifolder = $this->img_storage.strval(intval($id));
    $files = scandir($ifolder);
    
    $result = array();
    foreach($files as $file){
      if (strpos($file, 'screenshot-') === 0){
        $result[] = $ifolder.'/'.$file;
      }
    }
    return $result;
  }
  
  // Remove screenshot represented by file name (e.g. 'screenshot-5.jpg')
  public function removeScreenshot($id, $filename){
    $ifolder = $this->img_storage.strval(intval($id));
    $ipath = $ifolder.'/'.$filename;
    if (file_exists($ipath)){
      unlink($ipath);
    }
  }
} ?>