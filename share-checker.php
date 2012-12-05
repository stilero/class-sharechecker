<?php
/**
 * Sharechecker_Class
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

class ShareChecker extends ShareLogger{
    
    public static $CATS_FUNCTION_INCLUDE = '0';
    public static $CATS_FUNCTION_EXCLUDE = '1';
    protected $_shareNewerThanDate;
    protected $_categories;
    protected $_categoriesFunction;
    protected $_delayInMin;
   
    /**
     * 
     * @param JArticle $JArticle An instance of an JArticle of the JArticle Class
     * @param String $table The table name for the Logs
     * @param String $plgLangPrefix The prefix used in language files for the plugin.
     * @param Date $shareNewerThanDate Only share items newer than this date
     * @param array $categories Array with category ids
     * @param integer $categoriesFunction 0/1 to include or exclude categories
     * @param integer $delayInMin the delay time in minutes. Who would have guessed?
     */
    public function __construct($JArticle, $table, $plgLangPrefix, $shareNewerThanDate='', $categories='', $categoriesFunction=0, $delayInMin=1) {
        parent::__construct($JArticle, $table, $plgLangPrefix);
        $this->_shareNewerThanDate = $shareNewerThanDate;
        $this->_categories = $categories;
        $this->_categoriesFunction = $categoriesFunction;
        $this->_delayInMin = $delayInMin;
    }
    
    /**
     * Convenient method for asserting that a boolean is true
     * @param boolean $boolActual
     * @param String $errorType
     * @param String $errorMsg
     * @return boolean
     */
    protected function isTrue($boolActual, $errorType, $errorMsg){
        if($this->hasError()){
            return FALSE;
        }
        if(!$boolActual){
            $this->setError($errorType, $this->_plgLangPrefix.$errorMsg);
            return FALSE;
        }
        return TRUE;
    }
    
    /**
     * Convenient method for asserting that a boolean is false
     * @param boolean $boolActual
     * @param String $errorType
     * @param String $errorMsg
     * @return boolean
     */
    protected function isFalse($boolActual, $errorType, $errorMsg){
        return $this->isTrue(!$boolActual, $errorType, $errorMsg);
    }
    
    /**
     * Convenient method for asserting that a value is bigger than the other
     * @param integer $bigger
     * @param integer $smaller
     * @param String $errorType
     * @param String $errorMsg
     * @return boolean
     */
    protected function isBiggerThan($bigger, $smaller, $errorType, $errorMsg){
        if($this->hasError()){
            return FALSE;
        }
        if($bigger < $smaller){
            $this->setError($errorType, $this->_plgLangPrefix.$errorMsg);
            return FALSE;
        }
        return TRUE;
    }
    
    /**
     * Check if the article is published
     * @return boolean
     */
    public function isPublished() {
        return $this->isTrue($this->_article->isPublished, self::$ERROR_TYPE_NOTICE, $this->_plgLangPrefix.'NOTACTIVE');
    }
    
    /**
     * Check if it is a proper article
     * @return boolean
     */
    public function isArticle() {
        $isNumeric = is_numeric($this->_article->id);
        return $this->isTrue($isNumeric, self::$ERROR_TYPE_ERROR, 'NOT_OBJECT');
    }
    
    /**
     * Check if the article is newer than the settings allows
     * @return boolean
     */
    public function isNewEnough() {
        $publishDate = $this->_article->publish_up;
        if($this->_shareNewerThanDate == '' || $publishDate == '0000-00-00 00:00:00'){
            return true;
        }
        return $this->isBiggerThan($publishDate, $this->_shareNewerThanDate, self::$ERROR_TYPE_NOTICE, 'ITEM_OLD');
    }
    
    /**
     * Check if atricle is public
     * @return boolean
     */
    public function isPublic(){
        return $this->isTrue($this->_article->isPublic, self::$ERROR_TYPE_NOTICE, 'RESTRICT');
    }
    
    /**
     * Check if category should be shared or excluded
     * @return boolean
     */
    public function isCategoryToShare(){
        $categories = $this->_categories;
        if ( $categories == "" || $categories[0]=="" ){
            return TRUE;
        }
        $include = true;
        if($this->_categoriesFunction == self::$CATS_FUNCTION_EXCLUDE){
            $include = false;
        }
        $catid = $this->_article->catid;
        $foundInCats = in_array( $catid, $categories );
        if($include){
            return $this->isTrue(!$foundInCats, self::$ERROR_TYPE_NOTICE, 'NOTSECTION');
        }else {
            return $this->isTrue($foundInCats, self::$ERROR_TYPE_NOTICE, 'NOTSECTION');
        }
    }
    
    /**
     * Check if an article is shared too early
     * @return boolean
     */
    public function isTooEarly(){
        $delayInMin = ( !is_numeric($this->_delayInMin) || $this->_delayInMin < 0 )? 1: $this->_delayInMin;
        if($delayInMin > 60){
            $delayInMin = 60;
        }
        $now = date("Y-m-d H:i:s");
        $db =& JFactory::getDbo();
        $query = $db->getQuery(true);
        $query->select('id');
        $query->from($db->nameQuote($this->_table));
        $query->where("date > SUBTIME(".$db->quote($now).",'0 0:".$delayInMin.":0.0')");
        $db->setQuery($query);
        $isTooEarly = $db->loadObject();
        if($isTooEarly){
            $this->setError(self::$ERROR_TYPE_NOTICE, 'DELAYED');
            return TRUE;
        }
        return FALSE;
    }
    
    /**
     * Check if the article is already shared
     * @return boolean
     */
    public function isShared(){
        $isShared = $this->isLogged();
        return $this->isFalse($isShared, self::$ERROR_TYPE_NOTICE, 'ALREADYSENT2');
    }
}