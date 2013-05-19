<?php defined('BASEPATH') OR exit('No direct script access allowed');

if ( ! class_exists('Controller'))
{
  class Controller extends CI_Controller {}
}

/**
 * Tests for featherweight, remove this file for production environments.
 * 
 * Currently not tested within the addon mode:
 * -> Screenshots/icons
 * -> owner features
 * -> additional listing stuff
 */

class Test extends Controller{

  public function __construct(){
    parent::__construct();
    
    $this->load->helper('url');
    $this->load->library('ion_auth');
    
    // Load the things we want to test
    $this->load->database();
    $this->load->model('Addons_model', 'addons');
    $this->load->library('xpi_parser');
  }
  
  
  // Helper to ensure only admins can use tests, should be used in every method here
  protected function lockTests(){
    if (!$this->ion_auth->is_admin())
    {
      //redirect them to the home page because they must be an administrator to view this
      redirect($this->config->item('base_url'), 'refresh');
    }
  }
  
  public function index(){
    $this->lockTests();
    
    echo '<p style="font-weight: bold; border: 1px solid red; color:red">THE TESTS ALTER / DESTROY YOUR DATABASE CONTENT.<br />TESTS SHOULD NOT BE AVAILABLE NOR USED IN PRODUCTIVE ENVIRONMENTS.<br />You have been warned.</p>';
    
    echo '<h1>Featherweight Add-on Platform: Tests</h1>';
    echo '<p>Remove application/controllers/test.php before making the installation available to the public to remove testing support.</p>';
    
    echo '<h2>XPI Parser</h2>';
    echo '<p>These tests will check if the add-on parser works or not.</p>';
    echo anchor('test/xpiparserStart', 'Start Testcase');
    
    echo '<h2>Add-on Model</h2>';
    echo '<p>These tests will check if the add-on model works or not. You need a user named "Administrator" which will be used for testing purposes (this user exists by default).</p>';
    echo anchor('test/addonmodelStart', 'Start Testcase');
  }
  
  // ------------------------------- XPI PARSER -----------------------------------------
  
  public function xpiparserStart(){
    $this->lockTests();
    
    echo '<h1>XPI-Parser Testcase: Start</h1>';
    
    echo '<p>For testing, you first need to upload an valid add-on which will then be used to test the xpi Parser:</p>';
    echo '<form action="'.site_url('test/xpiparser').'" method="post" enctype="multipart/form-data">
      <input name="file" type="file" />
      <input type="submit" />
    </form>';
  }
  
  public function xpiparser(){
    $this->lockTests();
    
    echo '<h1>XPI-Parser Testcase: Results</h1>';
    echo '<pre>xpi_parser->getMetadata:'."\n";
    
    // Do the test:
    $meta = $this->xpi_parser->getMetadata($_FILES['file']['tmp_name']);
    print_r($meta);
    
    echo '</pre>';
  }
  
  // ------------------------------ ADD-ON MODEL ----------------------------------------
  
  public function addonmodelStart(){
    $this->lockTests();
    
    echo '<h1>Add-on Model Testcase: Start</h1>';
    
    echo '<p>For testing, you first need to upload an valid add-on which will then be used to test the database model:</p>';
    echo '<form action="'.site_url('test/addonmodelDatabase').'" method="post" enctype="multipart/form-data">
      <input name="file" type="file" />
      <input type="submit" />
    </form>';
  }
  
  public function addonmodelDatabase(){
    $this->lockTests();
    
    echo '<h1>Add-on Model Testcase: Results</h1>';
    echo '<pre>';
    
    // Do the test:
    echo 'Getting metadata...'."\n";
    $meta = $this->xpi_parser->getMetadata($_FILES['file']['tmp_name']);
    echo 'UUID: '.$meta['id']."\n";
    echo 'Type: '.$meta['type']."\n";
    echo 'Version: '.$meta['version']."\n";
    echo 'Unlocalized Name: '.$meta['name']['default']."\n";
    echo 'Unlocalized Description: '.$meta['description']['default']."\n\n";
    
    echo 'Insert Add-on to database, with some fake credits and locale, owned by \'Administrator\'...'."\n";
    $id = $this->addons->storeAddon($meta['id'], $meta['type'],
      array(
        array(
          'description' => $meta['description']['default'],
          'name' => $meta['name']['default'],
          'homepage' => 'example.com',
          'email' => null,
          'locale' => 'def'
        ),
        array(
          'description' => $meta['description']['default'].'[en-US]',
          'name' => $meta['name']['default'].'[en-US]',
          'homepage' => 'en-us.example.com',
          'email' => 'support@en-us.example.com',
          'locale' => 'en-US'
        )
      ),
      array(
        'authors' => array('author one', 'author two'),
        'contributors' => array(),
        'translators' => array('nobody')
      ),
      array('Administrator'));
    echo 'New Add-on has ID: '.$id."\n\n";
    
    echo 'Query Add-on from Database [via id, locale=de]: ';
    print_r($this->addons->getAddonById($id, 'de'));
    echo 'Query Add-on from Database [via uuid, locale=en-US]: ';
    print_r($this->addons->getAddonByUUID($meta['id'], 'en-US'));
    echo "\n";
    
    echo 'Adding initial version for unix...'."\n";
    $this->addons->addAddonVersion(
      $id,
      $_FILES['file']['tmp_name'],
      'unix',
      array(
        array(
          'changelog' => 'What the changelog?',
          'locale' => 'def'
        ),
        array(
          'changelog' => 'What the en-US changelog?',
          'locale' => 'en-US'
        )
      ),
      true);
    echo 'Get version via getMostRecentAddonVersion, locale en-US: ';
    print_r($this->addons->getMostRecentAddonVersion($id, 'unix', 'en-US'));
    echo 'Adding initial version for windows...'."\n";
    $this->addons->addAddonVersion(
      $id,
      $_FILES['file']['tmp_name'],
      'windows',
      array(
        array(
          'changelog' => 'What the changelog for Windows?',
          'locale' => 'def'
        ),
        array(
          'changelog' => 'What the en-US changelog for Windows?',
          'locale' => 'en-US'
        )
      ));
    echo 'Get version via getMostRecentAddonVersion, locale en-US: ';
    print_r($this->addons->getMostRecentAddonVersion($id, 'windows', 'en-US'));
    echo "\n";
    
    echo 'Changelog listing (Page 1 with 10 entries):';
    print_r($this->addons->getAddonVersionHistory($id, 10, 0));
    echo 'Changelog listing (Page 2 with 1 entry, in en-US):';
    print_r($this->addons->getAddonVersionHistory($id, 1, 1, 'en-US'));
    echo "\n";
    
    echo 'Remove Unix version, new full changelog listing:';
    $this->addons->removeAddonVersion($id, $meta['version'], 'unix');
    print_r($this->addons->getAddonVersionHistory($id, 10, 0));
    
    echo 'List tags: ';
    print_r($this->addons->getAddonTags($id));
    echo 'Adding "test" and "test2" tag: ';
    $this->addons->tagAddon($id, 'test');
    $this->addons->tagAddon($id, 'test2');
    print_r($this->addons->getAddonTags($id));
    echo 'Removing "test" tag: ';
    $this->addons->untagAddon($id, 'test');
    print_r($this->addons->getAddonTags($id));
    echo "\n";
    
    echo 'Is admin owner:';
    print_r($this->addons->isAddonOwner($id, 'Administrator'));
    echo "\n".'Is admin owner after removing him:';
    $this->addons->removeAddonOwner($id, 'Administrator');
    print_r($this->addons->isAddonOwner($id, 'Administrator'));
    echo "\n".'Is admin owner after adding him:';
    $this->addons->addAddonOwner($id, 'Administrator');
    print_r($this->addons->isAddonOwner($id, 'Administrator'));
    echo "\n\n";
    
    echo 'Let the admin rate 3, then 5 stars... and download it 3 times, then list the first page of type add-ons:';
    $this->addons->addAddonRating($id, 'Administrator', 3, 'Might have some issues...');
    $this->addons->incAddonVersionDownloadCount($id);
    sleep(10);
    $this->addons->addAddonRating($id, 'Administrator', 5, null);
    $this->addons->incAddonVersionDownloadCount($id);
    $this->addons->incAddonVersionDownloadCount($id);
    print_r($this->addons->getAddonsByType(10,0,$meta['type']));
    echo "\n";
    
    echo '</pre>';
  }
}

?>