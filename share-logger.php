<?php
/**
 * Share Logger Class
 * Handles logs to the DB
 *
 * @version  1.0
 * @package Stilero
 * @subpackage Sharechecker_Class
 * @author Daniel Eliasson (joomla@stilero.com)
 * @copyright  (C) 2012-dec-04 Stilero Webdesign (www.stilero.com)
 * @license	GNU General Public License version 2 or later.
 * @link http://www.stilero.com
 */

// no direct access
defined('_JEXEC') or die('Restricted access'); 

class ShareLogger extends ShareErrors{
    
    protected $_table;
    protected $_plgLangPrefix;
    protected $_article;

    /**
     * 
     * @param JArticle $JArticle An instance of an JArticle of the JArticle Class
     * @param String $table The table name for the Logs
     * @param String $plgLangPrefix The prefix used in language files for the plugin.
     */
    public function __construct($JArticle, $table, $plgLangPrefix) {
        $this->_article = $JArticle->getArticle();
        $this->_table = $table;
        $this->_plgLangPrefix = $plgLangPrefix;
        $this->checkTables();
    }
    
    /**
     * Check if the log-table already exists
     * @return boolean 
     * @since 1.6
     */
    public function isTableExisting() {
        $db =& JFactory::getDbo();
        $query = 'DESC '.$db->nameQuote($this->_table);
        $db->setQuery($query);
        $isTableFound = $db->query();
        if($isTableFound){
            return TRUE;
        }
        return FALSE;
    }
    
    /**
     * Create the table from scratch
     * @return boolean false on fail, true on success
     * @since 1.5
     */
    public function createTable() {
        $db =& JFactory::getDbo();
        $queryDrop = 'DROP TABLE IF EXISTS '.$db->nameQuote($this->_table);
        $queryCreate = "CREATE TABLE ".$db->nameQuote($this->_table)." (
                `id` int(11) NOT NULL auto_increment,
                `article_id` int(11) NOT NULL default 0,
                `cat_id` int(11) NOT NULL default 0,
                `articlelink` varchar(255) NOT NULL default '',
                `date` datetime NOT NULL default '0000-00-00 00:00:00',
                `language` char(7) NOT NULL default '',
                PRIMARY KEY  (`id`)
                ) DEFAULT CHARSET=utf8;";
        $db->setQuery($queryDrop);
        $resultDrop = $db->query();
        if(!$resultDrop){
            $this->setError(self::$ERROR_TYPE_ERROR, $this->_plgLangPrefix.'DROP_TABLE_FAILED', $queryDrop);
            return false;
        }
        $db->setQuery($queryCreate);
        $resultCreate = $db->query();
        if($resultCreate){
            return TRUE;
        }
        $this->setError(self::$ERROR_TYPE_ERROR, $this->_plgLangPrefix.'CREATE_TABLE_FAILED', $queryCreate);
        return FALSE;
    }
    
    /**
     * Save to log
     * @return boolean true on success
     * @since 1.6
     */
    public function log() {
        $itemId = $this->_article->id;
        $catId = $this->_article->catid;
        $option = JRequest::getCmd('option');
        $now = date("Y-m-d H:i:s");
        $language = $this->_article->language;
        $db =& JFactory::getDbo();
        $query = $db->getQuery(true);
        $query->insert($db->nameQuote($this->_table));
        $query->set('article_id = '.$db->quote((int)$itemId));
        $query->set('cat_id = '.$db->quote((int)$catId));
        $query->set('articlelink = '.$db->quote($option));
        $query->set('date = '.$db->quote($now));
        $query->set('language = '.$db->quote($language));
        $db->setQuery($query);
        $result = $db->query($query);
        if($result){
            return true;
        }
        $this->setError(self::$ERROR_TYPE_ERROR, $this->_plgLangPrefix.'SAVE_LOG_FAILED', $query->dump());
        return FALSE;
    }
    
    /**
     * Delete log from table
     * @return boolean true on success
     * @since 1.6
     */
    public function unlog() {
        $db =& JFactory::getDbo();
        $query = $db->getQuery(true);
        $query->delete($db->nameQuote($this->_table));
        $query->where('article_id = '.$db->quote((int)$this->_article->id));
        $query->where('articlelink = '.$db->quote(JRequest::getVar('option')));
        $db->setQuery($query);
        $result = $db->query($query);
        if($result){
            return true;
        }
        $this->setError(self::$ERROR_TYPE_ERROR, $this->_plgLangPrefix.'UNLOG_FAILED', $query->dump());
        return FALSE;
    }
    
    /**
     * Check if a log aldready exists for the actual article
     * @return boolean true on found
     * @since 1.6
     */
    public function isLogged(){
        $db =& JFactory::getDbo();
        $query = $db->getQuery(TRUE);
        $query->select('id');
        $query->from($db->nameQuote($this->_table));
        $query->where('article_id = '.$db->quote((int)$this->_article->id));
        $query->where('articlelink = '.$db->quote(JRequest::getVar('option')));
        $db->setQuery($query);
        $isLogged = $db->loadObject();
        if($isLogged != NULL){
            $this->setError(self::$ERROR_TYPE_NOTICE, $this->_plgLangPrefix.'ALREADYSENT', $query->dump());
            return TRUE;
        }
        return FALSE;
    }
    
    /**
     * Check for table and create if not found.
     */
    protected function checkTables(){
        if(!$this->isTableExisting()){
            $this->createTable();
        }
    }
    
    public function setJArticle($JArticle){
        $this->_article = $JArticle;
    }
}
