<?php
/**
 * Scans the project source code for @pubsub* documentation pragmas, and generates
 * a docbook page of the results.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */

/**
 * This class contain methods for scanning a path for PHP source files, scanning a
 * source file for @publishes pragmas in docblocks, and generating Docbook source
 * for any pubsub topics located. 
 */
class Parser
{
    protected   $_debug = false;

    /**
     * The constructor accepts a debug option, which defaults to false.
     * 
     * @param   bool    $debug  A flag controlling whether debug output is emitted.
     */
    public function __construct($debug = false)
    {
        $this->_debug = $debug;
    }

    /**
     * Emits debugging information, when configured to do so.
     * 
     * @param   string  $message    The debug info to emit, if the debug flag is set.
     * @param   bool    $force      Flag to force debug output, defaults to false.
     */
    public function debug($message, $force = false) {
        if ($this->_debug || $force) {
            error_log($message);
        }
    }

    /**
     * locates PHP files recursively starting in the specified path.
     *
     * @param   string  $path       The path to scan recursively for PHP files.
     * @param   array   $ignores    A list of paths to ignore during scanning.
     * @return  array   The list of PHP files found within the provided tree
     */
    public function findPhpFiles($path, array $ignores = array())
    {
        $results = array();

        // return early if path is to be ignored
        if (isset($ignores) && is_array($ignores) && count($ignores)) {
            foreach ($ignores as $ignore) {
                $ignore = rtrim($ignore, "/");
                if (strpos($path, $ignore) === 0) {
                    return $results;
                }
            }
        }

        if (is_dir($path)) {
            try {
                $files = new RecursiveDirectoryIterator($path);
            } catch (Exception $e) {
                // if we can't open the path, we can't scan it
                return $results;
            }
            foreach ($files as $file) {
                if ($files->isDot()) {
                    continue;
                }
                if ($file->isFile()) {
                    if (preg_match('/\.php$/', $file->getFilename())) {
                        $results[] = $file->getPathname();
                    }
                } else if ($file->isDir()) {
                    $results = array_merge(
                        $results,
                        $this->findPhpFiles($file->getPathname(), $ignores)
                    );
                }
            }
            return $results;
        }

        if (is_file($path)) {
            return array($path);
        }

        return $results;
    }

    /**
     * Scans the stack of captured lines following a topic line, for topic and argument
     * descriptions.
     *
     * @param   array   $topic  The topic to update
     * @param   array   $stack  The stack of lines
     * @param   string  $why    A context label for where scanStack was called.
     * @return  array   The updated topic.
     */
    public function scanStack(Topic $topic, $stack, $why = "")
    {
        if (count($stack)) {
            $this->debug(
                "scanning stack, $why, for topic ". $topic->name .':'. print_r($stack, true)
            );
            $desc = '';
            $var  = '';
            $type = '';
            foreach ($stack as $fragment) {
                if (preg_match('/^([^ ]+)\s+(\$[^ ]+)\s+(.+)$/', $fragment, $matches)) {
                    $this->debug("fragment '$fragment' looks like an arg declaration");
                    $type = $matches[1];
                    $var  = $matches[2];
                    $desc = $matches[3];
                } else {
                    $this->debug("fragment '$fragment' seems to be just description");
                    $desc = $fragment;
                }

                if ($var) {
                    if ($topic->hasArgument($var)) {
                        $this->debug("Appending arg '$var' desc '$desc'");
                        $argument = $topic->getArgument($var);
                        $argument->appendDescription($desc);
                        $topic->setArgument($argument);
                    } else {
                        $this->debug("Adding argument '$var', '$type', '$desc;");
                        $topic->addArgument(
                            array(
                                'name'          => $var,
                                'type'          => $type,
                                'description'   => $desc
                            )
                        );
                    }
                } else {
                    $this->debug("Appending topic description '$desc'");
                    $topic->appendDescription($desc);
                }
            }
        }

        return $topic;
    }

    /**
     * Scans the specified file for pubsub topic pragmas
     * 
     * @param   string  $path   The path to the file to scan
     * @return  array   The list of discovered topics.
     */
    public function scanFile($path)
    {
        $filePath   = ltrim($path, "./");
        $topics     = array();

        $handle     = @fopen($path, 'r');
        if ($handle) {
            $stack   = array();
            $group   = false;
            $topic   = new Topic(array('files' => array($filePath)));
            $counter = 0;
            while (!feof($handle)) {
                $line = fgets($handle, 4096);
                $counter++;

                if (preg_match('/\*\s+@publishes\s+([^ ]+)(.*)$/', $line, $matches)) {
                    $name = trim($matches[1]);
                    $desc = trim($matches[2]);
                    $this->debug("--------Found publishes in '$path' for '$name' on line $counter");

                    // stash the previously assembled topic, if we have one.
                    $topic = $this->scanStack($topic, $stack, "for publishes");
                    if ($topic->isValid()) {
                        $this->debug('==== add topic '. $topic->name .' due to @publishes');
                        $topics[$topic->name] = $topic;
                        $topic = new Topic(array('files' => array($filePath)));
                    } else {
                        $error = $topic->getError();
                        if (!preg_match('/No name specified/', $error)) {
                            error_log(
                                "ERROR: file '$filePath', line ". $topic->line
                                . ", topic '". $topic->name
                                . "': $error"
                            );
                            exit(1);
                        }
                    }

                    // clear the stack for the new topic
                    $stack = array();

                    // assemble the new topic
                    $topic->name        = $name;
                    $topic->description = $desc;
                    $topic->line        = $counter;

                    $group = true;
                    continue;
                }

                if ($group && preg_match('/\*\s+([^@].+)$/', $line, $matches)) {
                    $stack[] = trim($matches[1]);
                } else if ($group) {
                    $group = false;
                    $topic = $this->scanStack($topic, $stack, "for group termination");
                    if ($topic->isValid()) {
                        $this->debug('==== add topic '. $topic->name .' due to closed group');
                        $topics[$topic->name] = $topic;
                        $topic = new Topic(array('files' => array($filePath)));
                    } else {
                        $this->debug(
                            "****** topic '". $topic->name ."' not valid in group termination: "
                            . $topic->getError()
                        );
                    }
                    $stack = array();
                }
            }
            fclose($handle);

            // handle the possible, but rare, case of topic documentation existing at
            // the end of the file
            $topic = $this->scanStack($topic, $stack, "for end of file");
            if ($topic->isValid()) {
                $this->debug('==== add topic '. $topic->name .' due to end of file');
                $topics[$topic->name] = $topic;
                $topic = new Topic(array('files' => array($path)));
                $stack = array();
            } else {
                $error = $topic->getError();
                if (!preg_match('/No name specified/', $error)) {
                    $this->debug(
                        "****** topic '". $topic->name ."' not valid in end of file: "
                        . $topic->getError()
                    );
                }
            }
        }
        return $topics;
    }

    /**
     * Provides the header for the Docbook document being constructed.
     *
     * @return  string  The Docbook source for the document header.
     */
    public function docbookHeader()
    {
        $output = <<<'EOS'
<!-- This file is auto-generated via 'make pubsub'. See pubsub_topics.php for details. -->
<!DOCTYPE section
[
    <!ENTITY % xinclude SYSTEM "../../en/xinclude.mod">
    %xinclude;

    <!-- Add translated specific definitions and snippets -->
    <!ENTITY % language-snippets SYSTEM "../standalone/language-snippets.xml">
    %language-snippets;

    <!-- Fallback to English definitions and snippets (in case of missing translation) -->
    <!ENTITY % language-snippets.default SYSTEM "../../en/standalone/language-snippets.xml">
    %language-snippets.default;
]>
<section id="modules.integration.pubsub.topics">
    <title>Pub/Sub Topics</title>

    <para>
        &product.name; pub/sub topics and arguments are as follows (click on each row to show more
        details about the topic arguments,
        <ulink url="javascript:" onclick="return false;" role="toggle_pubsub_args">click to toggle
        all details</ulink>):
    </para>

<table pgwide="1" frame="all" tabstyle="wide" id="modules.integration.pubsub.topics.table-1">
    <title>Pub/Sub Topics</title>

    <tgroup cols="2">
        <!-- colspec colwidth="50%"/ -->
        <!-- colspec colwidth="50%"/ -->
        <thead>
            <row>
                <entry>Topic</entry>

                <entry>Description</entry>
            </row>
        </thead>
        <tbody>

EOS;

        return $output;
    }

    /**
     * Provides the footer for the Docbook document being constructed.
     *
     * @return  string  The Docbook source for the document footer.
     */
    public function docbookFooter()
    {
        $output = <<<'EOS'
        </tbody>
    </tgroup>
</table>

</section>
<!--
vim:se ts=4 sw=4 et:
-->

EOS;

        return $output;
    }

    /**
     * Escapes content suitable for embedded in a Docbook document.
     * Allows <xref>, <emphasis>, and <varname> tags to pass through.
     *
     * @param   string  $string The string to escape.
     * @return  string  The escaped string.
     */
    public function escape($string)
    {
        // escape all HTML special characters
        $string = htmlspecialchars($string);

        // make an exception for embedded <xref> tags.
        $string = preg_replace(
            '/&lt;xref linkend=&quot;(.+?)&quot;\/&gt;/',
            "<xref linkend=\"$1\"/>",
            $string
        );

        // make an exception for embedded <varname>/<emphasis> tags.
        $string = preg_replace(
            '/&lt;(\/)?(varname|emphasis)&gt;/',
            "<$1$2>",
            $string
        );

        return $string;
    }

    /**
     * Main logic to scan for PHP files, parse for @publishes, and accumulate Topic objects.
     *
     * @param   string  $path       The path to scan for PHP files.
     * @param   array   $ignores    The paths to ignore during path scanning.
     * @return  array   The list of topics found.
     */
    public function collectTopics($path, array $ignores = array())
    {
        $topics     = array();
        $path       = $path ?: '.';

        $files      = $this->findPhpFiles($path, $ignores);
        foreach ($files as $file) {
            $fileTopics = $this->scanFile($file);
            foreach ($fileTopics as $name => $topic) {
                if (!array_key_exists($name, $topics)) {
                    $topics[$name] = $topic;
                    continue;
                }

                // compare to ensure documentation consistency
                $same = true;
                $old = $topics[$name];
                if ($topic->description !== $old->description) {
                    $same = false;
                    error_log(
                        "ERROR: two descriptions for topic '$name':\n"
                        . "1) in '". $old->files[0] ."', line ". $old->line .":\n"
                        . "   '". $old->description ."'\n"
                        . "2) in '". $topic->files[0] ."', line ". $topic->line .":\n"
                        . "   '". $topic->description ."'\n"
                    );
                }

                if (array_diff_assoc($old->order, $topic->order)) {
                    $same = false;
                    error_log(
                        "ERROR: differing argument naming/order for topic '$name':\n"
                        . "1) in '". $old->files[0] ."', line ". $old->line ."\n"
                        . "   '". implode("', '", $old->order) ."'\n"
                        . "2) in '". $topic->files[0] ."', line ". $topic->line ."\n"
                        . "   '". implode("', '", $topic->order) ."'\n"
                    );
                }

                if (array_diff(array_keys($old->arguments), array_keys($topic->arguments))) {
                    $same = false;
                    error_log(
                        "ERROR: differing argument names for topic '$name':\n"
                        . "1) in '". $old->files[0] ."', line ". $old->line ."\n"
                        . "   '". implode("', '", array_keys($old->arguments)) ."'\n"
                        . "2) in '". $topic->files[0] ."', line ". $topic->line ."\n"
                        . "   '". implode("', '", array_keys($topic->arguments)) ."'\n"
                    );
                }

                foreach ($old->arguments as $argName => $arg) {
                    if ($arg->type !== $topic->arguments[$argName]->type) {
                        $same = false;
                        error_log(
                            "ERROR: differing argument type for topic '$name', argument '$argName':\n"
                            . "1) in '". $old->files[0] ."', line ". $old->line ."\n"
                            . "   '". $arg->type ."'\n"
                            . "2) in '". $topic->files[0] ."', line ". $topic->line ."\n"
                            . "   '". $topic->arguments[$argName]->type ."'\n"
                        );
                    }

                    if ($arg->description !== $topic->arguments[$argName]->description) {
                        $same = false;
                        error_log(
                            "ERROR: differing argument description for topic '$name', argument '$argName':\n"
                            . "1) in '". $old->files[0] ."', line ". $old->line ."\n"
                            . "   '". $arg->description ."'\n"
                            . "2) in '". $topic->files[0] ."', line ". $topic->line ."\n"
                            . "   '". $topic->arguments[$argName]->description ."'\n"
                        );
                    }
                }

                // record file location if documentation matches, otherwise exit early
                if ($same) {
                    $topics[$name]->files[] = $file;
                    continue;
                } else {
                    exit(1);
                }
            }
        }

        // sort the topics
        ksort($topics, SORT_STRING);

        return $topics;
    }
}

/**
 * This class models a pubsub topic, including its name, description, arguments, which
 * files contain its documentation, and the line in the first file where the documentation
 * appears.
 */
class Topic
{
    public  $name           = '';
    public  $description    = '';
    public  $arguments      = array();
    public  $order          = array();
    public  $files          = array();
    public  $line           = 0;

    protected   $_fields        = array(
        'name', 'description', 'arguments', 'order', 'files', 'line'
    );
    protected   $_error         = '';

    public function __construct($options = array())
    {
        foreach ($this->_fields as $field) {
            if (isset($options[$field])) {
                $this->$field = $options[$field];
            }
        }
    }

    public function toString()
    {
        $output = '';
        foreach ($this->_fields as $field) {
            $output .= "Field $field: ". print_r($this->$field, true) ."\n";
        }

        return $output;
    }

    public function getError()
    {
        return $this->_error ."\n". $this->toString();
    }

    public function isValid()
    {
        if (!$this->name) {
            $this->_error = "No name specified.";
            return false;
        }
        if (!$this->description) {
            $this->_error = "No description specified.";
            return false;
        }

        // ensure that all the arguments listed in the order list exist
        $exists = array();
        foreach ($this->order as $name) {
            if (!array_key_exists($name, $this->arguments)) {
                $this->_error = "Argument '$name' does not exist.";
                return false;
            }
            $exists[$name] = true;
        }

        // ensure that all the arguments exist in the order list.
        foreach ($this->arguments as $name => $arg) {
            if (!array_key_exists($name, $exists)) {
                $this->_error = "Argument '$name' is not ordered.";
                return false;
            }
        }

        $this->_error = '';
        return true;
    }

    public function addArgument($argument)
    {
        if (is_array($argument)) {
            $argument = new Argument($argument);
        }
        $this->setArgument($argument);
        $this->order[] = $argument->name;

        return $this;
    }

    public function getArgument($name)
    {
        if (!array_key_exists($name, $this->arguments)) {
            throw new InvalidArgumentException("Cannot find argument '$name'");
        }

        return $this->arguments[$name];
    }

    public function hasOrder($name)
    {
        return in_array($name, $this->order);
    }

    public function hasArgument($name)
    {
        try {
            $argument = $this->getArgument($name);
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

    public function setArgument($argument)
    {
        if (is_array($argument)) {
            $argument = new Argument($argument);
        }
        if (!$argument instanceof Argument) {
            throw new InvalidArgumentException("Cannot add a non-argument as an argument.");
        }

        $this->arguments[$argument->name] = $argument;
    }

    function appendDescription($description)
    {
        if (strlen($this->description)) {
            $this->description .= " ";
        }
        $this->description .= $description;

        return $this;
    }
}

/**
 * This class models pubsub topic arguments, including their name, type, and description.
 */
class Argument
{
    public  $name           = '';
    public  $type           = '';
    public  $description    = '';

    protected   $_fields        = array(
        'name', 'type', 'description'
    );

    /**
     * Accept configuration options during object construction.
     * 
     * @param   array   $options    The options
     */
    public function __construct($options = array())
    {
        foreach ($this->_fields as $field) {
            if (isset($options[$field])) {
                $this->$field = $options[$field];
            }
        }
    }

    /**
     * Append additional text to the description.
     * 
     * @param   string      $description    The additional text to append.
     * @return  Argument    Provides a fluent interface.
     */
    function appendDescription($description)
    {
        if (strlen($this->description)) {
            $this->description .= " ";
        }
        $this->description .= $description;

        return $this;
    }
}

$path    = (isset($argv[1])) ? $argv[1] : '.';
$ignores = array_slice($argv, 2);

$parser  = new Parser(false);
$topics  = $parser->collectTopics($path, $ignores);

// compose output
$output = $parser->docbookHeader();
foreach ($topics as $name => $topic) {
    $rowSep = "";
    $output .= "$rowSep            <row>
                <entry>
                    <emphasis role=\"pubsub-topic\">". $parser->escape($name) ."</emphasis>";

    if (count($topic->order)) {
        $output .= "\n                    (&#xA0;";
        $separator = "";
        foreach ($topic->order as $name) {
            $arg = $topic->getArgument($name);
            $output .= "$separator<varname>". $parser->escape($name) ."</varname>";
            $separator = ",&#xA0;";
        }
        $output .= "&#xA0;)
                    <informaltable tabstyle=\"args\">
                        <tgroup cols=\"3\">
                            <thead>
                                <row>
                                    <entry>Type</entry>
                                    <entry>Argument</entry>
                                    <entry>Description</entry>
                                </row>
                            </thead>
                            <tbody>";
        $separator = "";
        foreach ($topic->order as $name) {
            $arg = $topic->getArgument($name);
            $output .= "$separator
                                <row>
                                    <entry><classname>"
                    . $parser->escape($arg->type)
                    . "</classname></entry>
                                    <entry><varname>"
                    . $parser->escape($name)
                    . "</varname></entry>
                                    <entry>"
                    . $parser->escape($arg->description)
                    . "</entry>
                                </row>";
            $separator = "\n";
        }
        $output .= "
                            </tbody>
                        </tgroup>
                    </informaltable>";
    }

    $output .= "
                </entry>

                <entry>
                    ". $parser->escape($topic->description) ."
                </entry>
            </row>\n";
    $rowSep = "\n";
}

$output .= $parser->docbookFooter();
print $output;