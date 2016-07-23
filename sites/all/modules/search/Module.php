<?php
/**
 * Integrates the search module with the rest of the application.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Search_Module extends P4Cms_Module_Integration
{
    // the maximum recursive depth for enhancing user queries
    const  MAX_DEPTH                    = 10;

    // the required prefix for wildcard queries to avoid performance problems.
    const   MIN_PREFIX_LENGTH           = 2;
    const   MAX_RESULTS                 = 10000;

    // the QueryPaserException error codes
    const  ERROR_TWO_CHARS_LEXEME       = 1;
    const  ERROR_LEXEME_MODIFIER        = 2;

    const  ACTIVE_INDEX_PATH            = 'search-index';
    const  NEW_DOCUMENT_COUNT_FILE      = 'search-newly-added-document.count';

    // The default number of new documents with triggers auto optimization
    const  DEFAULT_MAX_BUFFERED_DOCS    = 10;
    const  DEFAULT_MERGE_FACTOR         = 10;

    /**
     * @var array   the list of existing search instances (Zend_Search_Lucene_Interface objects)
     */
    protected static  $_searchInstances  = array();

    /**
     * Subscribe to search index topic.
     */
    public static function load()
    {
        // listen for documents to be indexed.
        P4Cms_PubSub::subscribe('p4cms.search.add',
            function($document)
            {
                // if we don't have a lucene doc, bail out.
                if (!$document = Search_Module::prepareDocument($document)) {
                    return;
                }

                // add the document
                Search_Module::factory()->addDocument($document);
            }
        );

        // listen for documents to be removed from index.
        P4Cms_PubSub::subscribe('p4cms.search.delete',
            function($document)
            {
                // if we don't have a lucene doc, bail out.
                if (!$document = Search_Module::prepareDocument($document)) {
                    return;
                }

                // remove documents with matching key fields.
                $index     = Search_Module::factory();
                $keyFields = array('uri', 'contentId');
                foreach ($keyFields as $keyField) {
                    if (in_array($keyField, $document->getFieldNames())) {

                        // search for existing documents with matching key field.
                        $term = new Zend_Search_Lucene_Index_Term(
                            $document->getFieldValue($keyField),
                            $keyField
                        );

                        // remove matches.
                        foreach ($index->termDocs($term) as $id) {
                            $index->delete($id);
                        }
                    }
                }
            }
        );

        /**
         * listen for documents to be updated in the index.
         *
         * @publishes   p4cms.search.delete
         *              Perform operations when an entry is deleted from the search-index.
         *              Note: Updates to existing entries are accomplished via delete/add.
         *              Zend_Search_Lucene_Document|P4Cms_Content   $document   The entry being
         *                                                                      deleted.
         *
         * @publishes   p4cms.search.add
         *              Perform operations when an entry is added to the search index.
         *              Note: Updates to existing entries are accomplished via delete/add.
         *              Zend_Search_Lucene_Document|P4Cms_Content   $document   The entry being
         *                                                                      added.
         */
        P4Cms_PubSub::subscribe('p4cms.search.update',
            function($document)
            {
                // if we don't have a lucene doc, bail out.
                if (!$document = Search_Module::prepareDocument($document)) {
                    return;
                }

                // lucene does not have a 'update' function, so
                // we publish to the delete and add topics instead.
                P4Cms_PubSub::publish('p4cms.search.delete', $document);
                P4Cms_PubSub::publish('p4cms.search.add',    $document);
            }
        );

        // steal content's search form to use lucene
        P4Cms_PubSub::subscribe('p4cms.content.grid.form',
            function(Zend_Form $form)
            {
                $search = $form->getSubForm('search');
                if (!$search) {
                    return;
                }

                $form->removeSubForm('search');
                $form->addSubForm($search, 'lucene');
            }
        );

        // filter content list by keyword search.
        P4Cms_PubSub::subscribe('p4cms.content.grid.populate',
            function(P4Cms_Record_Query $query, Zend_Form $form)
            {
                $values = $form->getValues();

                // extract search query.
                $searchQuery = isset($values['lucene']['query'])
                    ? $values['lucene']['query']
                    : null;

                // early exit if no query.
                if (!$searchQuery) {
                    return;
                }

                $filter = ($query->getFilter()) ?: new P4Cms_Record_Filter;

                if ($filter->getOption('lucene')) {
                    $searchQuery = (is_array($filter->getOption('lucene')))
                                 ? array_intersect($filter->getOption('lucene'), array($searchQuery))
                                 : $filter->getOption('lucene') . ' ' . $searchQuery;
                }

                $filter->setOption('lucene', $searchQuery);
                $query->setFilter($filter);
            }
        );

        // Allows for filtering a content query by lucene.
        // Used by creating a filter on the query with the 'lucene' option set to a string
        // or array containing keywords.
        P4Cms_PubSub::subscribe('p4cms.content.record.query',
            function(P4Cms_Record_Query $query, P4Cms_Record_Adapter $adapter)
            {
                $filter = $query->getFilter();
                if (!$filter || !$filter instanceof P4Cms_Record_Filter) {
                    return;
                }

                // see if the lucene filter option is set
                $keywords = $filter->getOption('lucene');
                if (!$keywords || (!is_string($keywords) && !is_array($keywords))) {
                    return;
                }

                if (is_array($keywords)) {
                    $keywords = implode(' ', $keywords);
                }

                // collect matching content ids.
                $ids = array();
                foreach (Search_Module::find($keywords) as $result) {
                    $document = $result->getDocument();
                    if (in_array('contentId', $document->getFieldNames())) {
                        $ids[] = $document->contentId;
                    }
                }

                // add content ids to query paths.
                $query->addPaths($ids, true);
            }
        );

        // copy the search index when a new branch is created.
        P4Cms_PubSub::subscribe(
            'p4cms.site.branch.add.postSubmit',
            function($target, $source, $adapter)
            {
                $sourcePath = $source->getDataPath() . '/' . Search_Module::ACTIVE_INDEX_PATH;
                $targetPath = $target->getDataPath() . '/' . Search_Module::ACTIVE_INDEX_PATH;

                // if a search index exists, it means the target branch has previously
                // existed. remove the old search index because the content of this branch
                // now represents the content of the source branch.
                if (is_dir($targetPath)) {
                    P4Cms_FileUtility::deleteRecursive($targetPath);
                }

                // if no existing source index, nothing to do.
                // if we proceeded and took lock on the source directory that creates
                // an empty search index (with a lock file) which breaks lucene.
                if (!is_dir($sourcePath)) {
                    return;
                }

                // lock the source branch's search index so we don't clash with writers.
                $lock = Zend_Search_Lucene_LockManager::obtainReadLock(
                    new Zend_Search_Lucene_Storage_Directory_Filesystem($sourcePath)
                );

                // copy source index files to target.
                P4Cms_FileUtility::copyRecursive($sourcePath, $targetPath);

                // all done.
                $lock->unlock();
            }
        );
    }

    /**
     * Get matching results for the given search string.
     *
     * @param   string  $query  a user provided search string.
     * @return  array   an array of Zend_Search_Lucene_Search_QueryHit objects.
     */
    public static function find($query)
    {
        $index = Search_Module::factory();
        $query = Search_Module::stringToQuery($query);

        return $index->find($query);
    }

    /**
     * Create a lucene search instance for a given site's index folder name.
     *
     * @param  string   $indexName              the index folder name
     * @return Zend_Search_Lucene_Interface     search instance for the site index.
     */
    public static function factory($indexName = null)
    {
        if (!$indexName) {
            $indexName = self::ACTIVE_INDEX_PATH;
        }

        // If we don't already have the search index set up, create one
        if (!array_key_exists($indexName, static::$_searchInstances)) {
            static::$_searchInstances[$indexName] = static::_getSearchIndex($indexName);
        }

        return static::$_searchInstances[$indexName];
    }

    /**
     * Check if there exists a static search instance reference under the given index folder name.
     *
     * @param   string  $indexName  the name of the search index folder
     * @return  boolean             true,  if the search instance exists
     *                              false, otherwise
     */
    public static function hasSearchInstance($indexName = null)
    {
        $exists = false;

        if (!$indexName) {
            $indexName = self::ACTIVE_INDEX_PATH;
        }

        if (isset(static::$_searchInstances[$indexName])) {
           $exists = static::$_searchInstances[$indexName] instanceof Zend_Search_Lucene_Interface;
        }

        return $exists;
    }

    /**
     * Destroy the static search instance references.
     * Intended primarly for testing.
     */
    public static function clearSearchInstances()
    {
        foreach (static::$_searchInstances as $index) {
            if ($index instanceof Zend_Search_Lucene_Interface) {
                $index->__destruct();
            }
        }

        static::$_searchInstances = array();
    }

    /**
     * Produce a lucene query object for a given search string.
     *
     * @param   string  $search     the string based search query.
     * @return  Zend_Search_Lucene_Search_Query     the lucene query object.
     */
    public static function stringToQuery($search)
    {
        $enhanced  = static::_enhanceQuery($search);
        $userQuery = Zend_Search_Lucene_Search_QueryParser::parse($enhanced);
        $query     = new Zend_Search_Lucene_Search_Query_Boolean();
        $query->addSubquery($userQuery, true);

        return $query;
    }

    /**
     * Enhance a user provided search query to fix common problems.
     *
     * @param   string  $query  the query string to enhance.
     * @param   integer $depth  the recursive depth
     * @return  string  the enhanced query string.
     */
    protected static function _enhanceQuery($query, $depth = 0)
    {
        // increase the depth
        $depth++;

        // use Zend's lexer for proper parsing of search queries.
        $lexer = new Zend_Search_Lucene_Search_QueryLexer;

        // catch syntax errors known to us and try to help
        try {
            $tokens = $lexer->tokenize($query, 'UTF-8');
        } catch (Zend_Search_Lucene_Search_QueryParserException $e) {
            // re-throw exception if it's too deep
            if ($depth >= self::MAX_DEPTH) {
                throw $e;
            }

            $error = static::_getQueryParserError($e);

            // if we don't know the error, throw it
            if (empty($error)) {
                throw $e;
            }

            switch ($error['code']) {
                case self::ERROR_TWO_CHARS_LEXEME:
                    $query = substr($query, 0, $error['position'] - 1)
                           . str_repeat($query[$error['position'] - 1], 2)
                           . substr($query, $error['position']);

                    return static::_enhanceQuery($query, $depth);
                    break;

                case self::ERROR_LEXEME_MODIFIER:
                    $query = substr($query, 0, $error['position'] - 1)
                           . ' '
                           . substr($query, $error['position']);

                    return static::_enhanceQuery($query, $depth);
                    break;

                default:
                    throw $e;  // re-throw any unknow queryparser exceptions
                    break;
            }
        }

        // look at each token.
        $newQuery = "";
        for ($i = 0; $i < count($tokens); $i++) {

            $token     = $tokens[$i];
            $prevToken = isset($tokens[$i-1]) ? $tokens[$i-1] : null;
            $nextToken = isset($tokens[$i+1]) ? $tokens[$i+1] : null;

            // extract portion of query associated with this token.
            $start        = $prevToken ? $prevToken->position : 0;
            $length       = $token->position - $start;
            $token->query = substr($query, $start, $length);

            // make word tokens wild by default.
            static::_makeWordTokensWild($token, $prevToken, $nextToken);

            // fix problems with multi-word tokens.
            static::_fixMultiWordTokens($token, $prevToken, $nextToken);

            $newQuery .= $token->query;
        }

        return $newQuery;
    }

    /**
     * Work-around poor handling of multiple-word terms.
     *
     * Multi-word terms such as foo-bar and joe's are treated as individual
     * words which causes them to match more documents than the user likely
     * wants. Additionally, they are incompatible with wildcards and fuzzy
     * searches.
     *
     * Quoting multi-word search terms avoids these problems and seems to
     * be the least offensive thing to do to the user's query.
     *
     * @param   Zend_Search_Lucene_Search_QueryToken    $token      the token to examine for repair.
     * @param   Zend_Search_Lucene_Search_QueryToken    $prevToken  optional - the previous token if there is one.
     * @param   Zend_Search_Lucene_Search_QueryToken    $nextToken  optional - the next token if there is one.
     */
    protected static function _fixMultiWordTokens(
        Zend_Search_Lucene_Search_QueryToken $token,
        Zend_Search_Lucene_Search_QueryToken $prevToken = null,
        Zend_Search_Lucene_Search_QueryToken $nextToken = null)
    {
        // only examine word tokens.
        if ($token->type !== Zend_Search_Lucene_Search_QueryToken::TT_WORD) {
            return;
        }

        // count sub-tokens after removing wildcards.
        $text  = preg_replace('/[\*\?]/', '', $token->text);
        $count = count(Zend_Search_Lucene_Analysis_Analyzer::getDefault()->tokenize($text));

        // if there are multiple parts to this word, quote it.
        if ($count > 1) {
            $token->query = '"' . $token->query . '"';
        }
    }

    /**
     * If user searches for 'foo' we want it to match 'foobar'.
     * This will only happen if we append a wildcard, so we do this
     * automatically for the user.
     *
     * @param   Zend_Search_Lucene_Search_QueryToken    $token      the token to examine.
     * @param   Zend_Search_Lucene_Search_QueryToken    $prevToken  optional - the previous token if there is one.
     * @param   Zend_Search_Lucene_Search_QueryToken    $nextToken  optional - the next token if there is one.
     */
    protected static function _makeWordTokensWild(
        Zend_Search_Lucene_Search_QueryToken $token,
        Zend_Search_Lucene_Search_QueryToken $prevToken = null,
        Zend_Search_Lucene_Search_QueryToken $nextToken = null)
    {
        // only examine word tokens.
        if ($token->type !== Zend_Search_Lucene_Search_QueryToken::TT_WORD) {
            return;
        }

        // if token query length is long enough, append a wildcard.
        if (strlen($token->query) >= static::MIN_PREFIX_LENGTH) {
            $token->query = rtrim($token->query, '*') . "*";
        }
    }

    /**
     * Parse a QueryParserException error message to get error code
     * and the position for errors that we want to handle.
     *
     * @param Zend_Search_Lucene_Search_QueryParserException $e  the exception
     * @return array   the error code and position in the following format:
     *                     array('code' => 1, 'position' => 2)
     *                 array is empty if we don't know the error.
     */
    protected static function _getQueryParserError(
        Zend_Search_Lucene_Search_QueryParserException $e
    )
    {
        $error = array();

        $message = $e->getMessage();

        // Two chars lexeme -- '&&', '||' -- error
        $twoCharsPattern = '/Two chars lexeme expected. Position is ([0-9]+)./';

        // Lexeme modifier char error -- '~' and '^'
        $modifierPattern = '/Lexeme modifier character can be followed'
                         . ' only by number, white space or query syntax'
                         . ' element. Position is ([0-9]+)./';

        // for two chars operators, we correct it
        if (preg_match($twoCharsPattern, $message, $matches)) {
            $error['code']     = self::ERROR_TWO_CHARS_LEXEME;
            $error['position'] = $matches[1];

        } else if (preg_match($modifierPattern, $message, $matches)) {
            $error['code']     = self::ERROR_LEXEME_MODIFIER;
            $error['position'] = $matches[1];
        }

        return $error;
    }

    /**
     * Get a Zend_Search_Lucene instance.  It opens the search index if
     * the index exists.  Otherwise, it will create a new one.
     *
     * @param  string $index       the name of the search index
     *                              (also the folder name).
     * @return Zend_Search_Lucene_Interface   a search instance
     */
    protected static function _getSearchIndex($index)
    {
        // if $index is not a string or it's an empty string
        // we cannot get search index
        if (!is_string($index) || (strlen($index) == 0) ) {
            throw new Zend_Search_Exception(
                'Require a directory to fetch a Search index.'
            );
        }

        // give R/W only for current user and group
        Zend_Search_Lucene_Storage_Directory_Filesystem::setDefaultFilePermissions(0660);

        // set a limit on the size of a result set and set the minimum
        // characters allowed before a wildcard in a query to helps avoid
        // performance problems resulting from too queries that are too broad
        Zend_Search_Lucene::setResultSetLimit(static::MAX_RESULTS);
        Zend_Search_Lucene_Search_Query_Wildcard::setMinPrefixLength(static::MIN_PREFIX_LENGTH);

        // use 'UTF8num' analyzer so words with numbers embedded will
        // be treated as a single token (otherwise considered multi-word).
        Zend_Search_Lucene_Analysis_Analyzer::setDefault(
            new P4Cms_Search_Lucene_Analysis_Analyzer_Common_Utf8Num_CaseInsensitive
        );

        // make space imply AND instead of OR.
        Zend_Search_Lucene_Search_QueryParser::setDefaultOperator(
            Zend_Search_Lucene_Search_QueryParser::B_AND
        );

        $indexFile = P4Cms_Site::fetchActive()->getDataPath() . '/' . $index;

        if (file_exists($indexFile)) {
            $searchInstance = Zend_Search_Lucene::open($indexFile);
        } else {
            $searchInstance = Zend_Search_Lucene::create($indexFile);
        }

        // apply performance tunables if they exist
        $maxBufferedDocs = Search_Module::getMaxBufferedDocs();
        $searchInstance->setMaxBufferedDocs(intval($maxBufferedDocs));

        $maxMergeDocs    = Search_Module::getMaxMergeDocs();
        $searchInstance->setMaxMergeDocs(intval($maxMergeDocs));

        $mergeFactor     = Search_Module::getMergeFactor();
        $searchInstance->setMergeFactor(intval($mergeFactor));

        return $searchInstance;
    }

    /**
     * Get the Module config option.
     *
     * @param string $option  the name of the config option
     * @return mixed          the value of the option,
     *                        null, if the option does not exist
     */
    public static function getOption($option)
    {
        $config = self::getConfig();

        if ($config instanceof Zend_Config) {
            $config = $config->toArray();
        }

        if (isset($config[$option])) {
            return $config[$option];
        }

        return null;
    }

    /**
     * Attempts to normalize the given 'document' into a lucene document
     * object. If the input is an object with a toLuceneDocument method,
     * we will use that.
     *
     * @param   mixed   $document                   the input document to normalize to lucene
     * @return  Zend_Search_Lucene_Document|false   a lucene document object or false if we
     *                                              can't convert the input to lucene.
     *
     * @publishes   p4cms.search.prepareDocument
     *              Return the passed document after making any necessary modifications for proper
     *              indexing. Subscribers can adjust values or take responsibility for converting
     *              the document to Lucene Document format so it can be successfully indexed.
     *              Zend_Search_Lucene_Document|mixed   $document   The document to prepare for
     *                                                              indexing.
     *              mixed                               $original   The original value passed to
     *                                                              'prepareDocument'
     */
    public static function prepareDocument($document)
    {
        $original = $document;

        // can the object turn itself into a lucene doc?
        if (is_object($document) && method_exists($document, 'toLuceneDocument')) {
            try {
                $document = $document->toLuceneDocument();
            } catch (Exception $e) {
                P4Cms_Log::logException(
                    "Failed to create Lucene document.",
                    $e
                );
            }
        }

        // if document is not yet a lucene doc, make one.
        if (!$document instanceof Zend_Search_Lucene_Document) {
            $document = new Zend_Search_Lucene_Document;
        }

        // allow third-parties to influence how document is prepared for index.
        // this is done via the 'filter' technique of pub/sub whereby the first
        // argument passed to each subscriber is the return value of the last.
        $document = P4Cms_PubSub::filter(
            'p4cms.search.prepareDocument',
            $document,
            $original
        );

        // if the document doesn't have any fields, then we were unable
        // to prepare it for indexing, therefore return false.
        if (!$document instanceof Zend_Search_Lucene_Document
            || !count($document->getFieldNames())
        ) {
            return false;
        }

        return $document;
    }

    /**
     * Get the maximum number of documents buffered in memory at one time.
     *
     * @return  string  The maximum number of documents
     */
    public static function getMaxBufferedDocs()
    {
        return Search_Module::getOption('maxBufferedDocs')
            ? Search_Module::getOption('maxBufferedDocs')
            : self::DEFAULT_MAX_BUFFERED_DOCS;
    }

    /**
     * Get the maximum number of documents merged into an index segment by auto-optimization.
     *
     * @return  string   the maximum number of merge documents
     */
    public static function getMaxMergeDocs()
    {
        return Search_Module::getOption('maxMergeDocs')
            ? Search_Module::getOption('maxMergeDocs')
            : PHP_INT_MAX;
    }

    /**
     * Get the Merge Factor.
     *
     * @return  string   the merge factor.
     */
    public static function getMergeFactor()
    {
        return Search_Module::getOption('mergeFactor')
            ? Search_Module::getOption('mergeFactor')
            : self::DEFAULT_MERGE_FACTOR;
    }
}
